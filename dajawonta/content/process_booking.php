<?php
session_start();
require '../db.php';

// Only process POST requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Ensure customer is logged in ---
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['message'] = "You must be logged in to book a service.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../login.php");
        exit;
    }

    // 1. Get and sanitize form data
    $customer_id = $_SESSION['user_id'];
    $role = isset($_SESSION['user_rules']) ? $_SESSION['user_rules'] : 'customer';
    $provider_id = $_POST['provider_id'];
    $customer_name = trim($_POST['customer_name']);
    $customer_email = trim($_POST['customer_email']);
    $customer_phone = trim($_POST['customer_phone']);
    $booking_date_from = $_POST['booking_date_from'];
    $booking_date_to = $_POST['booking_date_to'];
    $service_id = $_POST['service_id']; // This is now being used!

    // Time range
    $booking_time_from = $_POST['booking_time_from'];
    $booking_time_to = $_POST['booking_time_to'];
    
    $special_request = trim($_POST['special_request']);

    // 2. Validation
    if (
        empty($provider_id) || empty($customer_name) || empty($customer_email) ||
        empty($customer_phone) || empty($booking_date_from) || empty($booking_date_to) ||
        empty($booking_time_from) || empty($booking_time_to) || empty($service_id) // Added check for service_id
    ) {
        $_SESSION['message'] = "All required fields must be filled.";
        $_SESSION['msg_type'] = "danger";
        header("Location: book_service.php?provider_id=" . $provider_id);
        exit;
    }

    if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = "Invalid email format.";
        $_SESSION['msg_type'] = "danger";
        header("Location: book_service.php?provider_id=" . $provider_id);
        exit;
    }

    if (strtotime($booking_date_from) > strtotime($booking_date_to)) {
        $_SESSION['message'] = "The 'From' date cannot be later than the 'To' date.";
        $_SESSION['msg_type'] = "danger";
        header("Location: book_service.php?provider_id=" . $provider_id);
        exit;
    }

    if (strtotime($booking_time_from) > strtotime($booking_time_to)) {
        $_SESSION['message'] = "The 'From' time cannot be later than the 'To' time.";
        $_SESSION['msg_type'] = "danger";
        header("Location: book_service.php?provider_id=" . $provider_id);
        exit;
    }

    // 3. Re-check provider availability
    $stmt = $conn->prepare("SELECT * FROM service_providers WHERE id = ? AND is_approved = 1 AND is_available = 1");
    $stmt->bind_param("i", $provider_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['message'] = "This provider is no longer available for booking.";
        $_SESSION['msg_type'] = "danger";
        header("Location: index.php");
        exit;
    }

    $provider = $result->fetch_assoc();
    $stmt->close();

    $total_price = $provider['price']; // Note: This is the provider's *rate*

    $req_date_from_ts = strtotime($booking_date_from);
    $req_date_to_ts = strtotime($booking_date_to);
    $provider_date_from_ts = strtotime($provider['available_date_from']);
    $provider_date_to_ts = strtotime($provider['available_date_to']);
    
    $provider_time_from_ts = strtotime($provider['available_time_from']);
    $provider_time_to_ts = strtotime($provider['available_time_to']);
    $req_time_from_ts = strtotime($booking_time_from);
    $req_time_to_ts = strtotime($booking_time_to);

    if ($req_date_from_ts < $provider_date_from_ts || $req_date_to_ts > $provider_date_to_ts) {
        $_SESSION['message'] = "The selected date range is outside the provider's availability.";
        $_SESSION['msg_type'] = "danger";
        header("Location: book_service.php?provider_id=" . $provider_id);
        exit;
    }

    if ($req_time_from_ts < $provider_time_from_ts || $req_time_to_ts > $provider_time_to_ts) {
        $_SESSION['message'] = "The selected time range is outside the provider's available hours.";
        $_SESSION['msg_type'] = "danger";
        header("Location: book_service.php?provider_id=" . $provider_id);
        exit;
    }

    // 4. Insert booking (with customer_id, role, and service_id)
    $sql = "INSERT INTO bookings (
                customer_id, provider_id, service_id, role, customer_name, customer_email, customer_phone, 
                booking_date_from, booking_date_to, booking_time_from, booking_time_to, 
                special_request, total_price, booking_status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
            
    $stmt = $conn->prepare($sql);

    // Bind parameters - "iiisssssssssd"
    $stmt->bind_param(
        "iiisssssssssd", 
        $customer_id,
        $provider_id,
        $service_id, // <-- Here is the change
        $role, 
        $customer_name,
        $customer_email,
        $customer_phone,
        $booking_date_from,
        $booking_date_to,
        $booking_time_from,
        $booking_time_to,
        $special_request,
        $total_price
    );

    if ($stmt->execute()) {
        $new_booking_id = $conn->insert_id;
        $_SESSION['message'] = "Your booking request has been submitted successfully!";
        $_SESSION['msg_type'] = "success";

        // --- START PROVIDER NOTIFICATION ---
        $provider_user_id = $provider['user_id']; 
        $notification_user_id = $provider_user_id;
        $notification_message = "You have a new booking request from " . htmlspecialchars($customer_name) . ".";
        $notification_link = "dashboard.php?action=provider_booking_details&booking_id=" . $new_booking_id;
        $notification_role = 1; // provider

        $notify_sql = "INSERT INTO notifications (user_id, message, link, role) VALUES (?, ?, ?, ?)";
        $notify_stmt = $conn->prepare($notify_sql);

        if ($notify_stmt) {
            $notify_stmt->bind_param("issi", $notification_user_id, $notification_message, $notification_link, $notification_role);
            if ($notify_stmt->execute()) {
                $_SESSION['message'] .= " A notification has been sent to the provider.";
            } else {
                $_SESSION['message'] .= " (Provider notification failed: " . $notify_stmt->error . ")";
                $_SESSION['msg_type'] = "warning";
            }
            $notify_stmt->close();
        }
        // --- END PROVIDER NOTIFICATION ---

        // --- START CUSTOMER CONFIRMATION NOTIFICATION ---
        $customer_notification_message = "Your booking request for " . htmlspecialchars($provider['service_name']) . " has been received.";
        $customer_notification_link = "dashboard.php?action=my_bookings&booking_id=" . $new_booking_id;
        $customer_role = 2; // customer

        $cust_notify_sql = "INSERT INTO notifications (user_id, message, link, role) VALUES (?, ?, ?, ?)";
        $cust_stmt = $conn->prepare($cust_notify_sql);

        if ($cust_stmt) {
            $cust_stmt->bind_param("issi", $customer_id, $customer_notification_message, $customer_notification_link, $customer_role);
            if ($cust_stmt->execute()) {
                $_SESSION['message'] .= " You will be notified once the provider responds.";
            } else {
                $_SESSION['message'] .= " (Customer notification failed: " . $cust_stmt->error . ")";
                $_SESSION['msg_type'] = "warning";
            }
            $cust_stmt->close();
        }
        // --- END CUSTOMER NOTIFICATION ---

    } else {
        $_SESSION['message'] = "There was an error submitting your booking. Please try again. Error: " . $stmt->error;
        $_SESSION['msg_type'] = "danger";
    }

    $stmt->close();
    $conn->close();

    header("Location: book_service.php?provider_id=" . $provider_id);
    exit;

} else {
    die("Invalid request method.");
}
?>