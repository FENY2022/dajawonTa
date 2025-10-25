<?php
ob_start(); // Start output buffering to prevent header issues
session_start();
require '../db.php'; // Assuming your db connection file is one level up

// --- STEP 1 & 3: PayMongo UAT (Test) Configuration ---
define('PAYMONGO_SECRET_KEY', 'sk_test_96buPr5S5wCEGUSjiEizVgMx'); // Updated with provided test secret key
define('SITE_BASE_URL', 'http://localhost/dajawonta'); // For local testing; use ngrok for public URL in production

/**
 * --- PayMongo Helper Function ---
 * Creates a PayMongo Checkout Session and returns the checkout URL and ID.
 *
 * @param array $booking The booking details array. Must contain:
 * id, total_price, customer_name, customer_email, customer_phone, booking_date_from
 * @return array ['checkout_url' => string, 'checkout_id' => string] on success,
 * ['error' => string] on failure.
 */
function create_paymongo_checkout_session($booking) {
    error_log("Creating PayMongo checkout session for booking ID: " . $booking['id']);
    
    // --- START FIX #1 & #2: Validate Price and Customer Details ---

    // 1. Validate price and convert to centavos
    if (!isset($booking['total_price']) || !is_numeric($booking['total_price'])) {
        error_log("Invalid or missing total_price: " . print_r($booking, true));
        return ['error' => 'Invalid or missing total_price in booking.'];
    }
    $amount_in_centavos = (int) ($booking['total_price'] * 100);

    // FIX #1: PayMongo requires a minimum of 10000 centavos (PHP 100.00)
    if ($amount_in_centavos < 10000) {
        error_log("Total price invalid: $amount_in_centavos. Must be at least 10000 centavos (PHP 100.00).");
        return ['error' => 'Total price must be at least PHP 100.00.'];
    }

    // FIX #2: Validate customer details
    $customer_name = $booking['customer_name'] ?? '';
    $customer_email = $booking['customer_email'] ?? '';
    $customer_phone = $booking['customer_phone'] ?? '';

    if (empty($customer_name)) {
        error_log("Customer name is missing for booking ID: " . $booking['id']);
        return ['error' => 'Customer name is missing.'];
    }

    if (empty($customer_email) || !filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        error_log("Customer email is missing or invalid: $customer_email");
        return ['error' => 'Customer email is missing or invalid.'];
    }

    // Basic phone validation (must be at least 10 digits, e.g., 09171234567 or +639171234567)
    $cleaned_phone = preg_replace('/[^0-9]/', '', $customer_phone); // Remove +, spaces, etc.
    if (strlen($cleaned_phone) < 10) {
        error_log("Customer phone number is missing or invalid: $customer_phone");
        return ['error' => 'Customer phone number is missing or invalid (must be 10+ digits).'];
    }
    // --- END FIX #1 & #2 ---


    // 3. Define the payload for PayMongo API
    $payload = [
        'data' => [
            'attributes' => [
                'billing' => [
                    'name'  => $customer_name,  // Use validated variable
                    'email' => $customer_email, // Use validated variable
                    'phone' => $customer_phone  // Use original (PayMongo is flexible with format if valid)
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
                'success_url' => SITE_BASE_URL . 'payment_success.php?booking_id=' . $booking['id'],
                'cancel_url'  => SITE_BASE_URL . 'payment_cancel.php?booking_id=' . $booking['id'],
                'reference_number' => 'BOOKING_' . $booking['id']
            ]
        ]
    ];

    error_log("PayMongo Payload: " . json_encode($payload));

    // 4. Initialize cURL request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.paymongo.com/v1/checkout_sessions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_USERPWD, PAYMONGO_SECRET_KEY . ':');

    // --- START FIX #3: SSL Fix for Localhost ---
    // This disables SSL certificate verification.
    // ONLY use this on your local development machine (localhost).
    // REMOVE or COMMENT OUT this line when you go to production (live server)!
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // --- END FIX #3 ---

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
    ]);

    $response_body = curl_exec($ch);
    if ($response_body === false) {
        $error = curl_error($ch);
        error_log("PayMongo CURL Error: " . $error);
        curl_close($ch);
        // Display a more user-friendly cURL error
        if (strpos($error, 'SSL') !== false) {
             return ['error' => 'CURL SSL Error. If on localhost, ensure CURLOPT_SSL_VERIFYPEER is false. Error: ' . $error];
        }
        return ['error' => 'CURL error: ' . $error];
    }
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $response_data = json_decode($response_body, true);
    error_log("PayMongo Response (HTTP $http_code): " . $response_body);

    // 5. Handle response
    if ($http_code === 200 && isset($response_data['data']['id'])) {
        return [
            'checkout_id'  => $response_data['data']['id'],
            'checkout_url' => $response_data['data']['attributes']['checkout_url']
        ];
    } else {
        $error_message = 'Failed to create payment link.';
        if (isset($response_data['errors'][0]['detail'])) {
            $error_message .= ' PayMongo said: ' . $response_data['errors'][0]['detail'];
        }
        error_log("PayMongo API Error: " . $error_message);
        return ['error' => $error_message];
    }
}

