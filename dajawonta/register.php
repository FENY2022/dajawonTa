<?php
// --- STEP 1: Enable detailed error reporting for debugging ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include your database connection file
include "db.php"; 

// Initialize variables with empty values or POST values if they exist
$firstName = $_POST['first_name'] ?? '';
$middleName = $_POST['middle_name'] ?? '';
$lastName = $_POST['last_name'] ?? '';
$suffix = $_POST['suffix'] ?? '';
$gender = $_POST['gender'] ?? '';
$birthday = $_POST['birthday'] ?? '';
$contactNumber = $_POST['contact_number'] ?? '';
$role = $_POST['role'] ?? '';
$province = $_POST['province'] ?? '';
$municipality = $_POST['city'] ?? '';
$barangay = $_POST['barangay'] ?? '';
$purok = $_POST['purok'] ?? '';
$email = $_POST['email'] ?? '';
// Note: Passwords are not repopulated for security reasons

$message = '';
$redirectEmail = '';
$errorStep = 0; // Variable to track which step to return to

// --- Function to send verification email ---
function sendVerificationEmail($email, $firstName, $verificationToken) {
    $baseURL = 'http://localhost/dajawonTa/dajawonta'; // UPDATE THIS TO LIVE URL
    $confirmationLink = $baseURL . '/confirm.php?token=' . urlencode($verificationToken);

    $subject = 'DajawonTa Account Confirmation';
    $emailMessage = "Hello " . htmlspecialchars($firstName) . ",\n\n"
                    . "Thank you for registering with DajawonTa. Please click the link below to activate your account:\n\n"
                    . $confirmationLink . "\n\n"
                    . "If you did not register for an account, please ignore this email.";

    $emailUrl = 'https://ict-amsos.e-dats.info/sendemail/send.php';
    $queryParams = http_build_query([
        'send' => 1,
        'email' => $email,
        'Subject' => $subject,
        'message' => $emailMessage,
        'yourname' => 'DajawonTa Support'
    ]);
    
    // cURL logic...
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $emailUrl . '?' . $queryParams);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!$conn) {
        $message = "Error: Database connection failed. Check your db.php file.";
    } elseif ($conn->connect_error) {
        $message = "Error: Database connection failed: " . $conn->connect_error;
    } else {
        
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $verificationToken = bin2hex(random_bytes(32));

        $userRules = 0; 
        if ($role === 'provider') { 
            $userRules = 1;
        } elseif ($role === 'client') { 
            $userRules = 0;
        }

        // Check if email exists
        $sql_check = "SELECT first_name, is_verified FROM users WHERE email = ? LIMIT 1";
        if ($stmt_check = $conn->prepare($sql_check)) {
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $result = $stmt_check->get_result();

            if ($result->num_rows > 0) {
                $message = "Error: This email is already registered.";
                $errorStep = 4; // Set step to Credentials if email error
            } else {
                
                $sql_insert = "INSERT INTO users (first_name, middle_name, last_name, suffix, gender, birthday, phone_number, email, region, province, municipality, barangay, purok, role, password, verification_code, user_rules) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $region = 'Caraga';

                if ($stmt_insert = $conn->prepare($sql_insert)) {
                    $stmt_insert->bind_param("ssssssssssssssssi", 
                        $firstName, $middleName, $lastName, $suffix, $gender, $birthday,
                        $contactNumber, $email, $region, $province, $municipality,
                        $barangay, $purok, $role, $password, $verificationToken, $userRules
                    );
                    
                    if ($stmt_insert->execute()) {
                        sendVerificationEmail($email, $firstName, $verificationToken);
                        $message = "Registration successful! A confirmation link has been sent to your email.";
                        $redirectEmail = $email; 
                    } else {
                        $message = "Error: Could not save your registration. " . $stmt_insert->error;
                        $errorStep = 4;
                    }
                    $stmt_insert->close();
                } else {
                    $message = "Error: Query preparation failed: " . $conn->error;
                }
            }
            $stmt_check->close();
        } else {
            $message = "Error: Email check failed: " . $conn->error;
        }
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DajawonTa - User Registration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.tailwindcss.com/3.4.1?plugins=typography"></script> 
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Roboto+Slab:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <style>
        /* [Existing CSS remains exactly the same] */
        :root {--primary: #1976D2; --primary-dark: #0D47A1; --secondary: #FFC107; --success: #28a745; --danger: #dc3545; --warning: #FFC107; --light: #f4f6f9; --dark: #212529; --gray: #6c757d;}body{font-family: 'Roboto', sans-serif; background-color: var(--light); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;}h1, h2, h3, .card-header h1 {font-family: 'Roboto Slab', serif;}.card{background:white;border-radius:16px;box-shadow:0 20px 25px -5px rgba(0,0,0,0.1),0 10px 10px -5px rgba(0,0,0,0.04);overflow:hidden;width:100%;max-width:1000px}.card-header{background-color:var(--primary);color:white;padding:24px 32px}.card-body{padding:32px}.form-step{display:none}.form-step.active{display:block;animation:fadeIn 0.5s ease-in-out}.progress-bar{display:flex;margin-bottom:32px;justify-content:space-between;position:relative}.progress-bar::before{content:'';position:absolute;top:50%;left:0;transform:translateY(-50%);height:4px;width:100%;background:#e2e8f0;z-index:1}.progress-bar::after{content:'';position:absolute;top:50%;left:0;transform:translateY(-50%);height:4px;width:var(--progress-width,0%);background:var(--primary);z-index:1;transition:width 0.5s ease}.step{width:40px;height:40px;border-radius:50%;background:white;display:flex;align-items:center;justify-content:center;border:2px solid #e2e8f0;z-index:2;font-weight:600;color:var(--gray);position:relative}.step.active{background:var(--primary);color:white;border-color:var(--primary)}.step.completed{background:var(--success);color:white;border-color:var(--success)}.step-label{position:absolute;top:100%;left:50%;transform:translateX(-50%);margin-top:8px;font-size:12px;font-weight:500;color:var(--gray);white-space:nowrap}.step.active .step-label{color:var(--primary);font-weight:600}.btn{padding:12px 24px;border-radius:8px;font-weight:600;cursor:pointer;transition:all 0.3s ease;border:none;display:inline-flex;align-items:center;justify-content:center}.btn-primary{background:var(--primary);color:white}.btn-primary:hover{background:var(--primary-dark)}.btn-outline{background:transparent;color:var(--primary);border:1px solid var(--primary)}.btn-outline:hover{background:rgba(25, 118, 210, 0.05)}.password-toggle{position:absolute;right:12px;top:50%;transform:translateY(-50%);color:var(--gray);cursor:pointer}.loader{border:3px solid #f3f3f3;border-radius:50%;border-top:3px solid var(--primary);width:20px;height:20px;animation:spin 1s linear infinite;display:inline-block;margin-left:8px}@keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}@keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}.input-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray)}.input-with-icon{padding-left:40px !important}.suggestion-item{padding:12px 16px;cursor:pointer;border-bottom:1px solid #f1f5f9;display:flex;align-items:center}.suggestion-item:hover{background:#f8fafc}.suggestion-item i{margin-right:12px;color:var(--primary)}.password-strength{height:6px;margin-top:8px;border-radius:3px;background:#e2e8f0;overflow:hidden}.password-strength-bar{height:100%;width:0%;transition:width 0.3s ease;border-radius:3px}.requirement-list{margin-top:8px}.requirement-item{display:flex;align-items:center;margin-bottom:4px;font-size:13px;color:var(--gray)}.requirement-item i{margin-right:8px;font-size:12px}.requirement-item.valid{color:var(--success)}.role-selection{display:flex;gap:16px;margin-top:16px}.role-card{flex:1;padding:16px;border:2px solid #e2e8f0;border-radius:8px;cursor:pointer;transition:all 0.3s ease;text-align:center}.role-card i{font-size:24px;margin-bottom:8px;color:var(--gray)}.role-card.selected{border-color:var(--primary);background:rgba(25, 118, 210, 0.05);color:var(--primary)}.role-card.selected i{color:var(--primary)}@media (max-width:768px){.card{border-radius:12px}.card-body{padding:24px}.progress-bar{margin-bottom:24px}.step-label{display:none}}
        /* Small Fix for Flatpickr z-index if needed */
        .flatpickr-calendar { z-index: 9999 !important; }
    </style>
</head>
<body>

<?php include 'topbar.php'; ?>

    <div class="card">
        <div class="card-header">
            <div class="flex flex-col items-center justify-center mb-2">
                <img src="logo/dajawonta.png" alt="DajawonTa Logo" class="h-20 w-20 mb-2">
                <div class="flex items-center justify-center">
                    <i class="fas fa-tools h-8 w-8 mr-3 text-4xl flex items-center justify-center"></i>
                    <h1 class="text-3xl font-bold text-white">DajawonTa</h1>
                </div>
            </div>
            <p class="text-blue-100 text-center">Create an account to connect with local service professionals</p>
        </div>
        
        <div class="card-body">
            <div class="progress-bar">
                <div class="step active" id="step1"><span>1</span><span class="step-label">Agreement</span></div>
                <div class="step" id="step2"><span>2</span><span class="step-label">Personal Info</span></div>
                <div class="step" id="step3"><span>3</span><span class="step-label">Address</span></div>
                <div class="step" id="step4"><span>4</span><span class="step-label">Credentials</span></div>
            </div>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" id="registration-form">
                
                <div class="form-step active" id="form-step-1">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Data Privacy Agreement</h2>
                    <div class="prose prose-sm max-w-none h-64 overflow-y-auto border p-4 rounded-lg bg-gray-50 mb-6">
                        <h4>1. Introduction</h4><p>Welcome to DajawonTa. We are committed to protecting your privacy. This Data Privacy Agreement explains how we collect, use, disclose, and safeguard your information when you use our services.</p>
                        <h4>2. Information We Collect</h4><p>We may collect personal identification information from you in various ways, including, but not limited to, when you register on the site, book a service, or fill out a form. The information we collect includes:</p><ul><li><strong>Personal Data:</strong> Your name, email address, contact number, and address.</li><li><strong>Service Information:</strong> Details related to the services you book or offer.</li></ul>
                        <h4>3. How We Use Your Information</h4><p>We use the information we collect to:</p><ul><li>Provide, operate, and maintain our service booking platform.</li><li>Process your transactions and manage your service bookings.</li><li>Communicate with you, including sending verification codes, booking reminders, and promotional materials.</li><li>Improve our website and services.</li></ul>
                        <h4>4. Your Consent</h4><p>By using our services and providing us with your personal information, you consent to the collection, use, and processing of your data as described in this agreement. You have the right to withdraw your consent at any time by contacting us.</p>
                        <p class="font-semibold">By checking the box below, you confirm that you have read, understood, and agree to the terms outlined in this Data Privacy Agreement.</p>
                    </div>
                    <div class="flex items-center"><input type="checkbox" id="privacy-agreement" name="privacy_agreement" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"><label for="privacy-agreement" class="ml-2 block text-sm text-gray-900">I have read and agree to the Data Privacy Agreement.</label></div>
                    <div class="flex justify-end mt-8"><button type="button" class="btn btn-primary next-step" data-next="2">Continue <i class="fa-solid fa-arrow-right ml-2"></i></button></div>
                </div>
                
                <div class="form-step" id="form-step-2">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Personal Information & Role</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="relative"><label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label><div class="relative"><i class="fa-solid fa-user input-icon"></i>
                        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($firstName); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon" required></div></div>
                        
                        <div class="relative"><label for="middle_name" class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label><div class="relative"><i class="fa-solid fa-user input-icon"></i>
                        <input type="text" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($middleName); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon"></div></div>
                        
                        <div class="relative"><label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label><div class="relative"><i class="fa-solid fa-user input-icon"></i>
                        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($lastName); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon" required></div></div>
                        
                        <div class="relative"><label for="suffix" class="block text-sm font-medium text-gray-700 mb-1">Suffix</label><div class="relative"><i class="fa-solid fa-user-tag input-icon"></i>
                        <input type="text" id="suffix" name="suffix" value="<?php echo htmlspecialchars($suffix); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon"></div></div>
                        
                        <div class="relative"><label for="birthday" class="block text-sm font-medium text-gray-700 mb-1">Birthday</label><div class="relative"><i class="fa-solid fa-cake-candles input-icon z-10"></i>
                        <input type="text" id="birthday" name="birthday" value="<?php echo htmlspecialchars($birthday); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon bg-white" placeholder="Select Date" required></div></div>
                        
                        <div class="relative"><label for="contact_number" class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label><div class="relative"><i class="fa-solid fa-phone input-icon"></i>
                        <input type="tel" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($contactNumber); ?>" placeholder="09xxxxxxxxx" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon" required></div></div>
                    </div>
                    
                    <div class="mt-6"><label class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                    <div class="flex items-center space-x-6">
                        <label for="gender-male" class="flex items-center cursor-pointer"><input type="radio" id="gender-male" name="gender" value="Male" <?php if($gender=='Male') echo 'checked'; ?> class="h-4 w-4 text-blue-600" required><span class="ml-2 text-gray-700">Male</span></label>
                        <label for="gender-female" class="flex items-center cursor-pointer"><input type="radio" id="gender-female" name="gender" value="Female" <?php if($gender=='Female') echo 'checked'; ?> class="h-4 w-4 text-blue-600"><span class="ml-2 text-gray-700">Female</span></label>
                        <label for="gender-other" class="flex items-center cursor-pointer"><input type="radio" id="gender-other" name="gender" value="Other" <?php if($gender=='Other') echo 'checked'; ?> class="h-4 w-4 text-blue-600"><span class="ml-2 text-gray-700">Other</span></label>
                    </div></div>
                    
                    <div class="mt-6"><label class="block text-sm font-medium text-gray-700 mb-2">Choose Your Role</label>
                        <div class="role-selection">
                            <label for="role-client" class="role-card <?php if($role=='client') echo 'selected'; ?>"><i class="fa-solid fa-user"></i><div>Local Client</div><input type="radio" id="role-client" name="role" value="client" class="hidden" required <?php if($role=='client') echo 'checked'; ?>></label>
                            <label for="role-provider" class="role-card <?php if($role=='provider') echo 'selected'; ?>"><i class="fa-toolbox fa-solid"></i> <div>Service Provider</div><input type="radio" id="role-provider" name="role" value="provider" class="hidden" <?php if($role=='provider') echo 'checked'; ?>></label>
                        </div>
                    </div>
                    <div class="flex justify-between mt-8"><button type="button" class="btn btn-outline prev-step" data-prev="1"><i class="fa-solid fa-arrow-left mr-2"></i> Back</button><button type="button" class="btn btn-primary next-step" data-next="3">Continue <i class="fa-solid fa-arrow-right ml-2"></i></button></div>
                </div>
                
                <div class="form-step" id="form-step-3">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Address Information</h2>
                    <div class="relative mb-6"><label for="address-search" class="block text-sm font-medium text-gray-700 mb-1">Search for an address</label><div class="relative"><i class="fa-solid fa-magnifying-glass input-icon"></i><input type="text" id="address-search" placeholder="Search for an address in Caraga Region" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon"><div id="address-spinner" class="loader absolute right-3 top-3 hidden"></div></div><div id="address-suggestions" class="absolute z-10 w-full bg-white border border-gray-300 rounded-lg mt-1 shadow-xl max-h-60 overflow-y-auto hidden"></div></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div><label for="purok" class="block text-sm font-medium text-gray-700 mb-1">Purok / Street</label><div class="relative"><i class="fa-solid fa-road input-icon"></i>
                        <input type="text" id="purok" name="purok" value="<?php echo htmlspecialchars($purok); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon"></div></div>
                        
                        <div><label for="barangay" class="block text-sm font-medium text-gray-700 mb-1">Barangay</label><div class="relative"><i class="fa-solid fa-map-marker-alt input-icon"></i>
                        <input type="text" id="barangay" name="barangay" value="<?php echo htmlspecialchars($barangay); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon" required></div></div>
                        
                        <div><label for="city" class="block text-sm font-medium text-gray-700 mb-1">Municipality / City</label><div class="relative"><i class="fa-solid fa-city input-icon"></i>
                        <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($municipality); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon" required></div></div>
                        
                        <div><label for="province" class="block text-sm font-medium text-gray-700 mb-1">Province</label><div class="relative"><i class="fa-solid fa-map-location-dot input-icon"></i>
                        <input type="text" id="province" name="province" value="<?php echo htmlspecialchars($province); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon" required></div></div>
                        
                        <input type="hidden" id="street" name="street"><input type="hidden" id="postal_code" name="postal_code"><input type="hidden" id="country" name="country">
                    </div>
                    <div class="flex justify-between mt-8"><button type="button" class="btn btn-outline prev-step" data-prev="2"><i class="fa-solid fa-arrow-left mr-2"></i> Back</button><button type="button" class="btn btn-primary next-step" data-next="4">Continue <i class="fa-solid fa-arrow-right ml-2"></i></button></div>
                </div>
                
                <div class="form-step" id="form-step-4">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Account Credentials</h2>
                    <div class="grid grid-cols-1 gap-6">
                        <div><label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label><div class="relative"><i class="fa-solid fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon" required></div></div>
                        
                        <div class="relative"><label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label><div class="relative"><i class="fa-solid fa-lock input-icon"></i><input type="password" id="password" name="password" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon" required><span class="password-toggle" id="password-toggle"><i class="fa-solid fa-eye"></i></span></div><div class="password-strength mt-2"><div class="password-strength-bar" id="password-strength-bar"></div></div><div class="requirement-list"><div class="requirement-item" id="length-req"><i class="fa-solid fa-circle"></i><span>At least 8 characters</span></div><div class="requirement-item" id="uppercase-req"><i class="fa-solid fa-circle"></i><span>Contains uppercase letter</span></div><div class="requirement-item" id="lowercase-req"><i class="fa-solid fa-circle"></i><span>Contains lowercase letter</span></div><div class="requirement-item" id="number-req"><i class="fa-solid fa-circle"></i><span>Contains number</span></div><div class="requirement-item" id="special-req"><i class="fa-solid fa-circle"></i><span>Contains special character</span></div></div></div>
                        <div class="relative"><label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label><div class="relative"><i class="fa-solid fa-lock input-icon"></i><input type="password" id="confirm_password" name="confirm_password" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent input-with-icon" required><span class="password-toggle" id="confirm-password-toggle"><i class="fa-solid fa-eye"></i></span></div></div>
                    </div>
                    <div class="flex justify-between mt-8"><button type="button" class="btn btn-outline prev-step" data-prev="3"><i class="fa-solid fa-arrow-left mr-2"></i> Back</button><button type="submit" class="btn btn-primary" id="submit-btn">Register <i class="fa-solid fa-user-plus ml-2"></i></button></div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
    // ... [Previous JS code for form steps, validation, etc. goes here] ...
    const formSteps=document.querySelectorAll(".form-step"),steps=document.querySelectorAll(".step"),nextButtons=document.querySelectorAll(".next-step"),prevButtons=document.querySelectorAll(".prev-step"),progressBar=document.querySelector(".progress-bar");let currentStep=1;
    
    // --- NEW: LOGIC TO RESTORE STEP ON ERROR ---
    const serverErrorStep = <?php echo $errorStep; ?>;
    function jumpToStep(step) {
        formSteps.forEach(t=>t.classList.remove("active"));
        document.getElementById(`form-step-${step}`).classList.add("active");
        currentStep = step;
        steps.forEach((s,t)=>{t+1<currentStep?(s.classList.add("completed"),s.classList.remove("active")):t+1===currentStep?(s.classList.add("active"),s.classList.remove("completed")):(s.classList.remove("active","completed"),s.classList.remove("completed"))});
        const e=(currentStep-1)/(steps.length-1)*100;
        progressBar.style.setProperty("--progress-width",`${e}%`);
    }
    if (serverErrorStep > 0) { jumpToStep(serverErrorStep); }

    function updateProgress(){const e=(currentStep-1)/(steps.length-1)*100;progressBar.style.setProperty("--progress-width",`${e}%`),steps.forEach((s,t)=>{t+1<currentStep?(s.classList.add("completed"),s.classList.remove("active")):t+1===currentStep?(s.classList.add("active"),s.classList.remove("completed")):(s.classList.remove("active","completed"),s.classList.remove("completed"))})}function validateStep(e){let s=!0;if(1===e){const t=document.getElementById("privacy-agreement");t.checked||(showToast("You must agree to the Data Privacy Agreement.","error"),s=!1)}else if(2===e){["first_name","last_name","contact_number","birthday"].forEach(t=>{const o=document.getElementById(t);o.value.trim()?(clearError(o),clearError(o)):(showError(o,`${o.previousElementSibling.innerText.replace("*","")} is required.`),s=!1)});const t=document.getElementById("contact_number");t.value.trim()&&!/^09\d{9}$/.test(t.value.trim())&&(showError(t,"Enter a valid 11-digit mobile number (e.g., 09xxxxxxxxx)."),s=!1),document.querySelector('input[name="gender"]:checked')||(showToast("Please select a gender.","error"),s=!1),document.querySelector('input[name="role"]:checked')||(showToast("Please select a role (Client or Provider).","error"),s=!1)}else 3===e&&["barangay","city","province"].forEach(t=>{const o=document.getElementById(t);o.value.trim()?clearError(o):(showError(o,`${o.previousElementSibling.innerText.replace("*","")} is required.`),s=!1)});return s}function showError(e,s){const t=e.closest(".relative");let o=t.querySelector(".text-red-500");o||(o=document.createElement("div"),o.className="text-red-500 text-xs mt-1 pl-1",t.appendChild(o)),o.innerText=s,e.classList.add("border-red-500")}function clearError(e){const s=e.closest(".relative"),t=s.querySelector(".text-red-500");t&&s.removeChild(t),e.classList.remove("border-red-500")}function showToast(e,s="info"){Toastify({text:e,duration:3500,gravity:"top",position:"right",style:{background:{error:"linear-gradient(to right, #dc3545, #b02a37)",success:"linear-gradient(to right, #28a745, #218838)",info:"linear-gradient(to right, #1976D2, #0D47A1)"}[s]}}).showToast()}nextButtons.forEach(e=>{e.addEventListener("click",()=>{const s=parseInt(e.getAttribute("data-next"));validateStep(currentStep)&&(formSteps.forEach(t=>t.classList.remove("active")),document.getElementById(`form-step-${s}`).classList.add("active"),currentStep=s,updateProgress())})}),prevButtons.forEach(e=>{e.addEventListener("click",()=>{const s=parseInt(e.getAttribute("data-prev"));formSteps.forEach(t=>t.classList.remove("active")),document.getElementById(`form-step-${s}`).classList.add("active"),currentStep=s,updateProgress()})}),document.querySelectorAll(".role-card").forEach(e=>{e.addEventListener("click",()=>{document.querySelectorAll(".role-card").forEach(s=>s.classList.remove("selected")),e.classList.add("selected"),e.querySelector('input[type="radio"]').checked=!0})});const searchInput=document.getElementById("address-search"),suggestionsContainer=document.getElementById("address-suggestions"),spinner=document.getElementById("address-spinner"),geoapifyKey="1bb88d414e5c47c89903f9a886f54d1d",streetInput=document.getElementById("street"),purokInput=document.getElementById("purok"),barangayInput=document.getElementById("barangay"),cityInput=document.getElementById("city"),provinceInput=document.getElementById("province"),postalCodeInput=document.getElementById("postal_code"),countryInput=document.getElementById("country");let debounceTimer;function fillAddressFields(e){const s=e.properties;purokInput.value=(s.street||"").trim(),barangayInput.value=s.suburb||"",cityInput.value=s.city||"",provinceInput.value=s.state||"",streetInput.value=(s.street||"").trim(),postalCodeInput.value=s.postcode||"",countryInput.value=s.country||""}searchInput.addEventListener("input",()=>{clearTimeout(debounceTimer),spinner.classList.remove("hidden"),debounceTimer=setTimeout(()=>{fetch(`https://api.geoapify.com/v1/geocode/autocomplete?text=${encodeURIComponent(searchInput.value)}&filter=countrycode:ph&bias=proximity:125.7,9.2&apiKey=${geoapifyKey}`).then(e=>e.json()).then(e=>{spinner.classList.add("hidden"),suggestionsContainer.innerHTML="",e.features&&e.features.length>0?(suggestionsContainer.classList.remove("hidden"),e.features.forEach(s=>{const t=document.createElement("div");t.className="suggestion-item",t.innerHTML=`<i class="fa-solid fa-location-dot"></i><div>${s.properties.formatted}</div>`,t.addEventListener("click",()=>{fillAddressFields(s),suggestionsContainer.classList.add("hidden"),searchInput.value=s.properties.formatted}),suggestionsContainer.appendChild(t)})):suggestionsContainer.classList.add("hidden")}).catch(e=>{spinner.classList.add("hidden"),console.error("Error fetching address data:",e)})},500)}),document.addEventListener("click",function(e){suggestionsContainer.contains(e.target)||e.target===searchInput||suggestionsContainer.classList.add("hidden")});const passwordToggle=document.getElementById("password-toggle"),passwordInput=document.getElementById("password");passwordToggle.addEventListener("click",()=>{passwordInput.type="password"===passwordInput.type?"text":"password",passwordToggle.innerHTML=`<i class="fa-solid ${"password"===passwordInput.type?"fa-eye":"fa-eye-slash"}"></i>`});const confirmPasswordToggle=document.getElementById("confirm-password-toggle"),confirmPasswordInput=document.getElementById("confirm_password");confirmPasswordToggle.addEventListener("click",()=>{confirmPasswordInput.type="password"===confirmPasswordInput.type?"text":"password",confirmPasswordToggle.innerHTML=`<i class="fa-solid ${"password"===confirmPasswordInput.type?"fa-eye":"fa-eye-slash"}"></i>`});const strengthBar=document.getElementById("password-strength-bar"),requirements={length:document.getElementById("length-req"),uppercase:document.getElementById("uppercase-req"),lowercase:document.getElementById("lowercase-req"),number:document.getElementById("number-req"),special:document.getElementById("special-req")};function checkPasswordStrength(){const e=passwordInput.value;let s=0;const t={length:e.length>=8,uppercase:/[A-Z]/.test(e),lowercase:/[a-z]/.test(e),number:/[0-9]/.test(e),special:/[^A-Za-z0-9]/.test(e)};for(const o in t){const a=requirements[o];t[o]?(s+=20,a.classList.add("valid"),a.querySelector("i").className="fa-solid fa-check-circle"):(a.classList.remove("valid"),a.querySelector("i").className="fa-solid fa-circle")}strengthBar.style.width=`${s}%`,s<40?strengthBar.style.backgroundColor="var(--danger)":s<80?strengthBar.style.backgroundColor="var(--warning)":strengthBar.style.backgroundColor="var(--success)"}passwordInput.addEventListener("input",checkPasswordStrength),document.getElementById("registration-form").addEventListener("submit",function(e){passwordInput.value!==confirmPasswordInput.value?(e.preventDefault(),showError(confirmPasswordInput,"Passwords do not match.")):clearError(confirmPasswordInput),Object.values(requirements).some(s=>!s.classList.contains("valid"))&&(e.preventDefault(),showToast("Password does not meet all requirements.","error"))}),updateProgress();
    
    // --- ADDED: Initialize Flatpickr ---
    flatpickr("#birthday", {
        dateFormat: "Y-m-d", // Format for the database
        altInput: true,      // Show a different format to user
        altFormat: "F j, Y", // e.g., "October 25, 2000"
        maxDate: "today",    // Can't be born in the future
        disableMobile: "true" // Force the nice UI even on mobile devices
    });
    </script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <?php if (!empty($message)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const isError = <?php echo (strpos(strtolower($message), 'error') !== false) ? 'true' : 'false'; ?>;
            const msg = "<?php echo addslashes($message); ?>";
            showToast(msg, isError ? 'error' : 'success');
        });
    </script>
    <?php endif; ?>

</body>
</html>