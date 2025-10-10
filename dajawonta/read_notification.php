<?php
// Start the session to access user data
session_start();

// Include your database configuration file
// This file should establish a connection to your database, typically creating a $mysqli object.
// Example: $mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
require_once 'config/db_config.php';

// Set the content type header to signal a JSON response
header('Content-Type: application/json');

// --- Security Checks ---

// 1. Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_id'])) {
    // If not logged in, send an authentication error and stop the script
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'error' => 'User not authenticated.']);
    exit;
}

// 2. Ensure the request is a POST request, as expected from the frontend fetch call
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // If not, send a method not allowed error and stop
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'error' => 'Invalid request method. Only POST is accepted.']);
    exit;
}


// --- Input Processing ---

// Get the raw JSON payload from the request body
$input = json_decode(file_get_contents('php://input'), true);

// 3. Validate that the notification_id was provided and is a valid number
if (!isset($input['notification_id']) || !filter_var($input['notification_id'], FILTER_VALIDATE_INT)) {
    // If validation fails, send a bad request error and stop
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'error' => 'Invalid or missing notification ID.']);
    exit;
}

// Sanitize the input
$notification_id = (int)$input['notification_id'];
$user_id = (int)$_SESSION['user_id'];


// --- Database Operation ---

// 4. Prepare the SQL UPDATE statement to prevent SQL injection
// This query updates the 'is_read' status to 1 (true).
// The WHERE clause is crucial: it ensures a user can ONLY update THEIR OWN notifications.
$sql = "UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?";

if ($stmt = $mysqli->prepare($sql)) {
    // Bind the notification ID and user ID as integer parameters
    $stmt->bind_param("ii", $notification_id, $user_id);

    // Execute the statement
    if ($stmt->execute()) {
        // Check if a row was actually affected.
        // If affected_rows is 0, it means the notification didn't exist or didn't belong to the user.
        if ($stmt->affected_rows > 0) {
            // Success: The notification was successfully marked as read
            echo json_encode(['success' => true, 'message' => 'Notification marked as read.']);
        } else {
            // No rows were updated, which is not a server error but good to know.
            echo json_encode(['success' => false, 'error' => 'Notification not found for this user or already marked as read.']);
        }
    } else {
        // An error occurred during the execution of the query
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'error' => 'Database query execution failed.']);
    }

    // Close the prepared statement
    $stmt->close();
} else {
    // An error occurred while preparing the query
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'error' => 'Database statement preparation failed.']);
}

// Close the database connection
$mysqli->close();

?>