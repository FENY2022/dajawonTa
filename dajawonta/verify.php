<?php
// Include your database connection file
include 'db.php';

$message = '';
$email = '';
$showForm = false;

// Determine if the request is a GET (initial visit) or POST (form submission)
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    // This part runs when the user first visits the page via the email link
    if (isset($_GET['email'])) {
        $email = trim($_GET['email']);

        // Check if the email exists in the database and is unverified
        if ($conn) {
            $sql = "SELECT id FROM users WHERE email = ? AND is_verified = 0";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $showForm = true;
                    $message = "Please enter the 6-digit verification code sent to your email address.";
                } else {
                    $message = "Error: Invalid email or your account is already verified.";
                }
                $stmt->close();
            } else {
                $message = "Error: Could not prepare the initial email check query.";
            }
        } else {
            $message = "Error: Database connection failed.";
        }
    } else {
        $message = "Error: Invalid verification link. Please go to the registration page and try again.";
    }
} else if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // This part runs when the user submits the form
    if (isset($_POST['email']) && isset($_POST['verification_code'])) {
        $email = trim($_POST['email']);
        $verificationCode = trim($_POST['verification_code']);
        $showForm = true; // Keep the form visible in case of an error

        if ($conn) {
            // Prepare the SQL statement to find the user with the submitted data
            $sql = "SELECT id FROM users WHERE email = ? AND verification_code = ? AND is_verified = 0";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ss", $email, $verificationCode);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    // User found, now update their status to verified
                    $updateSql = "UPDATE users SET is_verified = 1, verification_code = NULL WHERE email = ?";
                    if ($updateStmt = $conn->prepare($updateSql)) {
                        $updateStmt->bind_param("s", $email);
                        if ($updateStmt->execute()) {
                            $message = "Success! Your account has been verified. You can now log in.";
                            $showForm = false; // Hide the form on success
                        } else {
                            $message = "Error: Could not update verification status.";
                        }
                        $updateStmt->close();
                    } else {
                        $message = "Error: Could not prepare the update query.";
                    }
                } else {
                    // No matching user found
                    $message = "Error: The verification code is incorrect. Please try again.";
                }
                $stmt->close();
            } else {
                $message = "Error: Could not prepare the validation query.";
            }
        } else {
            $message = "Error: Database connection failed.";
        }
    } else {
        $message = "Error: Please provide an email and verification code.";
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Verification</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { background: white; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); overflow: hidden; width: 100%; max-width: 600px; padding: 32px; text-align: center; }
        .card-header { margin-bottom: 24px; }
        .success-icon { color: #06d6a0; font-size: 6rem; margin-bottom: 16px; }
        .error-icon { color: #ef476f; font-size: 6rem; margin-bottom: 16px; }
        .btn { padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; border: none; display: inline-flex; align-items: center; justify-content: center; background: #4361ee; color: white; margin-top: 24px; text-decoration: none; }
    </style>
</head>
<body>

<?php include 'topbar.php'; ?>

    <div class="card">
        <div class="card-header">
            <?php if (strpos($message, 'Success') !== false): ?>
                <i class="fa-solid fa-circle-check success-icon"></i>
            <?php else: ?>
                <i class="fa-solid fa-circle-xmark error-icon"></i>
            <?php endif; ?>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Account Verification</h1>
            <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($message); ?></p>
        </div>
        
        <?php if ($showForm): ?>
        <form method="POST" action="verify.php" class="flex flex-col items-center">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            <div class="relative w-full max-w-sm">
                <input type="text" name="verification_code" placeholder="Enter your 6-digit code" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-center" required>
            </div>
            <button type="submit" class="btn">Verify Account</button>
        </form>
        <?php endif; ?>

        <?php if (strpos($message, 'Success') !== false): ?>
            <a href="login.php" class="btn">Go to Login <i class="fa-solid fa-arrow-right ml-2"></i></a>
        <?php endif; ?>
    </div>
</body>
</html>