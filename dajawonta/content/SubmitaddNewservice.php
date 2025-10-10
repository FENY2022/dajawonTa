<?php
// Start the session to be safe, although not strictly needed if user_id is in the form
session_start();

// Include your database connection file
// Make sure the path is correct relative to this script's location
require '../db.php';

// 1. Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 2. Server-Side Validation: Check if all required fields are set and not empty
    $required_fields = [
        'companyname', 'companyaddress', 'companyemail', 'contactnumber', 
        'service_id', 'service_name', 'description', 
        'available_date_from', 'available_date_to', 
        'available_time_from', 'available_time_to', 'price'
    ];
    $errors = [];

    // Check for user ID in session first for security
    if (!isset($_SESSION['user_id']) || empty(trim($_SESSION['user_id']))) {
        $errors[] = "Authentication required. Please log in.";
    }

    foreach ($required_fields as $field) {
        // Create a more user-friendly name for the error message
        $friendly_name = str_replace('_', ' ', $field);
        $friendly_name = ucfirst($friendly_name);

        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            $errors[] = "'{$friendly_name}' is a required field.";
        }
    }

    // If there are validation errors, combine them into a single message and redirect
    if (!empty($errors)) {
        // Join the array of error messages into a single string.
        $error_message = urlencode(implode(' ', $errors));
        header("Location: addNewservice.php?status=error&message=" . $error_message);
        exit();
    }

    // 3. Sanitize and assign POST data to variables
    $user_id = (int)$_SESSION['user_id'];
    $company_name = trim($_POST['companyname']);
    $company_address = trim($_POST['companyaddress']);
    $company_email = trim($_POST['companyemail']);
    $contact_number = trim($_POST['contactnumber']);
    $service_id = (int)$_POST['service_id'];
    $service_name = trim($_POST['service_name']);
    $description = trim($_POST['description']);
    // New fields
    $available_date_from = trim($_POST['available_date_from']);
    $available_date_to = trim($_POST['available_date_to']);
    $available_time_from = trim($_POST['available_time_from']);
    $available_time_to = trim($_POST['available_time_to']);
    $price = (float)$_POST['price'];

    
    // 4. Prepare the SQL INSERT statement to prevent SQL injection
    $sql = "INSERT INTO service_providers (
                user_id, company_name, company_address, company_email, contact_number, 
                service_id, service_name, service_description,
                available_date_from, available_date_to, 
                available_time_from, available_time_to, price
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);

    // Check if the statement was prepared successfully
    if ($stmt === false) {
        $error_message = urlencode("Error preparing the database statement.");
        header("Location: addNewservice.php?status=error&message=" . $error_message);
        exit();
    }
    
    // 5. Bind parameters to the prepared statement
    // Type legend: i=integer, s=string, d=double
    $stmt->bind_param("issssissssssd", 
        $user_id, $company_name, $company_address, $company_email, $contact_number, 
        $service_id, $service_name, $description,
        $available_date_from, $available_date_to,
        $available_time_from, $available_time_to, $price
    );
    
    // 6. Execute the statement and check for success
    if ($stmt->execute()) {
        // Success! Now, notify the administrators.

        // ===================================================================
        // START: Administrator Notification Logic
        // ===================================================================

        // Step A: Find all administrator user IDs
        $sql_admins = "SELECT id FROM users WHERE user_rules = 2";
        $result_admins = $conn->query($sql_admins);

        if ($result_admins && $result_admins->num_rows > 0) {
            
            // Step B: Prepare two separate statements: one for INSERT, one for UPDATE
            $notification_sql = "INSERT INTO notifications (user_id, message, role) VALUES (?, ?, ?)";
            $stmt_notify_insert = $conn->prepare($notification_sql);

            $update_link_sql = "UPDATE notifications SET link = ? WHERE notification_id = ?";
            $stmt_notify_update = $conn->prepare($update_link_sql);

            if ($stmt_notify_insert && $stmt_notify_update) {
                // Step C: Define static notification details
                $message = "New provider '" . htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8') . "' has registered.";
                $admin_role = 2;

                // Step D: Loop through each admin to create and update their notification
                while ($admin_row = $result_admins->fetch_assoc()) {
                    $admin_id = $admin_row['id'];
                    
                    // 1. Insert the basic notification to generate an ID
                    $stmt_notify_insert->bind_param("isi", $admin_id, $message, $admin_role);
                    $stmt_notify_insert->execute();

                    // 2. Get the ID of the notification we just created
                    $notification_id = $conn->insert_id;

                    // 3. Create the final link using the new ID
                    // Note: Using a key like '&id=' is clearer than just the number
                    $final_link = "dashboard.php?action=confirmService_providers&notification_id=" . $notification_id;
                    
                    // 4. Update the record with the correct link
                    $stmt_notify_update->bind_param("si", $final_link, $notification_id);
                    $stmt_notify_update->execute();
                }
                
                // Close the notification statements
                $stmt_notify_insert->close();
                $stmt_notify_update->close();
            }
        }
        // ===================================================================
        // END: Administrator Notification Logic
        // ===================================================================

        // Close connections and redirect with success message
        $stmt->close();
        $conn->close();
        header("Location: addNewservice.php?status=success");
        exit();

    } else {
        // Failure. Check for a duplicate entry error (error code 1062)
        if ($conn->errno === 1062) {
             $error_message = urlencode("You have already registered as a service provider.");
        } else {
             // For other errors, provide a generic message
             $error_message = urlencode("An error occurred during registration. Please try again.");
             // Optional: Log the specific error: error_log($stmt->error);
        }
        
        $stmt->close();
        $conn->close();
        header("Location: addNewservice.php?status=error&message=" . $error_message);
        exit();
    }

} else {
    // If the script is accessed directly without POST data, redirect
    header("Location: addNewservice.php");
    exit();
}
?>