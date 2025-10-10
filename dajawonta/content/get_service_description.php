<?php
// Set the content type to plain text for the response
header('Content-Type: text/plain');

// Include your database connection file.
// Make sure the path is correct relative to this file's location.
require '../db.php';

// Initialize a variable to hold the service ID
$service_id = 0;

// 1. Validate the incoming request
// Check if 'id' is present in the URL and is not empty.
if (isset($_GET['id']) && !empty($_GET['id'])) {
    // Sanitize the input by casting it to an integer.
    $service_id = (int)$_GET['id'];
}

// 2. Proceed only if we have a valid, positive service ID
if ($service_id > 0) {
    // Prepare the SQL query using a placeholder (?) to prevent SQL injection
    $sql = "SELECT description FROM services WHERE service_id = ?";
    
    // Prepare the statement
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // Bind the integer service_id to the placeholder
        $stmt->bind_param("i", $service_id);
        
        // Execute the query
        $stmt->execute();
        
        // Get the result set
        $result = $stmt->get_result();
        
        // 3. Check if a service was found
        if ($result->num_rows > 0) {
            // Fetch the data as an associative array
            $row = $result->fetch_assoc();
            
            // Output the description. Use htmlspecialchars as a good practice.
            // If the description is NULL in the DB, this will output an empty string.
            echo htmlspecialchars($row['description'] ?? 'No description available.');
        } else {
            // No service found with that ID
            echo "Description not found for the selected service.";
        }
        
        // Close the statement
        $stmt->close();
    } else {
        // Error preparing the statement
        // In a production environment, you might log this error instead of echoing it.
        echo "Error: Could not prepare the database query.";
    }
} else {
    // Invalid or missing ID was provided in the URL
    echo "Invalid service ID.";
}

// Close the database connection
$conn->close();
?>