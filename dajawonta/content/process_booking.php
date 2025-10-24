<?php
session_start();
require '../db.php';

// Only process POST requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Get and sanitize form data
    $provider_id = $_POST['provider_id'];
    $customer_name = trim($_POST['customer_name']);
    $customer_email = trim($_POST['customer_email']);
    $customer_phone = trim($_POST['customer_phone']);
    $booking_date_from = $_POST['booking_date_from'];
    $booking_date_to = $_POST['booking_date_to'];
    $booking_time = $_POST['booking_time'];
    $special_request = trim($_POST['special_request']); // <-- 1. VARIABLE ADDED

    // 2. Server-side Validation
    if (
        empty($provider_id) || empty($customer_name) || empty($customer_email) ||
        empty($customer_phone) || empty($booking_date_from) || empty($booking_date_to) || empty($booking_time)
        // Note: $special_request is optional, so it's not checked in the empty() validation
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

    // Convert times
    $req_date_from_ts = strtotime($booking_date_from);
    $req_date_to_ts = strtotime($booking_date_to);
    $provider_date_from_ts = strtotime($provider['available_date_from']);
    $provider_date_to_ts = strtotime($provider['available_date_to']);
    $provider_time_from_ts = strtotime($provider['available_time_from']);
    $provider_time_to_ts = strtotime($provider['available_time_to']);
    $req_time_only_ts = strtotime($booking_time);

    if ($req_date_from_ts < $provider_date_from_ts || $req_date_to_ts > $provider_date_to_ts) {
        $_SESSION['message'] = "The selected date range is outside the provider's availability.";
        $_SESSION['msg_type'] = "danger";
        header("Location: book_service.php?provider_id=" . $provider_id);
        exit;
    }

    if ($req_time_only_ts < $provider_time_from_ts || $req_time_only_ts > $provider_time_to_ts) {
        $_SESSION['message'] = "The selected time is outside the provider's available hours.";
        $_SESSION['msg_type'] = "danger";
        header("Location: book_service.php?provider_id=" . $provider_id);
        exit;
    }

    // 4. Insert booking
    
    // <-- 2. SQL QUERY UPDATED -->
    $sql = "INSERT INTO bookings (provider_id, customer_name, customer_email, customer_phone, booking_date_from, booking_date_to, booking_time, special_request, booking_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
            
    $stmt = $conn->prepare($sql);
    
    // <-- 3. BIND_PARAM UPDATED -->
    $stmt->bind_param("isssssss", $provider_id, $customer_name, $customer_email, $customer_phone, $booking_date_from, $booking_date_to, $booking_time, $special_request);

    if ($stmt->execute()) {
        $new_booking_id = $conn->insert_id;
        $_SESSION['message'] = "Your booking request has been submitted successfully!";
        $_SESSION['msg_type'] = "success";

        // --- START NOTIFICATION CODE ---

        // Fetch the provider's linked user_id from the service_providers table
        $provider_user_id = $provider['user_id']; // Make sure 'user_id' column exists in your table

        // Prepare notification details
        $notification_user_id = $provider_user_id;
        $notification_message = "You have a new booking request from " . htmlspecialchars($customer_name) . ".";
        $notification_link = "dashboard.php?action=provider_booking_details&booking_id=" . $new_booking_id;
        $notification_role = 1; // Changed role to 1

        // Insert into notifications table
        $notify_sql = "INSERT INTO notifications (user_id, message, link, role) VALUES (?, ?, ?, ?)";
        $notify_stmt = $conn->prepare($notify_sql);

        if (!$notify_stmt) {
            $_SESSION['message'] .= " (Booking saved, but failed to prepare notification query: " . $conn->error . ")";
            $_SESSION['msg_type'] = "warning";
        } else {
            $notify_stmt->bind_param("issi", $notification_user_id, $notification_message, $notification_link, $notification_role);

            if ($notify_stmt->execute()) {
                $_SESSION['message'] .= " A notification has been sent to the provider.";
            } else {
                $_SESSION['message'] .= " (Notification failed: " . $notify_stmt->error . ")";
                $_SESSION['msg_type'] = "warning";
            }

            $notify_stmt->close();
        }

        // --- END NOTIFICATION CODE ---

    } else {
        $_SESSION['message'] = "There was an error submitting your booking. Please try again. Error: " . $stmt->error;
        $_SESSION['msg_type'] = "danger";
    }

    $stmt->close();
    $conn->close();

    // Redirect back to booking page
    header("Location: book_service.php?provider_id=" . $provider_id);
    exit;

} else {
    die("Invalid request method.");
}
?>