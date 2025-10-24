<?php
session_start();
require '../db.php';

// --- NEW: PayMongo UAT (Test) Configuration ---
// !! REPLACE with your actual PayMongo TEST keys !!
define('PAYMONGO_SECRET_KEY', 'sk_test_YOUR_SECRET_KEY_HERE');
// This is the URL of your site. PayMongo needs absolute URLs.
define('SITE_BASE_URL', 'https://your-website.com'); // e.g., http://localhost/myproject

/**
 * --- NEW: PayMongo Helper Function ---
 * Creates a PayMongo Checkout Session and returns the checkout URL and ID.
 *
 * @param array $booking The booking details array. Must contain:
 * id, total_price, customer_name, customer_email, customer_phone
 * @return array ['checkout_url' => string, 'checkout_id' => string] on success,
 * ['error' => string] on failure.
 */
function create_paymongo_checkout_session($booking) {
    // 1. Validate price and convert to centavos
    // This script ASSUMES $booking['total_price'] exists and is a decimal value (e.g., 100.00)
    if (!isset($booking['total_price']) || !is_numeric($booking['total_price'])) {
        return ['error' => 'Invalid or missing total_price in booking.'];
    }
    $amount_in_centavos = (int) ($booking['total_price'] * 100);

    if ($amount_in_centavos <= 0) {
         return ['error' => 'Total price must be greater than 0.'];
    }

    // 2. Define the payload for PayMongo API
    $payload = [
        'data' => [
            'attributes' => [
                'billing' => [
                    'name'  => $booking['customer_name'],
                    'email' => $booking['customer_email'],
                    'phone' => $booking['customer_phone']
                ],
                'send_email_receipt' => true,
                'show_description'   => true,
                'show_line_items'    => true,
                'line_items' => [
                    [
                        'currency'    => 'PHP',
                        'amount'      => $amount_in_centavos,
                        'description' => 'Service for ' . date('M j, Y', strtotime($booking['booking_date_from'])),
                        'name'        => 'Booking #' . $booking['id'],
                        'quantity'    => 1
                    ]
                ],
                'payment_method_types' => ['card', 'gcash', 'paymaya', 'grab_pay'],
                'description'          => 'Payment for Booking #' . $booking['id'],
                // --- !! MODIFIED: UPDATE THESE URLS !! ---
                'success_url' => SITE_BASE_URL . '/dashboard.php?action=payment_success&booking_id=' . $booking['id'],
                'cancel_url'  => SITE_BASE_URL . '/dashboard.php?action=view_booking&booking_id=' . $booking['id'],
                'reference_number' => 'BOOKING_' . $booking['id']
            ]
        ]
    ];

    // 3. Initialize cURL request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.paymongo.com/v1/checkout_sessions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    // Use Basic Auth: secret_key as username, blank password
    curl_setopt($ch, CURLOPT_USERPWD, PAYMONGO_SECRET_KEY . ':');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
    ]);

    $response_body = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $response_data = json_decode($response_body, true);

    // 4. Handle response
    if ($http_code === 200 && isset($response_data['data']['id'])) {
        return [
            'checkout_id'  => $response_data['data']['id'],
            'checkout_url' => $response_data['data']['attributes']['checkout_url']
        ];
    } else {
        // Log error for debugging
        error_log("PayMongo API Error: " . $response_body);
        $error_message = 'Failed to create payment link.';
        if (isset($response_data['errors'][0]['detail'])) {
            $error_message .= ' ' . $response_data['errors'][0]['detail'];
        }
        return ['error' => $error_message];
    }
}


// --- 1. Validate booking ID ---
if (!isset($_GET['booking_id']) || empty($_GET['booking_id'])) {
    // Using a more structured error page would be better in a real app
    echo "<!DOCTYPE html><html lang='en'><head><title>Error</title><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'></head><body><div class='container mt-5'><div class='alert alert-danger'>Invalid booking ID.</div><a href='dashboard.php' class='btn btn-primary'>Go to Dashboard</a></div></body></html>";
    exit;
}

$booking_id = intval($_GET['booking_id']);

