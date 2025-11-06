<?php
ob_start();
session_start();
require '../db.php'; // Database connection file

// === CONFIGURATION ===
define('PAYMONGO_SECRET_KEY', 'sk_test_96buPr5S5wCEGUSjiEizVgMx'); // ✅ Use SECRET key, not publishable
// NOTE: I've updated the SITE_BASE_URL to point to the base dashboard path,
// but the success/cancel URLs will need to be correctly configured for your environment.
// define('SITE_BASE_URL', 'http://localhost/dajawonTa/dajawonta/'); 
define('SITE_BASE_URL', 'http://dajawonta.online'); 


// Enable detailed error display for debugging (only for UAT)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// === FUNCTION: CREATE PAYMONGO CHECKOUT SESSION ===
function create_paymongo_checkout_session($booking)
{
    error_log("➡️ Creating PayMongo checkout for booking ID: " . $booking['id']);

    // Validate total price
    if (!isset($booking['total_price']) || !is_numeric($booking['total_price'])) {
        return ['error' => 'Invalid or missing total_price in booking record.'];
    }
    $amount_in_centavos = (int)($booking['total_price'] * 100);
    if ($amount_in_centavos < 2000) {
        return ['error' => 'Total price must be at least PHP 20.00 (PayMongo minimum).'];
    }

    // Validate customer info
    $name = $booking['customer_name'] ?? '';
    $email = $booking['customer_email'] ?? '';
    $phone = $booking['customer_phone'] ?? '';

    if (empty($name)) return ['error' => 'Missing customer name.'];
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) return ['error' => 'Invalid customer email.'];
    if (strlen(preg_replace('/[^0-9]/', '', $phone)) < 10) return ['error' => 'Invalid customer phone number.'];

    // === Build Payload ===
    $payload = [
        'data' => [
            'attributes' => [
                'billing' => [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone
                ],
                'send_email_receipt' => true,
                'show_description' => true,
                'show_line_items' => true,
                'line_items' => [[
                    'currency' => 'PHP',
                    'amount' => $amount_in_centavos,
                    'description' => 'Booking service for ' . date('M j, Y', strtotime($booking['booking_date_from'])),
                    'name' => 'Booking #' . $booking['id'],
                    'quantity' => 1
                ]],
                'payment_method_types' => ['card', 'gcash', 'paymaya', 'grab_pay'],
                'description' => 'Payment for Booking #' . $booking['id'],
                'success_url' => SITE_BASE_URL . '/payment_success.php?booking_id=' . $booking['id'],
                'cancel_url' 	=> SITE_BASE_URL . '/payment_cancel.php?booking_id=' . $booking['id'],
                'reference_number' => 'BOOKING_' . $booking['id']
            ]
        ]
    ];

    // === CURL REQUEST ===
    $ch = curl_init('https://api.paymongo.com/v1/checkout_sessions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_USERPWD => PAYMONGO_SECRET_KEY . ':',
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_SSL_VERIFYPEER => false // ✅ Use false for localhost only
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        error_log("❌ CURL Error: $curl_error");
        return ['error' => $curl_error];
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("❌ JSON Decode Error: " . json_last_error_msg());
        return ['error' => 'Invalid JSON response from PayMongo.'];
    }

    // Handle PayMongo API errors
    if (isset($data['errors'][0]['detail'])) {
        error_log("⚠️ PayMongo API Error: " . $data['errors'][0]['detail']);
        return ['error' => $data['errors'][0]['detail']];
    }

    // Success
    if ($http_code === 200 && isset($data['data']['attributes']['checkout_url'])) {
        return [
            'checkout_url' => $data['data']['attributes']['checkout_url'],
            'checkout_id' => $data['data']['id']
        ];
    }

    // Unexpected response
    error_log("⚠️ Unexpected PayMongo Response: " . $response);
    return ['error' => "Unexpected response (HTTP $http_code): " . $response];
}

