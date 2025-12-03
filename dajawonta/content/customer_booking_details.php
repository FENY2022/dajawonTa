<?php
ob_start();
session_start();
require '../db.php'; // Database connection file

// --- CUSTOMER AUTHENTICATION ---
// Ensure the customer is logged in.
// We assume the customer's user ID is stored in 'customer_user_id'
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to login page
    header("Location: login.php"); // Adjust to your login page
    exit;
}

$customer_user_id = $_SESSION['user_id'];

// Enable detailed error display for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ---------------------------------------------------------------------
// === CORE LOGIC STARTS HERE ===
// ---------------------------------------------------------------------

// === VALIDATE BOOKING ID ===
if (!isset($_GET['booking_id']) || empty($_GET['booking_id'])) {
    die("<div class='alert alert-danger'>Invalid booking ID.</div>");
}
$booking_id = intval($_GET['booking_id']);


// === FETCH BOOKING DETAILS ===
// Security: We fetch the booking *only* if the ID matches
// AND the customer_id matches the logged-in user's session ID.
$stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("<div class='alert alert-danger'>Booking not found.</div>");
}
$booking = $result->fetch_assoc();
$stmt->close();

// === FETCH PROVIDER INFO ===
$provider_name = "Unknown Provider";
$pstmt = $conn->prepare("SELECT company_name FROM service_providers WHERE id = ?");
$pstmt->bind_param("i", $booking['provider_id']);
$pstmt->execute();
$presult = $pstmt->get_result();
if ($presult->num_rows > 0) {
    $prov = $presult->fetch_assoc();
    $provider_name = $prov['company_name'];
}
$pstmt->close();
$conn->close();

// Helper function to determine status badge class
function get_status_badge_class($status) {
    switch ($status) {
        case 'approved':
            return 'status-approved';
        case 'pending':
            return 'status-pending';
        case 'declined':
            return 'status-declined';
        case 'completed':
            return 'status-completed';
        default:
            return 'status-pending';
    }
}