// --- 2. Fetch booking details (MODIFIED: We now assume total_price is in the table) ---
$query = "SELECT * FROM bookings WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<!DOCTYPE html><html lang='en'><head><title>Error</title><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'></head><body><div class='container mt-5'><div class='alert alert-danger'>Booking not found.</div><a href='dashboard.php' class='btn btn-primary'>Go to Dashboard</a></div></body></html>";
    exit;
}

$booking = $result->fetch_assoc();
$stmt->close();

// --- 3. Fetch provider details ---
$provider_name = "Unknown Provider";
$provider_user_id = null;

$provider_query = "SELECT company_name AS provider_name, user_id FROM service_providers WHERE id = ?";
$provider_stmt = $conn->prepare($provider_query);
$provider_stmt->bind_param("i", $booking['provider_id']);
$provider_stmt->execute();
$provider_result = $provider_stmt->get_result();

if ($provider_result->num_rows > 0) {
    $provider = $provider_result->fetch_assoc();
    $provider_name = $provider['provider_name'];
    $provider_user_id = $provider['user_id'];
}
$provider_stmt->close();


// --- 4. MODIFIED: Update booking status ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $new_status = $_POST['booking_status'];

    // Input validation for status
    $valid_statuses = ['approved', 'declined', 'completed'];
    if (!in_array($new_status, $valid_statuses)) {
        $_SESSION['toast_message'] = "Invalid status selected.";
        $_SESSION['toast_type'] = "danger";
        header("Location: dashboard.php?action=provider_booking_details&booking_id=" . $booking_id);
        exit;
    }

    $is_success = false;
    $notification_message = "";
    $notification_link = "";
    $update = null; // Initialize $update to null

    if ($new_status == 'approved') {
        // --- NEW: PayMongo integration for 'approved' status ---

        // 1. Check if booking already has a payment link or is already approved
        if (!empty($booking['payment_link']) && $booking['booking_status'] == 'approved') {
             $_SESSION['toast_message'] = "Booking is already approved and has a payment link.";
             $_SESSION['toast_type'] = "warning";
             header("Location: dashboard.php?action=provider_booking_details&booking_id=" . $booking_id);
             exit;
        }

        // 2. Create PayMongo Checkout Session
        $paymongo_data = create_paymongo_checkout_session($booking);

        if (isset($paymongo_data['checkout_url'])) {
            $payment_link = $paymongo_data['checkout_url'];
            $payment_id   = $paymongo_data['checkout_id'];

            // 3. Update database with new status AND payment info
            $update = $conn->prepare("UPDATE bookings SET booking_status = ?, payment_link = ?, payment_id = ? WHERE id = ?");
            $update->bind_param("sssi", $new_status, $payment_link, $payment_id, $booking_id);
            $is_success = $update->execute();

            if ($is_success) {
                // 4. Set notification for CUSTOMER with payment link
                $notification_message = "Your booking is approved! Please complete your payment to confirm.";
                $notification_link = $payment_link; // Send customer DIRECTLY to PayMongo
            } else {
                 $_SESSION['toast_message'] = "DB Error: " . $update->error;
                 $_SESSION['toast_type'] = "danger";
            }

        } else {
            // PayMongo API failed
            $_SESSION['toast_message'] = "Booking approved, but failed to create payment link: " . ($paymongo_data['error'] ?? 'Unknown PayMongo Error');
            $_SESSION['toast_type'] = "danger";
            // We don't set $is_success, so it will fail gracefully
        }

    } else {
        // --- ORIGINAL Logic for 'declined' or 'completed' ---
        $update = $conn->prepare("UPDATE bookings SET booking_status = ? WHERE id = ?");
        $update->bind_param("si", $new_status, $booking_id);
        $is_success = $update->execute();

        if ($is_success) {
            $notification_message = "Your booking request has been " . strtoupper($new_status) . " by " . htmlspecialchars($provider_name) . ".";
            $notification_link = "dashboard.php?action=view_booking&booking_id=" . $booking_id;
        } else {
            $_SESSION['toast_message'] = "Failed to update status: " . $update->error;
            $_SESSION['toast_type'] = "danger";
        }
    }

    // --- 5. MODIFIED: Centralized Notification & Redirect ---
    if ($is_success) {
        // Send notification (if message is set)
        if (!empty($notification_message)) {
            $notification_user_id = $booking['customer_id'] ?? 0;
            $notification_role = 0; // 0 = customer

            $notify = $conn->prepare("INSERT INTO notifications (user_id, message, link, role) VALUES (?, ?, ?, ?)");
            $notify->bind_param("issi", $notification_user_id, $notification_message, $notification_link, $notification_role);
            $notify->execute();
            $notify->close();
        }

        $_SESSION['toast_message'] = "Booking status updated to " . strtoupper($new_status) . "!";
        $_SESSION['toast_type'] = "success";

    } // If $is_success is false, a toast message should have already been set.

    if ($update) {
        $update->close();
    }
    
    // Redirect back to this page to show the toast and updated status
    header("Location: dashboard.php?action=provider_booking_details&booking_id=" . $booking_id);
    exit;
}