// --- 1. Validate booking ID ---
error_log("Processing booking_id: " . ($_GET['booking_id'] ?? 'Not set'));
if (!isset($_GET['booking_id']) || empty($_GET['booking_id'])) {
    echo "<!DOCTYPE html><html lang='en'><head><title>Error</title><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'></head><body><div class='container mt-5'><div class='alert alert-danger'>Invalid booking ID.</div><a href='dashboard.php' class='btn btn-primary'>Go to Dashboard</a></div></body></html>";
    exit;
}

$booking_id = intval($_GET['booking_id']);

// --- 2. Fetch booking details ---
$query = "SELECT * FROM bookings WHERE id = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    error_log("DB Prepare Error: " . $conn->error);
    echo "<!DOCTYPE html><html lang='en'><head><title>Error</title><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'></head><body><div class='container mt-5'><div class='alert alert-danger'>Database error.</div><a href='dashboard.php' class='btn btn-primary'>Go to Dashboard</a></div></body></html>";
    exit;
}
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    error_log("Booking not found for ID: $booking_id");
    echo "<!DOCTYPE html><html lang='en'><head><title>Error</title><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'></head><body><div class='container mt-5'><div class='alert alert-danger'>Booking not found.</div><a href='dashboard.php' class='btn btn-primary'>Go to Dashboard</a></div></body></html>";
    exit;
}

$booking = $result->fetch_assoc();
$stmt->close();
error_log("Booking data: " . print_r($booking, true));
$original_status = $booking['booking_status']; // Store original status for comparison

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

