<?php
session_start();
// Require your DB connection file. Adjust path if necessary.
require 'db.php'; 

if (!isset($_GET['booking_id'])) {
    // Redirect gracefully if the booking ID is missing
    $_SESSION['toast_message'] = "Payment successful, but the booking ID was missing.";
    $_SESSION['toast_type'] = "warning";
    header("Location: dashboard.php");
    exit;
}

$booking_id = intval($_GET['booking_id']);

// Update payment status in the database
// This logic assumes a direct redirect and marks it as paid immediately.
// For robust, production-level code, you should use PayMongo Webhooks 
// to verify the payment status before updating the database.
$stmt = $conn->prepare("UPDATE bookings SET payment_status = 'paid', booking_status = 'approved' WHERE id = ? AND payment_status != 'paid'");
$stmt->bind_param("i", $booking_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $_SESSION['toast_message'] = "Payment successful! Your booking has been confirmed.";
    $_SESSION['toast_type'] = "success";
} else {
    // This happens if the user refreshes the page or status was already updated via webhook
    $_SESSION['toast_message'] = "Your payment was processed, and the booking status is already confirmed.";
    $_SESSION['toast_type'] = "info";
}
$stmt->close();
$conn->close();

// Redirect the user to the booking details page in the dashboard
header("Location: dashboard.php?action=view_booking&booking_id=" . $booking_id);
exit;
?>