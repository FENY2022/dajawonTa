<?php
// Start the session
session_start();

// Include your database connection file
require '../db.php';

// 1. Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Configuration ---
    $upload_dir = '../uploads/legal_documents/';
    $allowed_mime_types = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png'
    ];
    $max_file_size = 5 * 1024 * 1024; // 5 MB
    $max_files = 10;
    
    $errors = [];
    $uploaded_files_list = [];

    // 2. Server-Side Validation: Check required text fields
    $required_fields = [
        'companyname', 'companyaddress', 'companyemail', 'contactnumber', 
        'service_id', 'service_name', 'description', 
        'available_date_from', 'available_date_to', 
        'available_time_from', 'available_time_to', 'price'
    ];
    
    if (!isset($_SESSION['user_id']) || empty(trim($_SESSION['user_id']))) {
        $errors[] = "Authentication required. Please log in.";
    }

    foreach ($required_fields as $field) {
        $friendly_name = ucfirst(str_replace('_', ' ', $field));
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            $errors[] = "'{$friendly_name}' is a required field.";
        }
    }

    // 3. Server-Side Validation: Check uploaded files
    if (isset($_FILES['legal_documents']) && !empty(array_filter($_FILES['legal_documents']['name']))) {
        
        // Check file count
        if (count($_FILES['legal_documents']['name']) > $max_files) {
            $errors[] = "You can upload a maximum of {$max_files} files.";
        } else {
            
            // Loop through each file
            foreach ($_FILES['legal_documents']['name'] as $key => $name) {
                // Check for upload errors
                if ($_FILES['legal_documents']['error'][$key] == UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['legal_documents']['tmp_name'][$key];
                    
                    // Check file size
                    if ($_FILES['legal_documents']['size'][$key] > $max_file_size) {
                        $errors[] = "File '{$name}' is too large (max 5MB).";
                    }
                    
                    // Check MIME type
                    $file_type = mime_content_type($tmp_name);
                    if (!in_array($file_type, $allowed_mime_types)) {
                        $errors[] = "File '{$name}' has an invalid type. Allowed: PDF, DOC, JPG, PNG.";
                    }
                } 
                // UPLOAD_ERR_NO_FILE is fine (it's an array), but other errors are not
                else if ($_FILES['legal_documents']['error'][$key] != UPLOAD_ERR_NO_FILE) {
                    $errors[] = "Error uploading file '{$name}'.";
                }
            }
        }
    }

    // 4. If there are any validation errors, redirect
    if (!empty($errors)) {
        $error_message = urlencode(implode(' ', $errors));
        header("Location: addNewservice.php?status=error&message=" . $error_message);
        exit();
    }

    // 5. No errors so far. Let's process the file uploads.
    // Ensure the upload directory exists
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
             $errors[] = "Failed to create upload directory. Check permissions.";
        }
    }

    if (empty($errors) && isset($_FILES['legal_documents']) && !empty(array_filter($_FILES['legal_documents']['name']))) {
        foreach ($_FILES['legal_documents']['name'] as $key => $name) {
            if ($_FILES['legal_documents']['error'][$key] == UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['legal_documents']['tmp_name'][$key];
                
                // Sanitize and create a unique filename
                $safe_name = preg_replace("/[^A-Za-z0-9._-]/", '', basename($name));
                $new_filename = uniqid() . '_' . $safe_name;
                $destination = $upload_dir . $new_filename;

                if (move_uploaded_file($tmp_name, $destination)) {
                    $uploaded_files_list[] = $new_filename;
                } else {
                    // If one file fails, stop and report.
                    $errors[] = "Failed to move uploaded file '{$name}'. Check server permissions.";
                    // Clean up files already moved in this request
                    foreach ($uploaded_files_list as $file_to_delete) {
                        @unlink($upload_dir . $file_to_delete);
                    }
                    $error_message = urlencode(implode(' ', $errors));
                    header("Location: addNewservice.php?status=error&message=" . $error_message);
                    exit();
                }
            }
        }
    }
    
    // 6. Sanitize and assign POST data to variables
    $user_id = (int)$_SESSION['user_id'];
    $company_name = trim($_POST['companyname']);
    $company_address = trim($_POST['companyaddress']);
    $company_email = trim($_POST['companyemail']);
    $contact_number = trim($_POST['contactnumber']);
    $service_id = (int)$_POST['service_id'];
    $service_name = trim($_POST['service_name']);
    $description = trim($_POST['description']);
    $available_date_from = trim($_POST['available_date_from']);
    $available_date_to = trim($_POST['available_date_to']);
    $available_time_from = trim($_POST['available_time_from']);
    $available_time_to = trim($_POST['available_time_to']);
    $price = (float)$_POST['price'];
    
    // Convert the array of filenames to a JSON string for database storage
    $legal_documents_json = json_encode($uploaded_files_list); // Will be '[]' if no files were uploaded

    
    // 7. Prepare the SQL INSERT statement
    $sql = "INSERT INTO service_providers (
                user_id, company_name, company_address, company_email, contact_number, 
                service_id, service_name, service_description,
                available_date_from, available_date_to, 
                available_time_from, available_time_to, price,
                legal_documents
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        // Error preparing. Delete uploaded files.
        foreach ($uploaded_files_list as $file_to_delete) { @unlink($upload_dir . $file_to_delete); }
        $error_message = urlencode("Error preparing the database statement.");
        header("Location: addNewservice.php?status=error&message=" . $error_message);
        exit();
    }
    
    // 8. Bind parameters
    // Old: "issssissssssd" (13)
    // New: "issssissssssds" (14) - adding 's' for the legal_documents JSON string
    $stmt->bind_param("issssissssssds", 
        $user_id, $company_name, $company_address, $company_email, $contact_number, 
        $service_id, $service_name, $description,
        $available_date_from, $available_date_to,
        $available_time_from, $available_time_to, $price,
        $legal_documents_json
    );
    
    // 9. Execute the statement and check for success
    if ($stmt->execute()) {
        // Success! Now, notify the administrators.
        // ===================================================================
        // START: Administrator Notification Logic (Unchanged)
        // ===================================================================
        $sql_admins = "SELECT id FROM users WHERE user_rules = 2";
        $result_admins = $conn->query($sql_admins);

        if ($result_admins && $result_admins->num_rows > 0) {
            $notification_sql = "INSERT INTO notifications (user_id, message, role) VALUES (?, ?, ?)";
            $stmt_notify_insert = $conn->prepare($notification_sql);
            $update_link_sql = "UPDATE notifications SET link = ? WHERE notification_id = ?";
            $stmt_notify_update = $conn->prepare($update_link_sql);

            if ($stmt_notify_insert && $stmt_notify_update) {
                $message = "New provider '" . htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8') . "' has registered.";
                $admin_role = 2;
                while ($admin_row = $result_admins->fetch_assoc()) {
                    $admin_id = $admin_row['id'];
                    $stmt_notify_insert->bind_param("isi", $admin_id, $message, $admin_role);
                    $stmt_notify_insert->execute();
                    $notification_id = $conn->insert_id;
                    $final_link = "dashboard.php?action=confirmService_providers&notification_id=" . $notification_id;
                    $stmt_notify_update->bind_param("si", $final_link, $notification_id);
                    $stmt_notify_update->execute();
                }
                $stmt_notify_insert->close();
                $stmt_notify_update->close();
            }
        }
        // ===================================================================
        // END: Administrator Notification Logic
        // ===================================================================

        $stmt->close();
        $conn->close();
        header("Location: addNewservice.php?status=success");
        exit();

    } else {
        // Failure. CRITICAL: Delete the files we just uploaded.
        foreach ($uploaded_files_list as $file_to_delete) {
            @unlink($upload_dir . $file_to_delete);
        }

        if ($conn->errno === 1062) {
             $error_message = urlencode("You have already registered as a service provider.");
        } else {
             $error_message = urlencode("An error occurred during registration. Please try again. Error: " . $stmt->error);
        }
        
        $stmt->close();
        $conn->close();
        header("Location: addNewservice.php?status=error&message=" . $error_message);
        exit();
    }

} else {
    // If accessed directly, redirect
    header("Location: addNewservice.php");
    exit();
}
?>