// --- 4. Handle Status Update (including PayMongo integration) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    error_log("POST request received for status update");
    $new_status = $_POST['booking_status'];

    // Input validation for status
    $valid_statuses = ['approved', 'declined', 'completed'];
    if (!in_array($new_status, $valid_statuses)) {
        error_log("Invalid status: $new_status");
        $_SESSION['toast_message'] = "Invalid status selected.";
        $_SESSION['toast_type'] = "danger";
        header("Location: dashboard.php?action=provider_booking_details&booking_id=" . $booking_id);
        exit;
    }

    $is_success = false;
    $notification_message = "";
    $notification_link = "";
    $update = null;

    if ($new_status == 'approved') {
        error_log("Processing approval for booking ID: $booking_id");
        
        // ** ENHANCEMENT: Removed check that blocked re-generating a link.
        // We now ALLOW re-generating a link for an 'approved' booking.

        // Create PayMongo Checkout Session
        $paymongo_data = create_paymongo_checkout_session($booking);

        if (isset($paymongo_data['checkout_url'])) {
            $payment_link = $paymongo_data['checkout_url'];
            $payment_id = $paymongo_data['checkout_id'];
            $is_approve = 1; // ** ENHANCEMENT: Set is_approve flag

            // Update database with new status, payment info, and approve flag
            $update = $conn->prepare("UPDATE bookings SET booking_status = ?, payment_link = ?, payment_id = ?, is_approve = ? WHERE id = ?");
            $update->bind_param("sssii", $new_status, $payment_link, $payment_id, $is_approve, $booking_id);
            $is_success = $update->execute();

            if ($is_success) {
                error_log("Database updated successfully: status=$new_status, payment_link=$payment_link, payment_id=$payment_id");
                
                // ** ENHANCEMENT: Customize notification based on action
                if ($original_status == 'approved') {
                    $notification_message = "Your payment link has been re-generated. Please complete your payment to confirm.";
                } else {
                    $notification_message = "Your booking is approved! Please complete your payment to confirm.";
                }
                $notification_link = $payment_link;

            } else {
                error_log("DB Update Error: " . $update->error);
                $_SESSION['toast_message'] = "DB Error: " . $update->error;
                $_SESSION['toast_type'] = "danger";
            }
        } else {
            error_log("PayMongo failed: " . ($paymongo_data['error'] ?? 'Unknown error'));
            $_SESSION['toast_message'] = "Failed to create payment link: " . ($paymongo_data['error'] ?? 'Unknown PayMongo Error');
            $_SESSION['toast_type'] = "danger";
        }
    } else {
        // Logic for 'declined' or 'completed'
        // ** ENHANCEMENT: Set is_approve flag based on status
        // 1 for completed, 0 for declined.
        $is_approve = ($new_status == 'completed') ? 1 : 0; 

        $update = $conn->prepare("UPDATE bookings SET booking_status = ?, is_approve = ? WHERE id = ?");
        $update->bind_param("sii", $new_status, $is_approve, $booking_id);
        $is_success = $update->execute();

        if ($is_success) {
            error_log("Status updated to $new_status for booking ID: $booking_id");
            $notification_message = "Your booking request has been " . strtoupper($new_status) . " by " . htmlspecialchars($provider_name) . ".";
            $notification_link = "dashboard.php?action=view_booking&booking_id=" . $booking_id;
        } else {
            error_log("DB Update Error: " . $update->error);
            $_SESSION['toast_message'] = "Failed to update status: " . $update->error;
            $_SESSION['toast_type'] = "danger";
        }
    }

    // Centralized Notification & Redirect
    if ($is_success) {
        $notification_sent = true;
        if (!empty($notification_message)) {
            $notification_user_id = $booking['customer_id'] ?? 0;
            $notification_role = 0; // 0 = customer

            if ($notification_user_id == 0) {
                error_log("Notification Error: customer_id is 0 or missing for booking_id: " . $booking_id);
                $notification_sent = false;
            } else {
                $notify = $conn->prepare("INSERT INTO notifications (user_id, message, link, role) VALUES (?, ?, ?, ?)");
                $notify->bind_param("issi", $notification_user_id, $notification_message, $notification_link, $notification_role);
                
                if (!$notify->execute()) {
                    error_log("Notification Insert Error: " . $notify->error);
                    $notification_sent = false;
                }
                $notify->close();
            }
        }

        // ** ENHANCEMENT: Customized toast messages
        if ($new_status == 'approved') {
            $toast_msg = ($original_status == 'approved') ? "Payment link re-generated and notification sent!" : "Booking approved and notification sent!";
            $_SESSION['toast_message'] = $notification_sent ? $toast_msg : "Action complete, but FAILED to send notification.";
            $_SESSION['toast_type'] = $notification_sent ? "success" : "warning";
        } else {
            $_SESSION['toast_message'] = $notification_sent ? "Booking status updated to " . strtoupper($new_status) . "!" : "Status updated, but FAILED to send notification.";
            $_SESSION['toast_type'] = $notification_sent ? "success" : "warning";
        }
    }

    if ($update) {
        $update->close();
    }
    
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

