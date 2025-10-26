<?php
session_start();

if (!isset($_GET['booking_id'])) {
    // If the booking ID is missing, just go to the dashboard
    $_SESSION['toast_message'] = "Payment cancelled. Please try again later.";
    $_SESSION['toast_type'] = "warning";
    header("Location: dashboard.php");
    exit;
}

$booking_id = intval($_GET['booking_id']);

// Set a toast message for the user
$_SESSION['toast_message'] = "Payment cancelled. You can try again from the booking details.";
$_SESSION['toast_type'] = "warning";

// Redirect the user back to the booking details page for retrying payment
header("Location: dashboard.php?action=customer_booking_details&booking_id=" . $booking_id);
exit;
?>