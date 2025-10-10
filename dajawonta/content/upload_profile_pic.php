<?php
session_start();
require_once '../db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: profile.php?error=notloggedin');
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if a file was uploaded without errors
if (isset($_FILES["profile_pic"]) && $_FILES["profile_pic"]["error"] == 0) {
    $allowed = array("jpg" => "image/jpeg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png");
    $filename = $_FILES["profile_pic"]["name"];
    $filetype = $_FILES["profile_pic"]["type"];
    $filesize = $_FILES["profile_pic"]["size"];

    // Verify file extension
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    if (!array_key_exists($ext, $allowed)) {
        header('Location: profile.php?error=invalidformat');
        exit;
    }
        
    // Verify file size - 5MB maximum
    $maxsize = 5 * 1024 * 1024;
    if ($filesize > $maxsize) {
        header('Location: profile.php?error=oversized');
        exit;
    }

    // Verify MIME type of the file
    if (in_array($filetype, $allowed)) {
        // Create a unique filename to prevent overwriting
        $new_filename = "user_" . $user_id . "_" . uniqid() . "." . $ext;
        $upload_path = "uploads/profile_pictures/";

        // Create the directory if it doesn't exist
        if (!file_exists($upload_path)) {
            mkdir($upload_path, 0777, true);
        }

        // Move the file to the target directory
        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $upload_path . $new_filename)) {
            // File uploaded successfully, now update the database
            $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
            $stmt->bind_param("si", $new_filename, $user_id);

            if ($stmt->execute()) {
                // Success
                header('Location: profile.php?success=uploaded');
                exit;
            } else {
                // Database update failed
                header('Location: profile.php?error=dbupdate');
                exit;
            }
            $stmt->close();
        } else {
            // File move failed
            header('Location: profile.php?error=fileupload');
            exit;
        }
    } else {
        header('Location: profile.php?error=invalidtype');
        exit;
    }
} else {
    // No file uploaded or upload error
    header('Location: profile.php?error=uploaderror');
    exit;
}

$conn->close();
?>