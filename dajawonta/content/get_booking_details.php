<?php
session_start();

// 1. Include your database connection
require_once '../db.php'; 

// 2. Set header to output JSON
header('Content-Type: application/json');

/**
 * Helper function to send a standardized JSON response and exit.
 * @param bool $success - Whether the request was successful
 * @param mixed $data_or_message - The data (on success) or error message (on failure)
 */
function send_json_response($success, $data_or_message) {
    if ($success) {
        echo json_encode(['success' => true, 'booking' => $data_or_message]);
    } else {
        echo json_encode(['success' => false, 'message' => $data_or_message]);
    }
    exit;
}

// 3. Get customer and booking IDs
$customer_id = $_SESSION['user_id'] ?? 0;
$booking_id = intval($_GET['booking_id'] ?? 0);

// 4. Security & Validation Checks
if ($customer_id === 0) {
    send_json_response(false, "User not logged in.");
}
if ($booking_id === 0) {
    send_json_response(false, "Invalid booking ID specified.");
}

// 5. Prepare and execute the query
// This query joins users twice to get both provider and customer names
$sql_detail = "SELECT 
    b.*,
    s.service_name,
    s.description AS service_description,
    CONCAT(u_provider.first_name, ' ', u_provider.last_name) AS provider_name,
    u_provider.email AS provider_email,
    u_provider.phone AS provider_phone,
    COALESCE(u_provider.profile_image, CONCAT('https://i.pravatar.cc/150?u=', u_provider.id)) AS provider_avatar,
    CONCAT(u_customer.first_name, ' ', u_customer.last_name) AS customer_name 
FROM 
    bookings AS b
JOIN 
    service_providers AS sp ON b.provider_id = sp.id
JOIN 
    users AS u_provider ON sp.user_id = u_provider.id
JOIN 
    services AS s ON sp.service_id = s.service_id
JOIN
    users AS u_customer ON b.customer_id = u_customer.id
WHERE 
    b.id = ? AND b.customer_id = ?";

$stmt_detail = $conn->prepare($sql_detail);

if (!$stmt_detail) {
    send_json_response(false, "Database query preparation failed: " . $conn->error);
}

$stmt_detail->bind_param("ii", $booking_id, $customer_id);
$stmt_detail->execute();
$result_detail = $stmt_detail->get_result();

if ($result_detail && $row = $result_detail->fetch_assoc()) {
    // 6. Format data and send success response
    $row['display_date_from'] = date('F j, Y', strtotime($row['booking_date_from']));
    $row['display_time_from'] = date('g:i A', strtotime($row['booking_time_from']));
    $row['display_date_to'] = date('F j, Y', strtotime($row['booking_date_to']));
    $row['display_time_to'] = date('g:i A', strtotime($row['booking_time_to']));
    
    send_json_response(true, $row);
} else {
    // 7. Send failure response
    send_json_response(false, "Booking not found or access denied.");
}

$stmt_detail->close();
$conn->close();

?>