// ** ENHANCEMENT: Dynamic text for approve button
$approve_btn_text = ($booking['booking_status'] == 'approved') ? 'Re-send Payment Link' : 'Approve & Send Payment';
$approve_btn_icon = ($booking['booking_status'] == 'approved') ? 'bi-arrow-clockwise' : 'bi-check-circle';
$approve_btn_title = ($booking['booking_status'] == 'approved') ? 'Generate a new payment link and re-notify customer' : 'Confirm and generate payment link';
$approve_btn_class = ($booking['booking_status'] == 'approved') ? 'btn-resend' : 'btn-approve'; // Using a new class for styling re-send

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
            --primary: #4361ee;
            --primary-dark: #3a50d6;
            --secondary: #7209b7;
            --success: #06d6a0;
            --warning: #ffd166;
            --warning-dark: #d4a74a;
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
            font-family: "Inter", "Segoe UI", sans-serif;
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
        /* ** ENHANCEMENT: Added style for re-send button */
        .btn-resend { background-color: rgba(255,209,102,0.15); color: var(--warning-dark); border-color: rgba(255,209,102,0.5); }
        .btn-decline { background-color: rgba(239,71,111,0.1); color: #d43a5e; border-color: rgba(239,71,111,0.4); }
        .btn-complete { background-color: rgba(67,97,238,0.1); color: var(--primary); border-color: rgba(67,97,238,0.4); }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-approve:hover, .btn-approve.active { background-color: #06d6a0; color: #fff; border-color: #06d6a0; }
        .btn-resend:hover, .btn-resend.active { background-color: var(--warning); color: var(--dark); border-color: var(--warning); }
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
                <div class="card-header"><i class="bi bi-calendar-check"></i> <strong>Booking Information</strong></div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item"><span class="info-label">Service Provider</span><span class="info-value"><i class="bi bi-building"></i><?php echo htmlspecialchars($provider_name); ?></span></div>
                        <div class="info-item"><span class="info-label">Date Range</span><span class="info-value"><i class="bi bi-calendar-range"></i><?php echo date('M j, Y', strtotime($booking['booking_date_from'])) . " - " . date('M j, Y', strtotime($booking['booking_date_to'])); ?></span></div>
                        <div class="info-item"><span class="info-label">Time Slot</span><span class="info-value"><i class="bi bi-clock"></i><?php echo htmlspecialchars($booking['booking_time_from']) . " - " . htmlspecialchars($booking['booking_time_to']); ?></span></div>
                        <div class="info-item"><span class="info-label">Total Price</span><span class="info-value"><i class="bi bi-cash-coin"></i>PHP <?php echo number_format($booking['total_price'] ?? 0, 2); ?></span></div>
                        <div class="info-item col-span-full"><span class="info-label">Notes / Special Request</span><span class="info-value text-wrap fst-italic"><i class="bi bi-chat-left-text"></i><?php echo !empty($booking['special_request']) ? htmlspecialchars($booking['special_request']) : 'No special requests.'; ?></span></div>
                        <?php if (!empty($booking['payment_link'])): ?>
                            <div class="info-item col-span-full" style="border-left-color: var(--success);">
                                <span class="info-label">Payment Link (PayMongo)</span>
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
                <div class="card-header"><i class="bi bi-repeat"></i> <strong>Update Booking Status</strong></div>
                <div class="card-body">
                    <form method="POST" id="statusUpdateForm">
                        <input type="hidden" name="booking_status" id="booking_status" value="<?php echo htmlspecialchars($booking['booking_status']); ?>">

                        <div class="action-btns mb-4">
                            <button type="submit" name="update_status" 
                                    class="action-btn <?php echo $approve_btn_class; ?> <?php echo ($booking['booking_status'] == 'completed' || $booking['booking_status'] == 'declined') ? 'btn-disabled' : ''; ?>" 
                                    onclick="return setStatusAndValidate('approved');" 
                                    title="<?php echo $approve_btn_title; ?>">
                                <i class="bi <?php echo $approve_btn_icon; ?>"></i> <?php echo $approve_btn_text; ?>
                            </button>
                            
                            <button type="submit" name="update_status" 
                                    class="action-btn btn-decline <?php echo ($booking['booking_status'] == 'declined' || $booking['booking_status'] == 'completed') ? 'btn-disabled' : ''; ?>" 
                                    onclick="return setStatusAndValidate('declined');" 
                                    title="Reject the customer's request">
                                <i class="bi bi-x-circle"></i> Decline
                            </button>
                            
                            <button type="submit" name="update_status" 
                                    class="action-btn btn-complete <?php echo ($booking['booking_status'] == 'completed' || $booking['booking_status'] == 'pending' || $booking['booking_status'] == 'declined') ? 'btn-disabled' : ''; ?>" 
                                    onclick="return setStatusAndValidate('completed');" 
                                    title="Mark the service as finished (Only works if approved)">
                                <i class="bi bi-check-all"></i> Mark as Completed
                            </button>
                        </div>
                        
                        <div class="alert alert-info py-2" role="alert">
                            Current Status: <strong><?php echo strtoupper($booking['booking_status']); ?></strong>.
                            <?php if ($booking['booking_status'] == 'pending'): ?>
                                Click "Approve" to generate a PayMongo link for the customer. (Min. PHP 100.00 required)
                            <?php elseif ($booking['booking_status'] == 'approved'): ?>
                                Waiting for customer payment. You can <strong>re-send the payment link</strong> or mark as "Completed".
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
                <div class="card-header"><i class="bi bi-person-fill"></i> <strong>Customer Details</strong></div>
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
                <button type="button" class="btn btn-primary" id="confirmActionButton" onclick="confirmStatusChange()">Yes, Confirm</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
/**
 * Displays a dynamic Bootstrap toast notification for JS-based alerts.
 * @param {string} message The message to display.
 * @param {string} type 'success', 'danger', 'warning', 'info'
 */
function showJsToast(message, type = 'danger') {
    console.log(`Showing toast: ${message} (${type})`);
    const container = document.getElementById('toast-container-main');
    if (!container) {
        console.error('Toast container not found');
        return;
    }

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
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}

let confirmationModalInstance = null;

/**
 * Validates the status change and shows confirmation modal.
 * Uses toasts for validation errors.
 * @param {string} status The new status to set ('approved', 'declined', 'completed').
 * @returns {boolean} Always returns false to stop the default form submit.
 */
function setStatusAndValidate(status) {
    console.log('setStatusAndValidate called with status:', status);
    const currentStatus = '<?php echo $booking['booking_status']; ?>';
    const currentPrice = <?php echo $booking['total_price'] ?? 0; ?>; // Get price for validation

    const modalTitle = document.getElementById('confirmationModalLabel');
    const modalBody = document.getElementById('confirmationModalBody');
    const confirmBtn = document.getElementById('confirmActionButton');

    let title = 'Confirm Action';
    let body = 'Are you sure?';
    let btnClass = 'btn-primary';
    let btnText = 'Confirm';

    // ** ENHANCEMENT: Updated logic for 'approved' status
    if (status === 'approved') {
        // --- START JS VALIDATION ---
        // Mirror the PHP validation for a better user experience
        if (currentPrice < 100) {
            showJsToast("Cannot approve: Total price must be at least PHP 100.00.", 'warning');
            return false;
        }
        // --- END JS VALIDATION ---

        if (currentStatus !== 'pending' && currentStatus !== 'approved') {
            showJsToast("You can only approve a 'PENDING' booking or re-send the link for an 'APPROVED' one.", 'warning');
            return false;
        }

        if (currentStatus === 'pending') {
            title = 'Confirm Approval';
            body = 'Are you sure you want to <strong>APPROVE</strong> this booking and generate a PayMongo payment link?';
            btnClass = 'btn-success';
            btnText = 'Yes, Approve';
        } else { // currentStatus must be 'approved'
            title = 'Re-send Payment Link';
            body = 'Are you sure you want to generate a <strong>NEW</strong> payment link? The customer will be notified with the new link.';
            btnClass = 'btn-warning'; // Use warning for a re-send
            btnText = 'Yes, Re-send Link';
        }

    } else if (status === 'declined') {
        if (currentStatus === 'completed' || currentStatus === 'declined') {
            showJsToast("This booking is already " + currentStatus + ".", 'warning');
            return false;
        }
        title = 'Confirm Decline';
        body = 'Are you sure you want to <strong>DECLINE</strong> this booking? This action cannot be undone.';
        btnClass = 'btn-danger';
        btnText = 'Yes, Decline';

    } else if (status === 'completed') {
        if (currentStatus === 'pending' || currentStatus === 'declined') {
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

    if (modalTitle) modalTitle.innerHTML = title;
    if (modalBody) modalBody.innerHTML = body;
    if (confirmBtn) {
        confirmBtn.innerHTML = btnText;
        confirmBtn.className = `btn ${btnClass}`;
        confirmBtn.dataset.status = status;
        confirmBtn.disabled = false;
    }

    if (confirmationModalInstance) {
        console.log('Showing confirmation modal');
        confirmationModalInstance.show();
    } else {
        console.error('Confirmation modal instance not initialized');
        showJsToast('Modal not initialized.', 'danger');
    }
    return false;
}

/**
 * Triggered when the user confirms the modal action.
 * Handles form submission after confirmation.
 */
function confirmStatusChange() {
    console.log('confirmStatusChange called');
    const confirmBtn = document.getElementById('confirmActionButton');
    const status = confirmBtn.dataset.status;
    if (!status) {
        console.error('No status selected');
        showJsToast("No status selected.", "warning");
        return;
    }

    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';

    const form = document.getElementById('statusUpdateForm');
    const statusInput = document.getElementById('booking_status');
    if (!form || !statusInput) {
        console.error('Form or status input not found');
        showJsToast('Form or status input not found.', 'danger');
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = 'Yes, Confirm';
        return;
    }

    statusInput.value = status;
    console.log('Submitting form with status:', status);
    form.submit();
}

document.addEventListener('DOMContentLoaded', function () {
    console.log('✅ JS loaded successfully');
    const modalEl = document.getElementById('confirmationModal');
    if (modalEl) {
        confirmationModalInstance = new bootstrap.Modal(modalEl);
        console.log('Modal initialized');
    } else {
        console.error('Confirmation modal element not found');
    }

    const toastElList = [].slice.call(document.querySelectorAll('#toast-container-main .toast'));
    toastElList.forEach(toastEl => {
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
    });

    const form = document.getElementById('statusUpdateForm');
    if (form) {
        form.addEventListener('submit', function() {
            // Disable all buttons in the form to prevent double-click
            form.querySelectorAll('.action-btn').forEach(button => {
                if (!button.classList.contains('btn-disabled')) {
                    button.disabled = true;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                }
            });
            console.log('Form submitted with status:', document.getElementById('booking_status').value);
        });
    }
});
</script>

</body>
</html>
<?php
ob_end_flush(); // Flush output buffer
?>