<?php
session_start();
require_once '../db.php'; // Adjust path if your db.php is elsewhere

// 1. Check if the request is a POST request
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    // Redirect non-POST requests or show an error
    header("Location: profile.php");
    exit;
}

// 2. Security Check: Ensure user is logged in and the form user ID matches the session user ID
if (!isset($_SESSION['user_id']) || !isset($_POST['user_id']) || $_SESSION['user_id'] != $_POST['user_id']) {
    // Redirect if security check fails
    header("Location: ../login.php?error=unauthorized");
    exit;
}

// 3. Retrieve and sanitize form data
$user_id      = $_POST['user_id'];
$first_name   = trim($_POST['first_name']);
$middle_name  = trim($_POST['middle_name']);
$last_name    = trim($_POST['last_name']);
$suffix       = trim($_POST['suffix']);
$gender       = trim($_POST['gender']);
$birthday     = trim($_POST['birthday']);
$phone_number = trim($_POST['phone_number']);
$email        = trim($_POST['email']);
$region       = trim($_POST['region']);
$province     = trim($_POST['province']);
$municipality = trim($_POST['municipality']);
$barangay     = trim($_POST['barangay']);
$purok        = trim($_POST['purok']);

// Basic validation (optional, but recommended)
if (empty($first_name) || empty($last_name) || empty($email)) {
    header("Location: profile.php?update=error&reason=empty");
    exit;
}

// 4. Prepare the UPDATE statement to prevent SQL injection
$sql = "UPDATE users SET 
            first_name = ?, 
            middle_name = ?, 
            last_name = ?, 
            suffix = ?, 
            gender = ?, 
            birthday = ?, 
            phone_number = ?, 
            email = ?, 
            region = ?, 
            province = ?, 
            municipality = ?, 
            barangay = ?, 
            purok = ? 
        WHERE id = ?";

if ($stmt = $conn->prepare($sql)) {
    // 5. Bind parameters. The types string 'sssssssssssssi' must match the columns
    // s = string, i = integer
    $stmt->bind_param(
        "sssssssssssssi",
        $first_name,
        $middle_name,
        $last_name,
        $suffix,
        $gender,
        $birthday,
        $phone_number,
        $email,
        $region,
        $province,
        $municipality,
        $barangay,
        $purok,
        $user_id
    );

    // 6. Execute the statement and redirect with a status message
    if ($stmt->execute()) {
        // Success
        header("Location: profile.php?update=success");
    } else {
        // Error
        header("Location: profile.php?update=error");
    }

    $stmt->close();
} else {
    // SQL prepare error
    header("Location: profile.php?update=error&reason=sql");
}

$conn->close();
exit;
?>