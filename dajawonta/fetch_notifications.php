<?php
session_start();
header('Content-Type: application/json');

// --- 1. Database Connection ---
// In a real-world application, it's best to have this in a separate file.
// The provided code already includes db.php, so we'll assume it exists.
// Example content for db.php:
/*
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dajawonta_db";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
*/
require_once('db.php');

$response = [
    'success' => false,
    'notifications' => [],
    'unread_count' => 0
];

// --- 2. Authentication and Role Check ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_id']) || !isset($_SESSION['user_rules'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'error' => 'User not authenticated or role not set.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_rules'];

// --- 3. Fetch Unread Notifications with User & Role Filter ---
// Using a single prepared statement for both fetching and counting is possible but can be less readable.
// Your approach of two separate queries is fine and often more performant if the notification table is large.
// Let's optimize slightly by getting the count first to avoid unnecessary data fetching if there are no notifications.

try {
    // Start a transaction for atomicity, though not strictly required here
    // but good practice for multi-query operations.
    // $conn->begin_transaction();

    // Query 1: Get the total unread count for the specific user and role
    $count_sql = "SELECT COUNT(notification_id) AS unread_count FROM notifications WHERE user_id = ? AND is_read = 0 AND role = ?";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("is", $user_id, $user_role);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_row = $count_result->fetch_assoc();
    $response['unread_count'] = $count_row['unread_count'];
    $count_stmt->close();

    // Query 2: Fetch the actual notifications if the count is greater than zero
    if ($response['unread_count'] > 0) {
        $sql = "SELECT notification_id, message, link, created_at FROM notifications WHERE user_id = ? AND is_read = 0 AND role = ? ORDER BY created_at DESC LIMIT 10";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $user_id, $user_role);
        $stmt->execute();
        $result = $stmt->get_result();
        $response['notifications'] = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
    
    // Commit the transaction
    // $conn->commit();

    $response['success'] = true;

} catch (Exception $e) {
    // $conn->rollback();
    http_response_code(500);
    $response['error'] = 'Database error: ' . $e->getMessage();
}

// --- 4. Close Connection and Return Response ---
$conn->close();

echo json_encode($response);


?>