$conn->close();

// Helper function to get status badge class
function get_status_class($status) {
    switch ($status) {
        case 'approved': return 'status-approved bi-check-circle';
        case 'declined': return 'status-declined bi-x-circle';
        case 'completed': return 'status-completed bi-check-all';
        default: return 'status-pending bi-clock';
    }
}

// Helper function to get initials for avatar
function get_initials($name) {
    $parts = explode(' ', trim($name));
    $initials = '';
    foreach ($parts as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }
    return substr($initials, 0, 2);
}

// Get the customer initials
$customer_initials = get_initials($booking['customer_name']);

// Get status badge info
$status_info = get_status_class($booking['booking_status']);
list($status_class, $status_icon) = explode(' ', $status_info);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking #<?php echo $booking_id; ?> Details | Provider Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee; /* A strong blue for focus */
            --primary-dark: #3a50d6;
            --secondary: #7209b7;
            --success: #06d6a0;
            --warning: #ffd166;
            --danger: #ef476f;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --radius: 14px;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        body {
            background: linear-gradient(120deg, #eef2ff 0%, #f5f7ff 100%);
            font-family: "Inter", "Segoe UI", sans-serif; /* Prefer Inter if available */
            color: var(--dark);
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
        }

        .card {
            border: none;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: 0.3s ease;
        }

        .card-header {
            background: var(--light);
            color: var(--dark);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1rem;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--light-gray);
        }

        .card-header i {
            color: var(--primary);
            font-size: 1.2rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .info-item {
            background: var(--light);
            border-radius: 10px;
            padding: 1rem;
            border-left: 5px solid var(--primary);
        }

        .info-label {
            color: var(--gray);
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 500;
            margin-bottom: 0.3rem;
        }

        .info-value {
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .info-value i {
            margin-right: 8px;
            color: var(--primary-dark);
        }
        
        .info-value.text-wrap {
            white-space: pre-wrap; 
            word-break: break-word; 
            line-height: 1.6;
        }

        /* Status Badges */
        .status-badge {
            font-size: 0.9rem;
            padding: 8px 14px;
            border-radius: 50px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            letter-spacing: 0.5px;
        }

        .status-approved { background-color: rgba(6,214,160,0.15); color: #06a17c; border: 1px solid rgba(6,214,160,0.5); }
        .status-pending { background-color: rgba(255,209,102,0.15); color: #d4a74a; border: 1px solid rgba(255,209,102,0.5); }
        .status-declined { background-color: rgba(239,71,111,0.15); color: #d43a5e; border: 1px solid rgba(239,71,111,0.5); }
        .status-completed { background-color: rgba(67,97,238,0.15); color: var(--primary); border: 1px solid rgba(67,97,238,0.5); }


        /* Customer Avatar */
        .customer-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--primary-dark), #4cc9f0);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.8rem;
            margin-right: 15px;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.4);
        }

        /* Status Update Actions */
        .action-btns {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            padding: 1rem 0;
            border-radius: 10px;
        }

        .action-btn {
            flex: 1;
            min-width: 150px;
            border-radius: 12px;
            font-weight: 600;
            padding: 0.8rem 1.2rem;
            border: 1px solid transparent;
            transition: all 0.2s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-approve { background-color: rgba(6,214,160,0.1); color: #06a17c; border-color: rgba(6,214,160,0.4); }
        .btn-decline { background-color: rgba(239,71,111,0.1); color: #d43a5e; border-color: rgba(239,71,111,0.4); }
        .btn-complete { background-color: rgba(67,97,238,0.1); color: var(--primary); border-color: rgba(67,97,238,0.4); }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-approve:hover, .btn-approve.active { background-color: #06d6a0; color: #fff; border-color: #06d6a0; }
        .btn-decline:hover, .btn-decline.active { background-color: #ef476f; color: #fff; border-color: #ef476f; }
        .btn-complete:hover, .btn-complete.active { background-color: var(--primary); color: #fff; border-color: var(--primary); }

        .btn-disabled {
            opacity: 0.6;
            cursor: not-allowed;
            pointer-events: none;
        }

        .hidden {
            display: none !important;
        }

        .form-select.shadow-sm {
            border-radius: 10px;
        }

        .toast-container {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            /* --- MODIFIED: Ensure toasts appear above modals --- */
            z-index: 1100; 
        }

        @media (max-width: 768px) {
            .action-btns { flex-direction: column; gap: 0.75rem; }
            .info-item { border-left-width: 3px; }
        }
    </style>
</head>
<body>

<div class="toast-container" id="toast-container-main">
    <?php if (isset($_SESSION['toast_message'])): ?>
        <div class="toast align-items-center text-white bg-<?php echo $_SESSION['toast_type']; ?> border-0 show" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-<?php echo ($_SESSION['toast_type'] == 'success') ? 'check-circle-fill' : 'exclamation-triangle-fill'; ?> me-2"></i>
                    <?php echo $_SESSION['toast_message']; ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
        <?php unset($_SESSION['toast_message']); unset($_SESSION['toast_type']); ?>
    <?php endif; ?>
    </div>

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold text-primary"><i class="bi bi-arrow-left-circle-fill me-2" onclick="history.back()" style="cursor: pointer;"></i> Booking #<?php echo $booking_id; ?></h1>
        <span class="status-badge <?php echo $status_class; ?>">
            <i class="<?php echo $status_icon; ?>"></i>
            <?php echo strtoupper($booking['booking_status']); ?>
        </span>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-calendar-check"></i> **Booking Information**</div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item"><span class="info-label">Service Provider</span><span class="info-value"><i class="bi bi-building"></i><?php echo htmlspecialchars($provider_name); ?></span></div>
                        <div class="info-item"><span class="info-label">Date Range</span><span class="info-value"><i class="bi bi-calendar-range"></i><?php echo date('M j, Y', strtotime($booking['booking_date_from'])) . " - " . date('M j, Y', strtotime($booking['booking_date_to'])); ?></span></div>
                        <div class="info-item"><span class="info-label">Time Slot</span><span class="info-value"><i class="bi bi-clock"></i><?php echo htmlspecialchars($booking['booking_time_from']) . " - " . htmlspecialchars($booking['booking_time_to']); ?></span></div>
                        
                        <div class="info-item"><span class="info-label">Total Price</span><span class="info-value"><i class="bi bi-cash-coin"></i>PHP <?php echo number_format($booking['total_price'] ?? 0, 2); ?></span></div>
                        
                        <div class="info-item col-span-full"><span class="info-label">Notes / Special Request</span><span class="info-value text-wrap fst-italic"><i class="bi bi-chat-left-text"></i><?php echo !empty($booking['special_request']) ? htmlspecialchars($booking['special_request']) : 'No special requests.'; ?></span></div>
                        
                        <?php if (!empty($booking['payment_link'])): ?>
                            <div class="info-item col-span-full" style="border-left-color: var(--success);">
                                <span class="info-label">Payment Link</span>
                                <span class="info-value">
                                    <i class="bi bi-credit-card" style="color: var(--success);"></i>
                                    <a href="<?php echo htmlspecialchars($booking['payment_link']); ?>" target="_blank" class="text-success text-decoration-none fw-bold">
                                        Click to View PayMongo Checkout
                                    </a>
                                </span>
                            </div>
                        <?php endif; ?>

                        </div>
                    <p class="text-muted small mt-4 text-end">Request created on: <?php echo date('F j, Y, h:i A', strtotime($booking['created_at'])); ?></p>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><i class="bi bi-repeat"></i> **Update Booking Status**</div>
                <div class="card-body">
                    <form method="POST" id="statusUpdateForm">
                        <input type="hidden" name="booking_status" id="booking_status" value="<?php echo htmlspecialchars($booking['booking_status']); ?>">

                        <div class="action-btns mb-4">
                            <button type="submit" name="update_status" class="action-btn btn-approve <?php echo ($booking['booking_status'] == 'approved' || $booking['booking_status'] == 'completed' || $booking['booking_status'] == 'declined') ? 'btn-disabled' : ''; ?>" onclick="return setStatusAndValidate('approved');" title="Confirm and generate payment link">
                                <i class="bi bi-check-circle"></i> Approve & Send Payment
                            </button>
                            <button type="submit" name="update_status" class="action-btn btn-decline <?php echo ($booking['booking_status'] == 'declined' || $booking['booking_status'] == 'completed') ? 'btn-disabled' : ''; ?>" onclick="return setStatusAndValidate('declined');" title="Reject the customer's request">
                                <i class="bi bi-x-circle"></i> Decline
                            </button>
                            <button type="submit" name="update_status" class="action-btn btn-complete <?php echo ($booking['booking_status'] == 'completed' || $booking['booking_status'] == 'pending') ? 'btn-disabled' : ''; ?>" onclick="return setStatusAndValidate('completed');" title="Mark the service as finished (Only works if approved)">
                                <i class="bi bi-check-all"></i> Mark as Completed
                            </button>
                        </div>
                        
                        <div class="alert alert-info py-2" role="alert">
                            Current Status: **<?php echo strtoupper($booking['booking_status']); ?>**.
                            <?php if ($booking['booking_status'] == 'pending'): ?>
                                Click "Approve" to generate a PayMongo link for the customer.
                            <?php elseif ($booking['booking_status'] == 'approved'): ?>
                                Waiting for customer payment. You can mark as "Completed" after the service.
                            <?php elseif ($booking['booking_status'] == 'declined'): ?>
                                This booking has been declined.
                            <?php elseif ($booking['booking_status'] == 'completed'): ?>
                                This booking is complete.
                            <?php endif; ?>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 1.5rem;">
                <div class="card-header"><i class="bi bi-person-fill"></i> **Customer Details**</div>
                <div class="card-body text-center">
                    <div class="customer-avatar mx-auto mb-3"><?php echo $customer_initials; ?></div>
                    <h4 class="mb-1 text-primary"><?php echo htmlspecialchars($booking['customer_name']); ?></h4>
                    <p class="text-muted small">Customer ID: <?php echo $booking['customer_id'] ?? 'N/A'; ?></p>

                    <hr class="my-3">

                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-envelope-fill me-2 text-primary"></i>
                        <a href="mailto:<?php echo htmlspecialchars($booking['customer_email']); ?>" class="text-decoration-none"><?php echo htmlspecialchars($booking['customer_email']); ?></a>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-telephone-fill me-2 text-primary"></i>
                        <a href="tel:<?php echo htmlspecialchars($booking['customer_phone']); ?>" class="text-decoration-none"><?php echo htmlspecialchars($booking['customer_phone']); ?></a>
                    </div>
                    <a href="#" class="btn btn-sm btn-outline-primary mt-3 w-100"><i class="bi bi-person-lines-fill me-1"></i> View Profile</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmationModalLabel">Confirm Action</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="confirmationModalBody">
        Are you sure you want to proceed?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="confirmActionButton">Yes, Confirm</button>
      </div>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // --- NEW: Modal instance variable ---
    let confirmationModalInstance = null;

    /**
     * --- NEW ---
     * Displays a dynamic Bootstrap toast notification for JS-based alerts.
     * @param {string} message The message to display.
     * @param {string} type 'success', 'danger', 'warning', 'info'
     */
    function showJsToast(message, type = 'danger') {
        const container = document.getElementById('toast-container-main');
        if (!container) return;

        const iconMap = {
            'success': 'check-circle-fill',
            'danger': 'exclamation-triangle-fill',
            'warning': 'exclamation-triangle-fill',
            'info': 'info-circle-fill'
        };
        const icon = iconMap[type] || 'info-circle-fill';

        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-white bg-${type} border-0 show`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        toastEl.setAttribute('data-bs-delay', '5000');

        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-${icon} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        container.appendChild(toastEl);
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
        // Remove the toast from DOM after it's hidden
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }

    /**
     * --- MODIFIED ---
     * Validates the status change and shows a confirmation MODAL instead of a simple confirm().
     * Shows a TOAST for validation errors instead of alert().
     * @param {string} status The new status to set ('approved', 'declined', 'completed').
     * @returns {boolean} ALWAYS returns false to prevent default form submission.
     */
    function setStatusAndValidate(status) {
        const currentStatus = '<?php echo $booking['booking_status']; ?>'; // Get status from PHP
        
        // Modal elements
        const modalTitle = document.getElementById('confirmationModalLabel');
        const modalBody = document.getElementById('confirmationModalBody');
        const confirmBtn = document.getElementById('confirmActionButton');

        let title = 'Confirm Action';
        let body = 'Are you sure?';
        let btnClass = 'btn-primary';
        let btnText = 'Confirm';

        // --- Validation logic (now uses showJsToast) ---
        if (status === 'approved') {
            if (currentStatus !== 'pending') {
                showJsToast("You can only approve a 'PENDING' booking.", 'warning');
                return false;
            }
            title = 'Confirm Approval';
            body = 'Are you sure you want to <strong>APPROVE</strong> this booking and generate a payment link?';
            btnClass = 'btn-success';
            btnText = 'Yes, Approve';
        } 
        else if (status === 'declined') {
            if (currentStatus === 'completed' || currentStatus === 'declined') {
                showJsToast("This booking is already " + currentStatus + ".", 'warning');
                return false;
            }
            title = 'Confirm Decline';
            body = 'Are you sure you want to <strong>DECLINE</strong> this booking? This action cannot be easily undone.';
            btnClass = 'btn-danger';
            btnText = 'Yes, Decline';
        } 
        else if (status === 'completed') {
            if (currentStatus === 'pending') {
                showJsToast("You must 'Approve' a booking before you can complete it.", 'warning');
                return false;
            }
            if (currentStatus === 'completed') {
                showJsToast("This booking is already marked as completed.", 'info');
                return false;
            }
            title = 'Confirm Completion';
            body = 'Are you sure you want to mark this booking as <strong>COMPLETED</strong>?';
            btnClass = 'btn-primary';
            btnText = 'Yes, Mark as Completed';
        }

        // --- Populate and show modal ---
        if (modalTitle) modalTitle.innerHTML = title;
        if (modalBody) modalBody.innerHTML = body;
        if (confirmBtn) {
            confirmBtn.innerHTML = btnText;
            confirmBtn.className = `btn ${btnClass}`; // Reset classes
            confirmBtn.dataset.status = status; // Store status
        }
        
        if (confirmationModalInstance) {
            confirmationModalInstance.show();
        }

        // ALWAYS return false to prevent the form from submitting via the button click
        return false;
    }

    // --- MODIFIED: Combined DOMContentLoaded listener ---
    document.addEventListener('DOMContentLoaded', function () {
        
        // 1. Initialize PHP Session Toasts (from Original Code)
        // We select toasts directly inside the main container that were rendered by PHP
        const toastElList = [].slice.call(document.querySelectorAll('#toast-container-main .toast'));
        toastElList.map(function (toastEl) {
            const toast = new bootstrap.Toast(toastEl);
            toast.show();
        });

        // 2. Initialize Modal Instance
        const modalEl = document.getElementById('confirmationModal');
        if (modalEl) {
            confirmationModalInstance = new bootstrap.Modal(modalEl);

            // 3. Add Modal "hidden" listener to reset confirm button
            modalEl.addEventListener('hidden.bs.modal', function () {
                const confirmBtn = document.getElementById('confirmActionButton');
                if (confirmBtn) {
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = 'Confirm'; // Reset text
                }
            });
        }

        // 4. Add Modal Confirm Button "click" listener
        const confirmBtn = document.getElementById('confirmActionButton');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function() {
                const status = this.dataset.status;
                if (status) {
                    // Set loading state
                    this.disabled = true;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';

                    // Set hidden input
                    document.getElementById('booking_status').value = status;
                    
                    // Hide modal (form submission will take over)
                    if (confirmationModalInstance) {
                        confirmationModalInstance.hide();
                    }
                    
                    // Submit the form
                    document.getElementById('statusUpdateForm').submit();
                }
            });
        }
    });
</script>
</body>
</html>