// Helper function to determine payment status badge class
function get_payment_badge_class($status) {
    switch ($status) {
        case 'paid':
            return 'bg-success';
        case 'unpaid':
            return 'bg-warning text-dark';
        default:
            return 'bg-secondary';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking #<?php echo $booking_id; ?> | DajawonTa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #198754; /* Bootstrap success */
            --light-bg: #f8f9fa;
            --dark-text: #212529;
            --border-radius: 12px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        
        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--dark-text);
            line-height: 1.6;
        }
        
        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-weight: 600;
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-declined {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .info-item {
            display: flex;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .info-label {
            font-weight: 600;
            min-width: 150px;
            color: #6c757d;
        }
        
        .info-value {
            color: var(--dark-text);
        }
        
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1055;
        }
        
        .price-highlight {
            font-size: 2.25rem;
            font-weight: 700;
            color: var(--primary-color);
            text-align: center;
        }
        
        .payment-card {
            background-color: var(--light-bg);
            border: 1px solid #dee2e6;
            border-radius: var(--border-radius);
        }
        
        .btn-pay-now {
            background-color: var(--success-color);
            border-color: var(--success-color);
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 700;
            font-size: 1.1rem;
            transition: var(--transition);
        }
        
        .btn-pay-now:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>


    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">Booking Details</h1>
                <p class="text-muted mb-0">Booking #<?php echo $booking_id; ?></p>
            </div>
            <div class="d-flex align-items-center">
                <span class="status-badge <?php echo get_status_badge_class($booking['booking_status']); ?> me-3">
                    <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>
                    Booking: <?php echo ucfirst($booking['booking_status']); ?>
                </span>
                <a href="my_bookings.php" class="btn btn-outline-secondary"> <i class="fas fa-arrow-left me-1"></i> Back to My Bookings
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['toast_message'])): ?>
        <div class="toast-container">
            <div class="toast align-items-center text-white bg-<?php echo $_SESSION['toast_type']; ?> border-0 show" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <?php echo $_SESSION['toast_message']; ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        </div>
        <?php 
            unset($_SESSION['toast_message']); 
            unset($_SESSION['toast_type']); 
        ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-7">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-info-circle me-2"></i>Booking Summary</span>
                    </div>
                    <div class="card-body">
                        <h6 class="text-muted mb-3">Service Details</h6>
                        <div class="info-item">
                            <span class="info-label">Provider:</span>
                            <span class="info-value fw-bold"><?php echo $provider_name; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Date:</span>
                            <span class="info-value"><?php echo date('M j, Y', strtotime($booking['booking_date_from'])); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Time:</span>
                            <span class="info-value"><?php echo date('g:i A', strtotime($booking['booking_time_from'])); ?></span>
                        </div>

                        <h6 class="text-muted mt-4 mb-3">Your Information</h6>
                        <div class="info-item">
                            <span class="info-label">Name:</span>
                            <span class="info-value"><?php echo $booking['customer_name']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?php echo $booking['customer_email']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Phone:</span>
                            <span class="info-value"><?php echo $booking['customer_phone']; ?></span>
                        </div>
                        
                        <?php if (!empty($booking['special_request'])): // Using 'special_request' from schema ?>
                        <div class="mt-3">
                            <h6 class="text-muted mb-2">Special Requests</h6>
                            <div class="alert alert-light border">
                                <?php echo nl2br(htmlspecialchars($booking['special_request'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card mb-4 payment-card">
                    <div class="card-header bg-transparent">
                        <i class="fas fa-credit-card me-2"></i>Payment & Status
                    </div>
                    <div class="card-body text-center p-4">
                        <label class="text-muted fs-6">Total Amount Due</label>
                        <div class="price-highlight mb-4">
                            ₱<?php echo number_format($booking['total_price'], 2); ?>
                        </div>
                        
                        <div class="d-flex justify-content-center mb-4">
                            <span class="badge fs-6 <?php echo get_payment_badge_class($booking['payment_status']); ?>">
                                Payment: <?php echo ucfirst($booking['payment_status']); ?>
                            </span>
                        </div>

                        <?php
                        // --- CONDITIONAL PAYMENT LOGIC ---
                        
                        // CASE 1: Approved, Unpaid, and Payment Link Exists -> SHOW PAY BUTTON
                        if ($booking['booking_status'] === 'approved' && $booking['payment_status'] === 'unpaid' && !empty($booking['payment_link'])) :
                        ?>
                            <div class="alert alert-info">
                                <i class="fas fa-check-circle me-1"></i>
                                Your booking is approved! Please complete your payment to confirm.
                            </div>
                            <a href="<?php echo $booking['payment_link']; ?>" class="btn btn-pay-now w-100">
                                <i class="fas fa-shield-alt me-2"></i> Proceed to Secure Payment
                            </a>

                        <?php
                        // CASE 2: Already Paid -> SHOW CONFIRMED
                        elseif ($booking['payment_status'] === 'paid') :
                        ?>
                            <div class="alert alert-success">
                                <i class="fas fa-party-horn me-1"></i>
                                Payment received! Your booking is confirmed.
                            </div>

                        <?php
                        // CASE 3: Still Pending Approval -> SHOW PENDING
                        elseif ($booking['booking_status'] === 'pending') :
                        ?>
                            <div class="alert alert-warning text-dark">
                                <i class="fas fa-hourglass-half me-1"></i>
                                Your booking is pending approval from the provider.
                            </div>
                        
                        <?php
                        // CASE 4: Declined -> SHOW DECLINED
                        elseif ($booking['booking_status'] === 'declined') :
                        ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-times-circle me-1"></i>
                                We're sorry, this booking has been declined.
                            </div>

                        <?php
                        // CASE 5: Completed -> SHOW COMPLETED
                        elseif ($booking['booking_status'] === 'completed') :
                        ?>
                            <div class="alert alert-secondary">
                                <i class="fas fa-star me-1"></i>
                                This booking has been completed. Thank you!
                            </div>

                        <?php
                        // CASE 6: Other (e.g., Approved but no link) -> SHOW ERROR
                        else :
                        ?>
                            <div class="alert alert-light border">
                                Please check back later for a payment link or contact the provider.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-history me-2"></i>Booking Timeline
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled timeline">
                            <li class="mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-circle text-success" style="font-size: 0.5rem;"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="mb-0 fw-semibold">Booking Created</p>
                                        <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($booking['created_at'])); ?></small>
                                    </div>
                                </div>
                            </li>
                            <li class="mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-circle text-<?php echo str_replace('status-', '', get_status_badge_class($booking['booking_status'])); ?>" style="font-size: 0.5rem;"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="mb-0 fw-semibold">Status: <?php echo ucfirst($booking['booking_status']); ?></p>
                                        <small class="text-muted">Current status</small>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide toast after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const toastElList = [].slice.call(document.querySelectorAll('.toast'));
            const toastList = toastElList.map(function(toastEl) {
                const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
                toast.show();
                return toast;
            });
        });
    </script>
</body>
</html>

<?php
ob_end_flush();
?>