<?php
include 'db.php';
$message = '';
$email = '';
$showForm = false;

// --- Function to send verification email (Copied from Register to allow Resend) ---
function sendVerificationEmail($email, $firstName, $verificationToken) {
    $baseURL = 'http://localhost/dajawonTa/dajawonta'; 
    $confirmationLink = $baseURL . '/confirm.php?token=' . urlencode($verificationToken);
    $subject = 'DajawonTa Account Confirmation Code (Resent)';
    $emailMessage = "Hello " . htmlspecialchars($firstName) . ",\n\n"
                    . "Here is your verification link:\n" . $confirmationLink . "\n\n"
                    . "Or use this code if prompted: " . $verificationToken . "\n\n";

    $emailUrl = 'https://ict-amsos.e-dats.info/sendemail/send.php';
    $queryParams = http_build_query([
        'send' => 1, 'email' => $email, 'Subject' => $subject,
        'message' => $emailMessage, 'yourname' => 'DajawonTa Support'
    ]);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $emailUrl . '?' . $queryParams);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['email'])) {
    $email = trim($_GET['email']);
    if ($conn) {
        $sql = "SELECT id FROM users WHERE email = ? AND is_verified = 0";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $showForm = true;
                $message = "Please enter the verification code sent to your email address.";
            } else {
                $message = "Error: Invalid email or your account is already verified.";
            }
            $stmt->close();
        }
    }
} else if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    // --- RESEND LOGIC ---
    if (isset($_POST['resend_code'])) {
        $showForm = true;
        if ($conn) {
            // Get user name for email
            $sql = "SELECT first_name, verification_code FROM users WHERE email = ? AND is_verified = 0";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    // Reuse existing token or generate new one if needed
                    $token = $row['verification_code'];
                    sendVerificationEmail($email, $row['first_name'], $token);
                    $message = "Verification code resent successfully!";
                } else {
                    $message = "Error: Email not found or already verified.";
                }
                $stmt->close();
            }
        }
    } 
    // --- VERIFY LOGIC ---
    else if (isset($_POST['verification_code'])) {
        $verificationCode = trim($_POST['verification_code']);
        $showForm = true;
        if ($conn) {
            $sql = "SELECT id FROM users WHERE email = ? AND verification_code = ? AND is_verified = 0";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ss", $email, $verificationCode);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $updateSql = "UPDATE users SET is_verified = 1, verification_code = NULL WHERE email = ?";
                    if ($updateStmt = $conn->prepare($updateSql)) {
                        $updateStmt->bind_param("s", $email);
                        if ($updateStmt->execute()) {
                            $message = "Success! Your account has been verified. You can now log in.";
                            $showForm = false; 
                        }
                        $updateStmt->close();
                    }
                } else {
                    $message = "Error: The verification code is incorrect. Please try again.";
                }
                $stmt->close();
            }
        }
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
        .success-icon { color: #06d6a0; font-size: 6rem; margin-bottom: 16px; }
        .error-icon { color: #ef476f; font-size: 6rem; margin-bottom: 16px; }
        .btn { padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; border: none; display: inline-flex; align-items: center; justify-content: center; background: #4361ee; color: white; margin-top: 10px; text-decoration: none; width: 100%; }
        .btn-outline { background: transparent; border: 1px solid #4361ee; color: #4361ee; margin-top: 10px; width: 100%; }
    </style>
</head>
<body>
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
        
        <form method="POST" action="verify.php" class="flex flex-col items-center mt-2">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            <input type="hidden" name="resend_code" value="1">
            <div class="relative w-full max-w-sm">
                <button type="submit" class="btn btn-outline">Resend Code</button>
            </div>
        </form>
        <?php endif; ?>

        <?php if (strpos($message, 'Success') !== false): ?>
            <a href="login.php" class="btn">Go to Login <i class="fa-solid fa-arrow-right ml-2"></i></a>
        <?php endif; ?>
    </div>
</body>
</html>