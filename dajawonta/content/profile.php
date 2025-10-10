<?php
session_start();
// Make sure you have a db.php file with your database connection details
require_once '../db.php'; 

// Check if user is logged in, otherwise redirect to the login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user = null;

// Prepare and execute the query to fetch user's data based on the new table structure
$stmt = $conn->prepare("SELECT first_name, middle_name, last_name, suffix, gender, birthday, phone_number, email, region, province, municipality, barangay, purok, role, created_at, user_rules, profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    // If for some reason the user ID in the session doesn't exist in the DB, handle the error
    echo "Error: User not found.";
    exit;
}
$stmt->close();
$conn->close();

// --- Variable Assignments from Database ---

// Construct full name, including middle initial and suffix if they exist
$full_name = htmlspecialchars($user['first_name']);
if (!empty($user['middle_name'])) {
    $full_name .= ' ' . htmlspecialchars(substr($user['middle_name'], 0, 1)) . '.';
}
$full_name .= ' ' . htmlspecialchars($user['last_name']);
if (!empty($user['suffix'])) {
    $full_name .= ' ' . htmlspecialchars($user['suffix']);
}

// Determine user role for display
$user_role = ucfirst(htmlspecialchars($user['role'])); // 'Client' or 'Provider'
if ($user['user_rules'] == 2) {
    $user_role = 'Administrator';
}

