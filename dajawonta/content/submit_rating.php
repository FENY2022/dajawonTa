<?php
session_start();
include '../db.php'; // ../ to go up one level

// Check if user is logged in and the form was submitted
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: my_reviews.php?error=Unauthorized access.");
    exit;
}

// Get and sanitize POST data
$booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
$provider_id = filter_input(INPUT_POST, 'provider_id', FILTER_VALIDATE_INT);
$customer_id = filter_input(INPUT_POST, 'customer_id', FILTER_VALIDATE_INT);
$rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
$comment = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);

// Validate data
if (!$booking_id || !$provider_id || !$customer_id || !$rating) {
    header("Location: my_reviews.php?error=Invalid data submitted.");
    exit;
}

// Security check: Ensure the customer ID from the form matches the logged-in user's ID
if ($customer_id !== $_SESSION['user_id']) {
    header("Location: my_reviews.php?error=Authorization failed.");
    exit;
}

// --- CHANGED HERE ---
// Security check: Verify this user actually made this booking AND it is 'completed'.
// Changed 'booking_status = 'approved'' to 'booking_status = 'completed''
// Removed 'AND booking_date_to < CURDATE()'
$verify_sql = "SELECT id FROM bookings 
               WHERE id = ? 
               AND customer_id = ? 
               AND booking_status = 'completed'"; 
$stmt = $conn->prepare($verify_sql);
$stmt->bind_param("ii", $booking_id, $customer_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: my_reviews.php?error=Booking not found or not eligible for review.");
    exit;
}
$stmt->close();

// Check if this booking has already been rated (using the UNIQUE key on booking_id)
$insert_sql = "INSERT INTO provider_ratings (booking_id, provider_id, customer_id, rating, comment) 
               VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($insert_sql);
if ($stmt === false) {
    header("Location: my_reviews.php?error=Database error (prepare).");
    exit;
}

$stmt->bind_param("iiiis", $booking_id, $provider_id, $customer_id, $rating, $comment);

if ($stmt->execute()) {
    // Success
    $stmt->close();
    $conn->close();
    header("Location: my_reviews.php?success=Thank you for your review!");
    exit;
} else {
    // Fail, likely due to UNIQUE constraint (already rated)
    $stmt->close();
    $conn->close();
    if ($conn->errno == 1062) { // Error code for duplicate entry
        header("Location: my_reviews.php?error=You have already submitted a review for this booking.");
    } else {
        header("Location: my_reviews.php?error=Could not submit review. " . $conn->error);
    }
    exit;
}
?>