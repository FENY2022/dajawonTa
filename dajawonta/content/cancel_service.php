<?php
// Start the session to check user details
session_start();

// Include our database connection file
require '../db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if an ID was passed and it's a valid integer
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $id = $_GET['id'];

    // Delete service by ID only
    $sql = "DELETE FROM service_providers WHERE id = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }

    // Bind id only
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $_SESSION['status_message'] = "Service successfully canceled.";
        $_SESSION['status_type'] = "success";
    } else {
        $_SESSION['status_message'] = "Error canceling the service: " . $stmt->error;
        $_SESSION['status_type'] = "error";
    }

    $stmt->close();
} else {
    $_SESSION['status_message'] = "Invalid request.";
    $_SESSION['status_type'] = "error";
}

$conn->close();

// Redirect back to listings page
header('Location: view_listings.php');
exit();
?>
