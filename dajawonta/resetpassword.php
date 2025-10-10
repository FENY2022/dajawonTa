<?php
// Include your database connection file
include 'db.php';

// Initialize variables
$message = '';
$messageType = 'error'; // Can be 'error' or 'success'
$token = '';
$showForm = false; // Controls if the password form is displayed

// --- 1. HANDLE PAGE LOAD (GET Request) to validate the token ---
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (isset($_GET['token']) && !empty($_GET['token'])) {
        $token = $_GET['token'];

        if ($conn) {
            // Check if the token is valid and not expired
            $sql = "SELECT email FROM users WHERE reset_token = ? AND reset_token_expiry > NOW() LIMIT 1";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("s", $token);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    // Token is valid, show the form
                    $showForm = true;
                } else {
                    $message = "This password reset link is invalid or has expired. Please request a new one.";
                }
                $stmt->close();
            }
        } else {
            $message = "Error: Database connection failed.";
        }
    } else {
        $message = "No reset token provided. The link may be broken.";
    }
}

// --- 2. HANDLE FORM SUBMISSION (POST Request) to update the password ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic validation
    if (empty($token) || empty($password) || empty($confirm_password)) {
        $message = "Please fill in all fields.";
        $showForm = true; // Show form again to correct errors
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $showForm = true;
    } else {
        // Advanced password strength check (server-side)
        $errors = [];
        if (strlen($password) < 8) $errors[] = "at least 8 characters";
        if (!preg_match('/[A-Z]/', $password)) $errors[] = "an uppercase letter";
        if (!preg_match('/[a-z]/', $password)) $errors[] = "a lowercase letter";
        if (!preg_match('/[0-9]/', $password)) $errors[] = "a number";
        if (!preg_match('/[^A-Za-z0-9]/', $password)) $errors[] = "a special character";

        if (!empty($errors)) {
            $message = "Password must contain " . implode(', ', $errors) . ".";
            $showForm = true;
        } else {
            // All validation passed, proceed with database update
            if ($conn) {
                // Re-validate the token before updating
                $sql_check = "SELECT email FROM users WHERE reset_token = ? AND reset_token_expiry > NOW() LIMIT 1";
                if ($stmt_check = $conn->prepare($sql_check)) {
                    $stmt_check->bind_param("s", $token);
                    $stmt_check->execute();
                    $result = $stmt_check->get_result();

                    if ($result->num_rows > 0) {
                        // Token is still valid, update the password
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        
                        // Update password and NULLIFY the reset token to prevent reuse
                        $sql_update = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE reset_token = ?";
                        if ($stmt_update = $conn->prepare($sql_update)) {
                            $stmt_update->bind_param("ss", $hashedPassword, $token);
                            if ($stmt_update->execute()) {
                                $message = "Your password has been reset successfully!";
                                $messageType = "success";
                                $showForm = false; // Hide form on success
                            } else {
                                $message = "Error: Could not update password.";
                                $showForm = true;
                            }
                            $stmt_update->close();
                        }
                    } else {
                        $message = "This password reset link is invalid or has expired. Please request a new one.";
                        $showForm = false; // Hide form if token became invalid
                    }
                    $stmt_check->close();
                }
                $conn->close();
            } else {
                $message = "Error: Database connection failed.";
                $showForm = true;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password - DajawonTa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="icon" type="image/png" href="logo/dajawonta.png">
    <style>
        /* DajawonTa Theme */
        :root {
            --primary: #0D6EFD; /* A strong, professional blue */
            --primary-dark: #0B5ED7;
            --secondary: #6c757d; /* A neutral gray for a professional/industrial feel */
            --success: #198754;
            --danger: #dc3545;
            --warning: #ffc107; /* Yellow accent, like construction equipment */
        }
        body { 
            font-family: 'Inter', sans-serif; 
            background: #e9ecef; 
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
            max-width: 480px;
            border-top: 5px solid var(--primary); /* Blue accent border */
        }
        .card-header { padding: 32px; text-align: center; }
        .card-body { padding: 0 32px 32px; }
        .btn { padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; border: none; display: flex; align-items: center; justify-content: center; width: 100%; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .input-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #64748b; }
        .input-with-icon { padding-left: 40px !important; }
        .password-toggle { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #64748b; cursor: pointer; }
        .password-strength-bar-container { height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden;}
        .password-strength-bar { height: 100%; width: 0%; border-radius: 3px; transition: width 0.3s ease, background-color 0.3s ease; }
        .requirement-list { margin-top: 8px; }
        .requirement-item { display: flex; align-items: center; margin-bottom: 4px; font-size: 13px; color: #64748b; transition: color 0.3s ease; }
        .requirement-item i { margin-right: 8px; font-size: 12px; }
        .requirement-item.valid { color: var(--success); }
    </style>
</head>
<body>

    <div class="card">
        <div class="card-header">
            <img src="logo/dajawonta.png" alt="DajawonTa Logo" class="h-24 w-24 mx-auto mb-4">
            <h1 class="text-2xl font-bold text-gray-800">Set a New Password</h1>
            <p class="text-gray-500 mt-1">Secure your DajawonTa account.</p>
        </div>
        
        <div class="card-body">
            <?php if ($showForm): ?>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" id="reset-password-form">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                    <div class="relative">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon" required>
                        <span class="password-toggle" id="password-toggle"><i class="fa-solid fa-eye"></i></span>
                    </div>
                    <div class="password-strength-bar-container mt-2">
                        <div class="password-strength-bar" id="password-strength-bar"></div>
                    </div>
                    <div class="requirement-list">
                        <div class="requirement-item" id="length-req"><i class="fa-solid fa-circle"></i><span>At least 8 characters</span></div>
                        <div class="requirement-item" id="uppercase-req"><i class="fa-solid fa-circle"></i><span>Contains uppercase letter</span></div>
                        <div class="requirement-item" id="lowercase-req"><i class="fa-solid fa-circle"></i><span>Contains lowercase letter</span></div>
                        <div class="requirement-item" id="number-req"><i class="fa-solid fa-circle"></i><span>Contains number</span></div>
                        <div class="requirement-item" id="special-req"><i class="fa-solid fa-circle"></i><span>Contains special character</span></div>
                    </div>
                </div>

                <div class="mb-6">
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                    <div class="relative">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" id="confirm_password" name="confirm_password" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Reset Password</button>
            </form>
            <?php endif; ?>

            <?php if (!empty($message)): ?>
                <div class="text-center">
                    <?php if ($messageType === 'success'): ?>
                        <i class="fas fa-check-circle text-5xl text-green-500 mx-auto mb-4"></i>
                        <p class="text-lg text-gray-700"><?php echo htmlspecialchars($message); ?></p>
                        <a href="login.php" class="inline-block mt-6 px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 transition-colors">
                            Proceed to Login
                        </a>
                    <?php else: ?>
                        <i class="fas fa-times-circle text-5xl text-red-500 mx-auto mb-4"></i>
                        <p class="text-lg text-gray-700"><?php echo htmlspecialchars($message); ?></p>
                        <a href="forgotpassword.php" class="inline-block mt-6 px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 transition-colors">
                            Try Again
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.getElementById('password-toggle');
        const strengthBar = document.getElementById('password-strength-bar');

        // Requirements
        const reqs = {
            length: document.getElementById('length-req'),
            uppercase: document.getElementById('uppercase-req'),
            lowercase: document.getElementById('lowercase-req'),
            number: document.getElementById('number-req'),
            special: document.getElementById('special-req')
        };

        // --- Password Visibility Toggle ---
        if (passwordToggle) {
            passwordToggle.addEventListener('click', () => {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                passwordToggle.querySelector('i').classList.toggle('fa-eye');
                passwordToggle.querySelector('i').classList.toggle('fa-eye-slash');
            });
        }

        // --- Password Strength Checker ---
        if (passwordInput && strengthBar) {
            passwordInput.addEventListener('input', () => {
                const password = passwordInput.value;
                let strength = 0;
                
                const lengthValid = password.length >= 8;
                const uppercaseValid = /[A-Z]/.test(password);
                const lowercaseValid = /[a-z]/.test(password);
                const numberValid = /[0-9]/.test(password);
                const specialValid = /[^A-Za-z0-9]/.test(password);

                reqs.length.classList.toggle('valid', lengthValid);
                reqs.uppercase.classList.toggle('valid', uppercaseValid);
                reqs.lowercase.classList.toggle('valid', lowercaseValid);
                reqs.number.classList.toggle('valid', numberValid);
                reqs.special.classList.toggle('valid', specialValid);

                if (lengthValid) strength++;
                if (uppercaseValid) strength++;
                if (lowercaseValid) strength++;
                if (numberValid) strength++;
                if (specialValid) strength++;

                const width = (strength / 5) * 100;
                strengthBar.style.width = `${width}%`;
                
                if (strength <= 2) {
                    strengthBar.style.backgroundColor = 'var(--danger)';
                } else if (strength <= 4) {
                    strengthBar.style.backgroundColor = 'var(--warning)';
                } else {
                    strengthBar.style.backgroundColor = 'var(--success)';
                }
            });
        }

        // --- Toastify notification for POST errors ---
        <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($message) && $showForm): ?>
            Toastify({
                text: "<?php echo addslashes($message); ?>",
                duration: 5000,
                close: true,
                gravity: "top",
                position: "right",
                style: { background: "linear-gradient(to right, var(--danger), #b02a37)" },
            }).showToast();
        <?php endif; ?>
    });
    </script>
</body>
</html>