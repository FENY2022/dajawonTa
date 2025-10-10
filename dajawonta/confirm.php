<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
include "db.php"; 

$message = '';
$message_type = 'error'; // Can be 'error' or 'success'

// Check if a token is provided in the URL
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];

    // Check database connection
    if (!$conn || $conn->connect_error) {
        $message = "Database connection failed. Please try again later.";
    } else {
        // Prepare a statement to find the user with the given token
        // We look for a user that is NOT yet verified (is_verified = 0)
        $sql = "SELECT id, is_verified FROM users WHERE verification_code = ? AND is_verified = 0 LIMIT 1";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                // User found, now update their status to verified
                $user = $result->fetch_assoc();
                $user_id = $user['id'];

                // Set is_verified to 1 and clear the token so it can't be reused
                $update_sql = "UPDATE users SET is_verified = 1, verification_code = NULL WHERE id = ?";
                if ($update_stmt = $conn->prepare($update_sql)) {
                    $update_stmt->bind_param("i", $user_id);
                    if ($update_stmt->execute()) {
                        $message = "Your account has been successfully verified! You can now log in.";
                        $message_type = 'success';
                    } else {
                        $message = "Error: Could not verify your account. Please contact support.";
                    }
                    $update_stmt->close();
                }
            } else {
                // No user found with this token, or they are already verified
                $message = "This verification link is invalid, has expired, or the account is already verified.";
            }
            $stmt->close();
        } else {
            $message = "A database error occurred. Please try again later.";
        }
        $conn->close();
    }
} else {
    $message = "No verification token provided. The link is incomplete.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Confirmation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Roboto+Slab:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="logo/dajawonta.png">
    <style>
        body { font-family: 'Roboto', sans-serif; background-color: #f4f6f9; }
        .card { background: white; border-radius: 16px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .success-icon { color: #28a745; }
        .error-icon { color: #dc3545; }
        .btn-primary { background-color: #1976D2; color: white; }
        .btn-primary:hover { background-color: #0D47A1; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="card w-full max-w-md p-8 text-center">
        <img src="logo/dajawonta.png" alt="DajawonTa Logo" class="h-24 w-24 mx-auto mb-4">
        
        <?php if ($message_type === 'success'): ?>
            <i class="fas fa-check-circle text-6xl success-icon mb-4"></i>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Verification Successful!</h1>
        <?php else: ?>
            <i class="fas fa-times-circle text-6xl error-icon mb-4"></i>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Verification Failed</h1>
        <?php endif; ?>

        <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($message); ?></p>

        <a href="login.php" class="btn-primary font-bold py-3 px-6 rounded-lg transition-colors duration-300">
            Proceed to Login
        </a>
    </div>
</body>
</html>