// ---------------------------------------------------------------------
// === FUNCTION: CREATE NOTIFICATION (NEW) ===
// ---------------------------------------------------------------------
/**
 * Inserts a new notification into the database for a user.
 *
 * @param mysqli $conn The database connection.
 * @param int $user_id The ID of the user to notify (the customer's user_id).
 * @param string $message The notification message text.
 * @param string $link The optional clickable link (e.g., to the booking details page).
 * @param int $role The role of the user (e.g., 3 for customer).
 * @return bool True on success, false on failure.
 */
// --- MODIFICATION HERE: Default role changed from 1 to 3 ---
function create_notification($conn, $user_id, $message, $link, $role = 3)
{
    // The $role parameter determines who sees the notification (e.g., customer, provider).
    $stmt = $conn->prepare(
        "INSERT INTO notifications (user_id, message, link, role) VALUES (?, ?, ?, ?)"
    );
    
    if ($stmt === false) {
        error_log("Notification prepare() failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("issi", $user_id, $message, $link, $role);
    $success = $stmt->execute();
    
    if (!$success) {
        error_log("Notification execute() failed: " . $stmt->error);
    }
    
    $stmt->close();
    return $success;
}

// ---------------------------------------------------------------------
// === CORE LOGIC STARTS HERE ===
// ---------------------------------------------------------------------

// === VALIDATE BOOKING ID ===
if (!isset($_GET['booking_id']) || empty($_GET['booking_id'])) {
    die("<div class='alert alert-danger'>Invalid booking ID.</div>");
}
$booking_id = intval($_GET['booking_id']);

// === FETCH BOOKING DETAILS ===
// ⚠️ Ensure your bookings table includes the 'customer_id' and 'role' columns
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
$provider_user_id = null;
$pstmt = $conn->prepare("SELECT company_name, user_id FROM service_providers WHERE id = ?");
$pstmt->bind_param("i", $booking['provider_id']);
$pstmt->execute();
$presult = $pstmt->get_result();
if ($presult->num_rows > 0) {
    $prov = $presult->fetch_assoc();
    $provider_name = $prov['company_name'];
    $provider_user_id = $prov['user_id'];
}
$pstmt->close();

// ---------------------------------------------------------------------
// === HANDLE STATUS UPDATE (REVISED) ===
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['booking_status'] ?? '';
    $valid_status = ['approved', 'declined', 'completed'];

    if (!in_array($new_status, $valid_status)) {
        $_SESSION['toast_message'] = "Invalid booking status.";
        $_SESSION['toast_type'] = "danger";
        header("Location: customer_booking_details.php?booking_id=$booking_id");
        exit;
    }

    $is_success = false;
    $notification_message = ""; 
    
    $customer_id = $booking['customer_id'] ?? null; // Get the customer's user ID

    if ($new_status === 'approved') {
        $paymongo = create_paymongo_checkout_session($booking);
        
        if (isset($paymongo['checkout_url'])) {
            $link = $paymongo['checkout_url'];
            $pid = $paymongo['checkout_id'];
            $is_approve = 1;
            
            $update = $conn->prepare("UPDATE bookings SET booking_status=?, payment_link=?, payment_id=?, is_approve=? WHERE id=?");
            $update->bind_param("sssii", $new_status, $link, $pid, $is_approve, $booking_id);
            $is_success = $update->execute();
            $update->close();

            $notification_message = $is_success ?
                "Booking approved! Payment link generated." :
                "Failed to update booking: " . $conn->error;

            // Create notification for customer
            if ($is_success && $customer_id) {
                $notif_message = "Your booking (#$booking_id) with $provider_name has been approved! Please proceed with payment.";
                // Adjust this to the customer's detail page link
       
                $notif_link = "dashboard.php?action=customer_booking_details&booking_id=$booking_id";

                // Use the role from the booking row, default to 3 (customer) if not set.
                create_notification($conn, $customer_id, $notif_message, $notif_link, $booking['role'] ?? 3);
            } elseif ($is_success) {
                error_log("Could not create 'approved' notification: customer_id not found for booking ID $booking_id.");
            }

        } else {
            $notification_message = "PayMongo failed: " . ($paymongo['error'] ?? 'Unknown error.');
        }

    } elseif ($new_status === 'declined' || $new_status === 'completed') {
        $is_approve = ($new_status === 'completed') ? 1 : 0;
        
        $update = $conn->prepare("UPDATE bookings SET booking_status=?, is_approve=? WHERE id=?");
        $update->bind_param("sii", $new_status, $is_approve, $booking_id);
        $is_success = $update->execute();
        $update->close();

        $notification_message = $is_success ?
            "Booking marked as $new_status." :
            "Failed to update booking: " . $conn->error;

        // Create notification for 'declined' status
        if ($is_success && $new_status === 'declined') {
            if ($customer_id) {
                $notif_message = "We're sorry, your booking (#$booking_id) with $provider_name has been declined.";
                // Adjust this to the customer's detail page link
                $notif_link = "customer_booking_details.php?booking_id=$booking_id";
                
                // Use the role from the booking row, default to 3 (customer) if not set.
                create_notification($conn, $customer_id, $notif_message, $notif_link, $booking['role'] ?? 3);
            } else {
                error_log("Could not create 'declined' notification: customer_id not found for booking ID $booking_id.");
            }
        }
    }

    // === TOAST MESSAGE HANDLER ===
    $_SESSION['toast_message'] = $notification_message;
    $_SESSION['toast_type'] = $is_success ? 'success' : 'danger';

    header("Location: provider_booking_details.php?booking_id=$booking_id");
    exit;
}

$conn->close();
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
            --success-color: #4cc9f0;
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
            transition: var(--transition);
            margin-bottom: 1.5rem;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
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
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            transition: var(--transition);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1055;
        }
        
        .toast {
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: none;
        }
        
        .section-title {
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid rgba(67, 97, 238, 0.2);
        }
        
        .action-btn {
            border-radius: 8px;
            padding: 0.5rem 1.25rem;
            font-weight: 600;
            transition: var(--transition);
        }
        
        .debug-toggle {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .debug-toggle:hover {
            background-color: #e9ecef;
        }
        
        .debug-content {
            display: none;
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            font-family: monospace;
            font-size: 0.85rem;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .price-highlight {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .payment-link {
            background-color: #e7f3ff;
            border-radius: 8px;
            padding: 0.75rem;
            margin-top: 1rem;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-calendar-check me-2"></i>DajawonTa
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="bookings.php"><i class="fas fa-list me-1"></i> Bookings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php"><i class="fas fa-user me-1"></i> Profile</a>
                    </li>
                </ul>
            }
        </div>
    </nav>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">Booking Details</h1>
                <p class="text-muted mb-0">Booking #<?php echo $booking_id; ?> • <?php echo $provider_name; ?></p>
            </div>
            <div class="d-flex align-items-center">
                <span class="status-badge status-<?php echo $booking['booking_status']; ?> me-3">
                    <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>
                    <?php echo ucfirst($booking['booking_status']); ?>
                </span>
                <a href="bookings.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Bookings
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
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-user-circle me-2"></i>Customer & Service Information</span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-3">Customer Details</h6>
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
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-3">Service Details</h6>
                                <div class="info-item">
                                    <span class="info-label">Service Type:</span>
                                    <span class="info-value"><?php echo $booking['service_type'] ?? 'Not specified'; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Date:</span>
                                    <span class="info-value"><?php echo date('M j, Y', strtotime($booking['booking_date_from'])); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Time:</span>
                                    <span class="info-value"><?php echo date('g:i A', strtotime($booking['booking_date_from'])); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($booking['special_requests'])): ?>
                        <div class="mt-3">
                            <h6 class="text-muted mb-2">Special Requests</h6>
                            <div class="alert alert-light border">
                                <?php echo $booking['special_requests']; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-credit-card me-2"></i>Payment Information
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <span class="info-label">Total Amount:</span>
                                    <span class="price-highlight ms-2">₱<?php echo number_format($booking['total_price'], 2); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Payment Status:</span>
                                    <span class="info-value">
                                        <?php 
                                        if ($booking['payment_status'] === 'paid') {
                                            echo '<span class="badge bg-success">Paid</span>';
                                        } else {
                                            echo '<span class="badge bg-warning text-dark">Pending</span>';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <?php if (!empty($booking['payment_link'])): ?>
                                <div class="payment-link">
                                    <h6 class="mb-2">Payment Link</h6>
                                    <p class="mb-2 small">Share this link with the customer for payment:</p>
                                    <a href="<?php echo $booking['payment_link']; ?>" target="_blank" class="text-break">
                                        <?php echo $booking['payment_link']; ?>
                                    </a>
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-outline-primary" onclick="copyToClipboard('<?php echo $booking['payment_link']; ?>')">
                                            <i class="fas fa-copy me-1"></i> Copy Link
                                        </button>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-tasks me-2"></i>Booking Actions
                    </div>
                    <div class="card-body">
                        <form method="POST" id="statusForm">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Update Booking Status</label>
                                <select name="booking_status" class="form-select" id="statusSelect" required>
                                    <option value="">-- Select Action --</option>
                                    <option value="approved" data-description="Approve this booking and generate a payment link for the customer.">Approve & Generate Payment Link</option>
                                    <option value="declined" data-description="Decline this booking. The customer will be notified.">Decline Booking</option>
                                    <option value="completed" data-description="Mark this booking as completed. This will finalize the transaction.">Mark as Completed</option>
                                </select>
                                <div class="form-text mt-2" id="statusDescription"></div>
                            </div>
                            <button type="submit" name="update_status" class="btn btn-primary w-100 action-btn">
                                <i class="fas fa-save me-1"></i> Update Status
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card mb-4">
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
                                        <i class="fas fa-circle text-<?php echo $booking['booking_status'] === 'pending' ? 'warning' : ($booking['booking_status'] === 'approved' ? 'info' : ($booking['booking_status'] === 'declined' ? 'danger' : 'success')); ?>" style="font-size: 0.5rem;"></i>
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

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-code me-2"></i>Debug Information</span>
                    </div>
                    <div class="card-body">
                        <div class="debug-toggle" onclick="toggleDebug()">
                            <i class="fas fa-terminal me-1"></i> Toggle Raw Data
                        </div>
                        <div class="debug-content" id="debugContent">
                            <pre><?php print_r($booking); ?></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Status description display
        document.getElementById('statusSelect').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const description = selectedOption.getAttribute('data-description') || '';
            document.getElementById('statusDescription').textContent = description;
        });
        
        // Toggle debug information
        function toggleDebug() {
            const debugContent = document.getElementById('debugContent');
            debugContent.style.display = debugContent.style.display === 'block' ? 'none' : 'block';
        }
        
        // Copy to clipboard function
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show a small notification
                const toast = document.createElement('div');
                toast.className = 'toast align-items-center text-white bg-success border-0 show';
                toast.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">
                            Payment link copied to clipboard!
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                `;
                document.querySelector('.toast-container').appendChild(toast);
                
                // Auto remove after 3 seconds
                setTimeout(() => {
                    toast.remove();
                }, 3000);
            }, function(err) {
                console.error('Could not copy text: ', err);
            });
        }
        
        // Auto-hide toast after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const toastEl = document.querySelector('.toast');
            if (toastEl) {
                setTimeout(() => {
                    // Initialize Bootstrap Toast and hide it
                    const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
                    toast.hide();
                }, 5000);
            }
        });
    </script>
</body>
</html>

<?php
ob_end_flush();
?>