// --- Profile Picture Logic ---
if (!empty($user['profile_image'])) {
    $profile_pic_path = "uploads/profile_pictures/" . htmlspecialchars($user['profile_image']);
    $profile_pic = file_exists($profile_pic_path) ? $profile_pic_path : 'https://i.pravatar.cc/150?u=' . htmlspecialchars($user['email']);
} else {
    $profile_pic = 'https://i.pravatar.cc/150?u=' . htmlspecialchars($user['email']);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - DajawonTa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0D6EFD; /* DajawonTa Blue */
            --primary-dark: #0B5ED7;
            --gray-100: #f1f5f9;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
        }
        .form-input {
            width: 100%; padding: 0.75rem; border-radius: 8px;
            border: 1px solid #cbd5e1; background-color: #f8fafc;
            transition: all 0.2s ease;
        }
        .form-input:focus {
            outline: none; border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.2);
        }
        .btn {
            padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600;
            cursor: pointer; transition: all 0.2s ease;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .btn-primary { background-color: var(--primary); color: white; }
        .btn-primary:hover { background-color: var(--primary-dark); }
        .btn-secondary {
            background-color: var(--gray-100); color: var(--gray-800);
            border: 1px solid #e2e8f0;
        }
        .btn-secondary:hover { background-color: #e2e8f0; }
        .card { background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); }
        .modal { transition: opacity 0.25s ease; }
    </style>
</head>
<body class="text-gray-800">

    <div class="container mx-auto p-4 sm:p-6 lg:p-8">
        
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Account Settings</h1>
            <p class="text-gray-500 mt-1">Manage your profile, password, and account settings.</p>
        </div>

        <?php 
            // Personal Info Update Messages
            if(isset($_GET['update']) && $_GET['update'] == 'success'):
                echo '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                        <p class="font-bold">Success</p>
                        <p>Your profile information has been updated successfully.</p>
                      </div>';
            endif;
            if(isset($_GET['update']) && $_GET['update'] == 'error'):
                echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                        <p class="font-bold">Update Failed</p>
                        <p>Could not save your changes. Please try again.</p>
                      </div>';
            endif;
            
            // Image Upload Messages
            if(isset($_GET['success'])): echo '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert"><p class="font-bold">Success</p><p>Profile picture updated successfully.</p></div>'; endif;
            if(isset($_GET['error'])): echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert"><p class="font-bold">Upload Error</p><p>Could not upload image. Please check file type and size.</p></div>'; endif;
            
            // Password Update Messages
            if(isset($_GET['pass_success'])): echo '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert"><p class="font-bold">Success</p><p>Your password has been changed successfully.</p></div>'; endif;
            if(isset($_GET['pass_error'])) {
                $errorMsg = '';
                switch ($_GET['pass_error']) {
                    case 'mismatch': $errorMsg = 'New passwords do not match.'; break;
                    case 'incorrect': $errorMsg = 'The current password you entered is incorrect.'; break;
                    case 'short': $errorMsg = 'New password must be at least 8 characters long.'; break;
                    case 'empty': $errorMsg = 'Please fill in all password fields.'; break;
                    default: $errorMsg = 'An unknown error occurred. Please try again.'; break;
                }
                echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert"><p class="font-bold">Password Error</p><p>' . htmlspecialchars($errorMsg) . '</p></div>';
            }
        ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-1">
                <div class="card p-6 text-center flex flex-col items-center">
                    <img src="<?php echo $profile_pic; ?>" alt="User Avatar" class="h-32 w-32 rounded-full object-cover mb-4 ring-4 ring-blue-100">
                    <h2 class="text-xl font-bold"><?php echo $full_name; ?></h2>
                    <p class="text-gray-500 mb-4"><?php echo htmlspecialchars($user['email']); ?></p>
                    
                    <button id="changePicBtn" class="btn btn-secondary w-full text-sm">
                        <i class="fas fa-camera mr-2"></i> Change Picture
                    </button>

                    <div class="w-full border-t my-6"></div>
                    <div class="text-left w-full space-y-3">
                         <div class="flex items-center text-sm"><i class="fas fa-user-circle w-5 mr-3 text-gray-400"></i><span class="font-semibold mr-2">Role:</span><span class="text-gray-600"><?php echo $user_role; ?></span></div>
                         <div class="flex items-center text-sm"><i class="fas fa-calendar-alt w-5 mr-3 text-gray-400"></i><span class="font-semibold mr-2">Joined:</span><span class="text-gray-600"><?php echo date("F d, Y", strtotime($user['created_at'])); ?></span></div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-8">
                
                <div class="card">
                    <div class="border-b p-6"><h3 class="text-xl font-semibold">Personal Information</h3><p class="text-sm text-gray-500 mt-1">Update your personal details here.</p></div>
                    <form action="save_changes.php" method="POST" class="p-6">
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                            <div><label for="first_name" class="block text-sm font-medium mb-1">First Name</label><input type="text" id="first_name" name="first_name" class="form-input" value="<?php echo htmlspecialchars($user['first_name']); ?>"></div>
                            <div><label for="middle_name" class="block text-sm font-medium mb-1">Middle Name</label><input type="text" id="middle_name" name="middle_name" class="form-input" value="<?php echo htmlspecialchars($user['middle_name'] ?? ''); ?>"></div>
                            <div><label for="last_name" class="block text-sm font-medium mb-1">Last Name</label><input type="text" id="last_name" name="last_name" class="form-input" value="<?php echo htmlspecialchars($user['last_name']); ?>"></div>
                             <div><label for="suffix" class="block text-sm font-medium mb-1">Suffix</label><input type="text" id="suffix" name="suffix" class="form-input" value="<?php echo htmlspecialchars($user['suffix'] ?? ''); ?>"></div>
                             <div><label for="gender" class="block text-sm font-medium mb-1">Gender</label>
                                <select id="gender" name="gender" class="form-input">
                                    <option value="Male" <?php if($user['gender'] == 'Male') echo 'selected'; ?>>Male</option>
                                    <option value="Female" <?php if($user['gender'] == 'Female') echo 'selected'; ?>>Female</option>
                                    <option value="Other" <?php if($user['gender'] == 'Other') echo 'selected'; ?>>Other</option>
                                </select>
                            </div>
                            <div><label for="birthday" class="block text-sm font-medium mb-1">Birthday</label><input type="date" id="birthday" name="birthday" class="form-input" value="<?php echo htmlspecialchars($user['birthday']); ?>"></div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                             <div><label for="phone_number" class="block text-sm font-medium mb-1">Phone Number</label><input type="tel" id="phone_number" name="phone_number" class="form-input" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>"></div>
                             <div><label for="email" class="block text-sm font-medium mb-1">Email Address</label><input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars($user['email']); ?>"></div>
                        </div>
                        <div class="border-t my-6"></div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div><label for="region" class="block text-sm font-medium mb-1">Region</label><input type="text" id="region" name="region" class="form-input" value="<?php echo htmlspecialchars($user['region'] ?? ''); ?>"></div>
                            <div><label for="province" class="block text-sm font-medium mb-1">Province</label><input type="text" id="province" name="province" class="form-input" value="<?php echo htmlspecialchars($user['province'] ?? ''); ?>"></div>
                            <div><label for="municipality" class="block text-sm font-medium mb-1">Municipality</label><input type="text" id="municipality" name="municipality" class="form-input" value="<?php echo htmlspecialchars($user['municipality'] ?? ''); ?>"></div>
                            <div><label for="barangay" class="block text-sm font-medium mb-1">Barangay</label><input type="text" id="barangay" name="barangay" class="form-input" value="<?php echo htmlspecialchars($user['barangay'] ?? ''); ?>"></div>
                            <div class="md:col-span-2"><label for="purok" class="block text-sm font-medium mb-1">Purok / Street</label><input type="text" id="purok" name="purok" class="form-input" value="<?php echo htmlspecialchars($user['purok'] ?? ''); ?>"></div>
                        </div>
                        <div class="flex justify-end"><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i> Save Changes</button></div>
                    </form>
                </div>

                <div class="card">
                     <div class="border-b p-6"><h3 class="text-xl font-semibold">Change Password</h3></div>
                     <form action="update_password.php" method="POST" class="p-6">
                         <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                        <div class="space-y-4 mb-6">
                            <div><label for="current_password" class="block text-sm font-medium mb-1">Current Password</label><input type="password" id="current_password" name="current_password" class="form-input" placeholder="••••••••" required></div>
                            <div><label for="new_password" class="block text-sm font-medium mb-1">New Password</label><input type="password" id="new_password" name="new_password" class="form-input" placeholder="••••••••" required></div>
                            <div><label for="confirm_password" class="block text-sm font-medium mb-1">Confirm New Password</label><input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="••••••••" required></div>
                        </div>
                         <div class="flex justify-end"><button type="submit" class="btn btn-primary"><i class="fas fa-key mr-2"></i> Update Password</button></div>
                     </form>
                </div>
            </div>
        </div>
    </div>

    <div id="uploadModal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center border-b pb-3"><h3 class="text-lg font-semibold">Change Profile Picture</h3><button id="closeModalBtn" class="text-gray-400 hover:text-gray-600 text-3xl">&times;</button></div>
            <div class="mt-4">
                <form action="upload_profile_pic.php" method="POST" enctype="multipart/form-data">
                    <div>
                        <label for="profile_pic_input" class="block text-sm font-medium text-gray-700">Select an image file (PNG, JPG, GIF)</label>
                        <input type="file" name="profile_pic" id="profile_pic_input" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" required>
                        <p class="text-xs text-gray-500 mt-1">Maximum file size: 5 MB.</p>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3"><button type="button" id="cancelModalBtn" class="btn btn-secondary">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-upload mr-2"></i>Upload</button></div>
                </form>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('uploadModal');
    const changePicBtn = document.getElementById('changePicBtn');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const cancelModalBtn = document.getElementById('cancelModalBtn');
    const openModal = () => modal.classList.remove('hidden');
    const closeModal = () => modal.classList.add('hidden');
    if (changePicBtn) { changePicBtn.addEventListener('click', openModal); }
    if (closeModalBtn) { closeModalBtn.addEventListener('click', closeModal); }
    if (cancelModalBtn) { cancelModalBtn.addEventListener('click', closeModal); }
    window.addEventListener('click', (event) => { if (event.target === modal) { closeModal(); } });
});
</script>

</body>
</html>