<?php
// Set your default timezone to prevent time-related errors
date_default_timezone_set('Asia/Manila');

// Include your database connection file
include 'db.php';

// Initialize variables
$message = '';
$messageType = 'error'; // Can be 'error' or 'success'

// --- Function to send reset email ---
function sendResetEmail($email, $firstName, $resetToken) {
    // IMPORTANT: Update this URL to your actual domain or local project path
    $resetLink = 'http://localhost/dajawonta/resetpassword.php?token=' . urlencode($resetToken);

    $subject = 'DajawonTa Password Reset Request';
    $emailMessage = "Hello " . htmlspecialchars($firstName) . ",\n\n";
    $emailMessage .= "We received a request to reset your password for your DajawonTa account. Please click the link below to set a new password:\n";
    $emailMessage .= $resetLink . "\n\n";
    $emailMessage .= "If you did not request this, please ignore this email. This link is valid for 1 hour.\n\n";
    $emailMessage .= "Thank you,\nThe DajawonTa Team";

    // This uses an external email sending service. For a real application,
    // consider a more robust solution like PHPMailer.
    $emailUrl = 'https://ict-amsos.e-dats.info/sendemail/send.php';

    $queryParams = http_build_query([
        'send' => 1,
        'email' => $email,
        'Subject' => $subject,
        'message' => $emailMessage,
        'yourname' => 'DajawonTa Support'
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $emailUrl . '?' . $queryParams);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
}

// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } else {
        if ($conn) {
            $sql = "SELECT first_name, is_verified FROM users WHERE email = ? LIMIT 1";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    // We only send a reset link if the user is verified.
                    if ($user['is_verified'] == 1) {
                        $token = bin2hex(random_bytes(32));
                        $expiry = date("Y-m-d H:i:s", time() + 3600); // Token is valid for 1 hour

                        $update_sql = "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?";
                        if ($update_stmt = $conn->prepare($update_sql)) {
                            $update_stmt->bind_param("sss", $token, $expiry, $email);
                            $update_stmt->execute();
                            $update_stmt->close();
                            sendResetEmail($email, $user['first_name'], $token);
                        }
                    }
                }
                $stmt->close();
            }
            $conn->close();

            // IMPORTANT: A generic success message is shown regardless of whether the email was found or not.
            // This is a security measure to prevent attackers from guessing which emails are registered.
            $message = "If an account with that email exists, a password reset link has been sent.";
            $messageType = 'success';
        } else {
            $message = "Error: Database connection failed.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - DajawonTa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Roboto+Slab:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="icon" type="image/png" href="logo/dajawonta.png">
    <style>
        :root { 
            /* Logo-based Theme Colors */
            --primary: #1d4ed8; /* Blue from logo roof */
            --primary-dark: #1e40af; /* Darker blue for hover */
            --secondary: #f59e0b; /* Yellow/Orange from tool handles */
            --text-dark: #1f2937; /* Dark gray to match logo text */
        }
        body { 
            font-family: 'Inter', sans-serif; 
            background: #e5e7eb; /* A simple light gray background */
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 20px; 
        }
        .card { 
            background: white; 
            border-radius: 16px; 
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); 
            width: 100%; 
            max-width: 450px;
            overflow: hidden; /* To contain the border-top */
            border-top: 6px solid var(--primary);
        }
        .card-logo {
            padding-top: 32px;
            padding-bottom: 16px;
            display: flex;
            justify-content: center;
        }
        .card-logo img {
            width: 120px; /* Adjust size as needed */
            height: 120px;
        }
        .card-header { 
            text-align: center;
            padding: 0 24px 24px 24px;
        }
        .card-header h1 {
            font-family: 'Roboto Slab', serif; /* Bolder, blocky font to match logo */
            color: var(--text-dark);
            font-weight: 700;
        }
        .card-body { padding: 0 32px 32px 32px; }
        .btn { 
            padding: 12px 24px; 
            border-radius: 8px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            border: none; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            width: 100%; 
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .input-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #6b7280; }
        .input-with-icon { padding-left: 40px !important; }
        .loader { border: 3px solid #f3f3f3; border-radius: 50%; border-top: 3px solid var(--primary); width: 20px; height: 20px; animation: spin 1s linear infinite; display: none; margin-left: 8px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

<?php include 'topbar.php'; ?>

    <div class="card">
        <div class="card-logo">
            <img src="logo/1757568637.png" alt="DajawonTa Logo">
        </div>

        <div class="card-header">
            <h1 class="text-3xl">Forgot Password?</h1>
            <p class="text-gray-500 text-sm mt-2">Enter your email to receive a reset link.</p>
        </div>
        
        <div class="card-body">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" id="forgot-password-form">
                <div class="mb-6">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <div class="relative">
                        <i class="fa-solid fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon" placeholder="you@example.com" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" id="submit-btn">
                    <span>Send Reset Link</span>
                    <div class="loader" id="loader"></div>
                </button>
            </form>
            <div class="text-center mt-6">
                <a href="login.php" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                    <i class="fa-solid fa-arrow-left mr-1"></i> Back to Login
                </a>
            </div>
        </div>
    </div>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        document.getElementById('forgot-password-form').addEventListener('submit', function() {
            document.getElementById('submit-btn').disabled = true;
            document.getElementById('submit-btn').querySelector('span').innerText = 'Sending...';
            document.getElementById('loader').style.display = 'inline-block';
        });

        <?php if (!empty($message)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const messageType = "<?php echo $messageType; ?>";
            Toastify({
                text: "<?php echo addslashes($message); ?>",
                duration: 5000, close: true, gravity: "top", position: "right", stopOnFocus: true,
                style: {
                    background: messageType === 'success'
                        // Using the accent yellow from the logo for success messages
                        ? "linear-gradient(to right, #F59E0B, #FBBF24)"
                        // Standard red for error
                        : "linear-gradient(to right, #ef476f, #d90429)",
                },
            }).showToast();
        });
        <?php endif; ?>
    </script>
</body>
</html>