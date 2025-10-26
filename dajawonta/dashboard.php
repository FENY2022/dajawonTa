<?php
session_start();

// Capture provider_id and booking_id (from query string) and sanitize/validate as integers
$provider_id = null;
$booking_id  = null;

if (isset($_GET['provider_id']) && $_GET['provider_id'] !== '') {
    $provider_id = filter_var($_GET['provider_id'], FILTER_VALIDATE_INT);
    if ($provider_id === false) {
        $provider_id = null;
    }
}

if (isset($_GET['booking_id']) && $_GET['booking_id'] !== '') {
    $booking_id = filter_var($_GET['booking_id'], FILTER_VALIDATE_INT);
    if ($booking_id === false) {
        $booking_id = null;
    }
}



// Redirect to login if not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

// Determine user role for navigation
// Based on comment: '1' for Client, '0' for Provider, '2' for Admin
$user_role = $_SESSION['user_rules'] ?? null; 

// Function to determine role name from rule number
function getRoleName($role) {
    switch ($role) {
        case '0': return 'Local Client';
        case '1': return 'Service Provider';
        case '2': return 'Administrator';
        default: return 'User';
    }
}

// Determine the current action for setting active state
$action = $_GET['action'] ?? 'dashboard';

// List of allowed pages to prevent security issues like file inclusion
$allowed_pages = [
    'dashboard' => 'content/dashboard_content.php',
    // Client pages (Role '1') - Note: Your comment says '1' is Client but your code uses '0'. I'm following the code.
    'browse_services' => 'content/browse_services.php',
    'my_bookings' => 'content/my_bookings.php',
    'my_reviews' => 'content/my_reviews.php',
    // Provider pages (Role '0') - Note: Your comment says '0' is Provider but your code uses '1'. I'm following the code.
    'view_listings' => 'content/view_listings.php?provider_id=' . urlencode($provider_id),
    'addNewservice.php' => 'content/addNewservice.php',
    'my_ratings' => 'content/my_ratings.php',
    'view_booking_history' => 'content/view_booking_history.php',
    'booking_details' => 'content/booking_details.php',

    'customer_booking_details' => 'content/customer_booking_details.php?booking_id=' . urlencode($booking_id),
    'payment_success' => 'content/payment_success.php?booking_id=' . urlencode($booking_id),
    'payment_cancel' => 'content/payment_cancel.php?booking_id=' . urlencode($booking_id),


    

    

    'reschedule_option' => 'content/reschedule_option.php',
    'payment_status' => 'content/payment_status.php',
    'provider_booking_details' => 'content/provider_booking_details.php?provider_id=' . urlencode($provider_id) . '&booking_id=' . urlencode($booking_id),




    // Admin pages (Role '2')
    'user_management' => 'content/user_management.php',
    'service_management' => 'content/service_management.php',
    'booking_management' => 'content/booking_management.php',
    'analytics' => 'content/analytics.php',
    'confirmService_providers' => 'content/confirmService_providers.php',


    // Common pages
    'profile' => 'content/profile.php',
    'settings' => 'content/profile.php',
    'system_settings' => 'content/system_settings.php',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - DajawonTa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="logo/dajawonta.png">
    <style>
        /* DajawonTa Theme */
        :root {
            --primary: #0D6EFD;
            --primary-dark: #0B5ED7;
            --light: #f8f9fa;
            --dark: #212529;
            --gray-100: #f1f5f9;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-800: #1e293b;
            --danger: #dc3545;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
            color: var(--gray-800);
        }
        
        .sidebar {
            width: 260px;
            transition: transform 0.3s ease-in-out;
        }
        
        .main-content-wrapper {
            margin-left: 260px;
            transition: margin-left 0.3s ease-in-out;
        }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); position: fixed; z-index: 50; height: 100vh; }
            .sidebar.active { transform: translateX(0); }
            .main-content-wrapper { margin-left: 0; }
            .overlay { display: none; position: fixed; inset: 0; background-color: rgba(0, 0, 0, 0.5); z-index: 40; }
            .overlay.active { display: block; }
        }

        .nav-item {
            display: flex; align-items: center; padding: 12px 16px; border-radius: 8px;
            margin-bottom: 4px; transition: all 0.2s ease; cursor: pointer;
            font-weight: 500; color: var(--gray-500);
        }
        
        .nav-item:hover { background-color: var(--gray-100); color: var(--primary); }
        .nav-item.active { background-color: #e0e7ff; color: var(--primary); font-weight: 600; }
        .nav-item i { width: 20px; margin-right: 12px; text-align: center; }
        
        .dropdown-toggle { display: flex; justify-content: space-between; align-items: center; width: 100%; }
        .dropdown-arrow { transition: transform 0.3s ease; }
        .submenu { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-in-out; padding-left: 12px; }
        .submenu-open { max-height: 200px; }
        .arrow-open { transform: rotate(90deg); }
        
        .card { background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); transition: all 0.3s ease; }
        .card:hover { box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1); transform: translateY(-2px); }

        .thin-scrollbar { scrollbar-width: thin; scrollbar-color: var(--gray-400) var(--gray-100); }
        .thin-scrollbar::-webkit-scrollbar { width: 6px; }
        .thin-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .thin-scrollbar::-webkit-scrollbar-thumb { background-color: var(--gray-400); border-radius: 20px; }
        .thin-scrollbar::-webkit-scrollbar-thumb:hover { background-color: var(--gray-500); }

        .notification-badge {
            position: absolute; top: -2px; right: -2px; width: 18px; height: 18px;
            background-color: var(--danger); border-radius: 50%; border: 1.5px solid white;
            display: flex; align-items: center; justify-content: center;
            font-size: 10px; font-weight: 700; color: white;
            line-height: 1; /* Ensure text sits correctly */
            padding: 2px;
            opacity: 0; /* Hidden initially, shown via JS */
            transform: scale(0);
            transition: all 0.2s ease;
        }
        .notification-badge.active {
            opacity: 1;
            transform: scale(1);
        }

        /* Styles for the Notification Modal */
        #notification-modal {
            position: absolute;
            top: 100%; 
            right: -20px;
            margin-top: 10px;
            width: 350px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            z-index: 100;
            display: none; 
            transform-origin: top right;
            animation: fadeIn 0.3s ease-out;
            border: 1px solid var(--gray-100);
        }
        #notification-modal.active {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95) translateY(-5px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .notification-item {
            padding: 12px 16px;
            border-bottom: 1px solid var(--gray-100);
            transition: background-color 0.1s;
        }
        .notification-item:last-child {
            border-bottom: none;
        }
        .notification-item:hover {
            background-color: var(--gray-100);
        }
    </style>
</head>
<body class="flex bg-gray-100">
    <aside class="sidebar bg-white shadow-lg fixed h-full flex flex-col">
        <div class="p-6 border-b border-gray-100">
            <div class="flex items-center space-x-3">
                <img src="logo/dajawonta.png" alt="DajawonTa Logo" class="h-10 w-10">
                <h1 class="text-xl font-bold text-gray-800">Dajawon<span style="color: var(--primary);">Ta</span></h1>
            </div>
        </div>
        
        <nav class="p-4 flex-grow overflow-y-auto thin-scrollbar" id="sidebar-nav">
            <?php if ($user_role == '0'): // LOCAL CLIENT MENU ?>
            <div class="mb-8">
                <h3 class="text-xs uppercase tracking-wider text-gray-500 font-semibold mb-4 px-2">Main Menu</h3>
                <ul>
                    <li><a href="dashboard.php?action=dashboard" data-action="dashboard" class="nav-item <?php echo ($action == 'dashboard' ? 'active' : ''); ?>"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="dashboard.php?action=browse_services" data-action="browse_services" class="nav-item <?php echo ($action == 'browse_services' ? 'active' : ''); ?>"><i class="fas fa-search"></i> Browse Services</a></li>
                    <li><a href="dashboard.php?action=my_bookings" data-action="my_bookings" class="nav-item <?php echo ($action == 'my_bookings' ? 'active' : ''); ?>"><i class="fas fa-calendar-check"></i> My Bookings</a></li>
                    <li><a href="dashboard.php?action=my_reviews" data-action="my_reviews" class="nav-item <?php echo ($action == 'my_reviews' ? 'active' : ''); ?>"><i class="fas fa-star"></i> My Reviews</a></li>
                </ul>
            </div>
            <?php elseif ($user_role == '1'): // SERVICE PROVIDER MENU ?>
            <div class="mb-8">
                <h3 class="text-xs uppercase tracking-wider text-gray-500 font-semibold mb-4 px-2">Provider Menu</h3>
                <ul>
                    <li><a href="dashboard.php?action=dashboard" data-action="dashboard" class="nav-item <?php echo ($action == 'dashboard' ? 'active' : ''); ?>"><i class="fas fa-home"></i> Dashboard</a></li>

                    <li class="dropdown">
                        <div class="nav-item dropdown-toggle <?php echo (in_array($action, ['view_booking_history', 'booking_details', 'reschedule_option', 'payment_status']) ? 'active' : ''); ?>">
                            <div class="flex items-center"><i class="fas fa-tasks"></i> Manage Bookings</div>
                            <i class="fas fa-chevron-right dropdown-arrow"></i>
                        </div>
                        <ul class="submenu">
                            <li><a href="dashboard.php?action=view_booking_history" data-action="view_booking_history" class="nav-item <?php echo ($action == 'view_booking_history' ? 'active' : ''); ?>">View Booking History</a></li>
                            <li><a href="dashboard.php?action=booking_details" data-action="booking_details" class="nav-item <?php echo ($action == 'booking_details' ? 'active' : ''); ?>">Booking Details</a></li>
                            <li><a href="dashboard.php?action=reschedule_option" data-action="reschedule_option" class="nav-item <?php echo ($action == 'reschedule_option' ? 'active' : ''); ?>">Reschedule Option</a></li>
                            <li><a href="dashboard.php?action=payment_status" data-action="payment_status" class="nav-item <?php echo ($action == 'payment_status' ? 'active' : ''); ?>">Payment Status</a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <div class="nav-item dropdown-toggle <?php echo (in_array($action, ['view_listings', 'addNewservice.php']) ? 'active' : ''); ?>">
                            <div class="flex items-center"><i class="fas fa-tools"></i> My Services</div>
                            <i class="fas fa-chevron-right dropdown-arrow"></i>
                        </div>
                        <ul class="submenu">
                            <li><a href="dashboard.php?action=view_listings" data-action="view_listings" class="nav-item <?php echo ($action == 'view_listings' ? 'active' : ''); ?>">View Listings</a></li>
                            <li><a href="dashboard.php?action=addNewservice.php" data-action="addNewservice.php" class="nav-item <?php echo ($action == 'addNewservice.php' ? 'active' : ''); ?>">Add New Service</a></li>
                        </ul>
                    </li>
                    <li><a href="dashboard.php?action=my_ratings" data-action="my_ratings" class="nav-item <?php echo ($action == 'my_ratings' ? 'active' : ''); ?>"><i class="fas fa-star-half-alt"></i> Ratings & Feedback</a></li>
                </ul>
            </div>
            <?php elseif ($user_role == '2'): // ADMIN MENU ?>
            <div class="mb-8">
                <h3 class="text-xs uppercase tracking-wider text-gray-500 font-semibold mb-4 px-2">Admin Panel</h3>
                <ul>
                    <li><a href="dashboard.php?action=dashboard" data-action="dashboard" class="nav-item <?php echo ($action == 'dashboard' ? 'active' : ''); ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="dashboard.php?action=user_management" data-action="user_management" class="nav-item <?php echo ($action == 'user_management' ? 'active' : ''); ?>"><i class="fas fa-users-cog"></i> User Management</a></li>
                    <li><a href="dashboard.php?action=service_management" data-action="service_management" class="nav-item <?php echo ($action == 'service_management' ? 'active' : ''); ?>"><i class="fas fa-list-alt"></i> Service Management</a></li>
                    <li><a href="dashboard.php?action=booking_management" data-action="booking_management" class="nav-item <?php echo ($action == 'booking_management' ? 'active' : ''); ?>"><i class="fas fa-book"></i> Booking Management</a></li>
                    <li><a href="dashboard.php?action=analytics" data-action="analytics" class="nav-item <?php echo ($action == 'analytics' ? 'active' : ''); ?>"><i class="fas fa-chart-line"></i> Analytics</a></li>
                </ul>
            </div>
            <?php endif; ?>

            <div>
                <h3 class="text-xs uppercase tracking-wider text-gray-500 font-semibold mb-4 px-2">Account</h3>
                <ul>
                    <li><a href="dashboard.php?action=profile" data-action="profile" class="nav-item <?php echo ($action == 'profile' ? 'active' : ''); ?>"><i class="fas fa-user"></i> My Profile</a></li>
                    <li><a href="dashboard.php?action=settings" data-action="settings" class="nav-item <?php echo ($action == 'settings' ? 'active' : ''); ?>"><i class="fas fa-cog"></i> Settings</a></li>
                    <?php if ($user_role == '2'): // Admin-only System Settings ?>
                    <li><a href="dashboard.php?action=system_settings" data-action="system_settings" class="nav-item <?php echo ($action == 'system_settings' ? 'active' : ''); ?>"><i class="fas fa-cogs"></i> System Settings</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

    <div class="overlay" id="overlay"></div>

    <div class="main-content-wrapper flex-1 flex flex-col h-screen overflow-y-hidden">
        
        <header class="bg-white shadow-sm p-4 flex items-center justify-between z-10">
            <div class="flex items-center space-x-4">
                <button id="menu-toggle" class="md:hidden p-2 rounded-md text-gray-600">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div class="hidden md:flex items-center relative">
                    <i class="fas fa-search absolute left-3 text-gray-400"></i>
                    <input type="text" placeholder="Search services, providers..." class="pl-10 pr-4 py-2 rounded-lg bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 w-80">
                </div>
            </div>

            <div class="flex items-center space-x-6">
                <div class="relative" id="notification-container">
                    <button id="notification-bell" class="relative p-2 rounded-full hover:bg-gray-100 transition-colors">
                        <i class="fas fa-bell text-xl text-gray-500 cursor-pointer"></i>
                        <span class="notification-badge" id="notification-count"></span>
                    </button>
                    <div id="notification-modal">
                        <div class="p-4 border-b">
                            <h4 class="font-semibold text-gray-800">Unread Notifications</h4>
                        </div>
                        <div class="max-h-64 overflow-y-auto thin-scrollbar" id="notification-list">
                            <div class="p-4 text-sm text-gray-500 text-center">Loading notifications...</div>
                        </div>
                        <div class="p-2 text-center text-sm border-t">
                            <a href="dashboard.php?action=settings" class="text-blue-600 hover:text-blue-800">View All</a>
                        </div>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <?php
                    $avatar_src = 'https://i.pravatar.cc/150?u=' . urlencode($_SESSION['user_id'] ?? 'guest');

                    if (!empty($_SESSION['profile_image'])) {
                        // sanitize filename to avoid path traversal
                        $profile_image = $_SESSION['profile_image'];
                        $filename = basename($profile_image);

                        // server side path to the uploads folder
                        $local_path = __DIR__ . '/content/uploads/profile_pictures/' . $filename;
                        $web_path = 'content/uploads/profile_pictures/' . $filename;

                        // If the file exists in the uploads folder, use that. Otherwise, if the stored value looks like a URL or absolute path, use it as-is.
                        if (file_exists($local_path)) {
                            $avatar_src = $web_path;
                        } elseif (preg_match('#^(https?://|/)#', $profile_image)) {
                            $avatar_src = $profile_image;
                        }
                    }
                    ?>
                    <img src="<?php echo htmlspecialchars($avatar_src); ?>" alt="User Avatar" class="h-10 w-10 rounded-full object-cover">
                    <div>
                        <h4 class="font-semibold text-sm text-gray-700"><?php echo htmlspecialchars($_SESSION['first_name'] ?? 'User'); ?></h4>
                        <p class="text-xs text-gray-500"><?php echo getRoleName($user_role); ?></p>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-grow p-0 m-0">
            <?php
            $page_to_load = $allowed_pages[$action] ?? $allowed_pages['dashboard'];
            echo '<iframe src="' . htmlspecialchars($page_to_load) . '" frameborder="0" style="width:100%; height:100%; border:none;"></iframe>';
            ?>
        </main>
        
        <footer class="bg-white border-t border-gray-200 p-4 text-center text-sm text-gray-500">
            &copy; <?php echo date('Y'); ?> DajawonTa. All Rights Reserved.
        </footer>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const menuToggle = document.getElementById('menu-toggle');
        const overlay = document.getElementById('overlay');
        const sidebar = document.querySelector('.sidebar');
        const sidebarNav = document.getElementById('sidebar-nav');
        const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
        const navLinks = document.querySelectorAll('#sidebar-nav a.nav-item');

        const notificationBell = document.getElementById('notification-bell');
        const notificationModal = document.getElementById('notification-modal');
        const notificationCount = document.getElementById('notification-count');
        const notificationList = document.getElementById('notification-list');
        const notificationContainer = document.getElementById('notification-container');

        // --- Utility Functions ---
        function timeAgo(dateString) {
            const now = new Date();
            const past = new Date(dateString);
            const diffSeconds = Math.round((now - past) / 1000);
            
            const intervals = {
                'year': 31536000,
                'month': 2592000,
                'day': 86400,
                'hour': 3600,
                'minute': 60,
                'second': 1
            };

            for (const unit in intervals) {
                const interval = intervals[unit];
                const count = Math.floor(diffSeconds / interval);
                if (count >= 1) {
                    return count + ' ' + unit + (count > 1 ? 's' : '') + ' ago';
                }
            }
            return 'just now';
        }

        // --- Notification Logic ---
        async function fetchNotifications() {
            try {
                const response = await fetch('fetch_notifications.php');
                const data = await response.json();

                if (data.success) {
                    // 1. Update Badge Count
                    const count = data.unread_count;
                    notificationCount.textContent = count;
                    if (count > 0) {
                        notificationCount.classList.add('active');
                    } else {
                        notificationCount.classList.remove('active');
                    }

                    // 2. Render List
                    notificationList.innerHTML = ''; // Clear existing content

                    if (data.notifications.length === 0) {
                        notificationList.innerHTML = '<div class="p-4 text-sm text-gray-500 text-center">You have no unread notifications.</div>';
                        return;
                    }

                    data.notifications.forEach(notif => {
                        const time = timeAgo(notif.created_at);
                        const item = document.createElement('a');
                        item.classList.add('notification-item', 'block', 'text-decoration-none');
                        item.setAttribute('href', notif.link || '#'); // Fallback link
                        item.innerHTML = `
                            <p class="text-sm text-gray-800">${notif.message}</p>
                            <span class="text-xs text-gray-500">${time}</span>
                        `;
                        
                        // ==========================================================
                        // START: CORRECTED CLICK HANDLER LOGIC
                        // ==========================================================
                        item.addEventListener('click', async (event) => {
                            // 1. ALWAYS prevent the default link navigation first.
                            event.preventDefault(); 

                            try {
                                // 2. Send the request to mark the notification as read.
                                await fetch('read_notification.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({ notification_id: notif.notification_id })
                                });

                                // 3. AFTER the fetch is done, check if we need to navigate.
                                if (notif.link) {
                                    // If there's a link, go to it now.
                                    window.location.href = notif.link;
                                } else {
                                    // If there's no link, just refresh the notification list.
                                    fetchNotifications();
                                }
                            } catch (error) {
                                console.error('Failed to mark notification as read:', error);
                            }
                        });
                        // ==========================================================
                        // END: CORRECTED CLICK HANDLER LOGIC
                        // ==========================================================

                        notificationList.appendChild(item);
                    });

                } else {
                    console.error("Error fetching notifications:", data.error || 'Unknown error');
                    notificationList.innerHTML = '<div class="p-4 text-sm text-red-500 text-center">Error loading notifications.</div>';
                }
            } catch (error) {
                console.error("Network error fetching notifications:", error);
                notificationList.innerHTML = '<div class="p-4 text-sm text-red-500 text-center">Network error. Check console for details.</div>';
            }
        }

        // Fetch notifications immediately on load and then every 60 seconds
        fetchNotifications();
        setInterval(fetchNotifications, 60000); 

        // --- Sidebar & General Logic (Existing) ---
        function setActiveNavItem() {
            const currentAction = new URLSearchParams(window.location.search).get('action') || 'dashboard';
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.dataset.action === currentAction) {
                    link.classList.add('active');
                    const parentDropdown = link.closest('.dropdown');
                    if (parentDropdown) {
                        parentDropdown.querySelector('.dropdown-toggle').classList.add('active');
                    }
                }
            });
        }

        function restoreDropdowns() {
            const openDropdowns = JSON.parse(localStorage.getItem('openDropdowns')) || {};
            dropdownToggles.forEach(toggle => {
                const dropdownId = toggle.querySelector('div').innerText.trim();
                if (openDropdowns[dropdownId]) {
                    toggle.nextElementSibling.classList.add('submenu-open');
                    toggle.querySelector('.dropdown-arrow').classList.add('arrow-open');
                }
            });
        }

        function restoreScrollPosition() {
            const savedScroll = localStorage.getItem('sidebarScroll');
            if (savedScroll && sidebarNav) {
                sidebarNav.scrollTop = savedScroll;
            }
        }

        setActiveNavItem();
        restoreDropdowns();
        restoreScrollPosition();

        // Toggle sidebar on mobile
        if (menuToggle) {
            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            });
        }

        // Close sidebar on overlay click (mobile)
        if (overlay) {
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });
        }

        // Dropdown toggle logic
        dropdownToggles.forEach(toggle => {
            toggle.addEventListener('click', () => {
                const submenu = toggle.nextElementSibling;
                const arrow = toggle.querySelector('.dropdown-arrow');
                const dropdownId = toggle.querySelector('div').innerText.trim();
                let openDropdowns = JSON.parse(localStorage.getItem('openDropdowns')) || {};

                submenu.classList.toggle('submenu-open');
                arrow.classList.toggle('arrow-open');
                
                if (submenu.classList.contains('submenu-open')) {
                    openDropdowns[dropdownId] = true;
                } else {
                    delete openDropdowns[dropdownId];
                }
                localStorage.setItem('openDropdowns', JSON.stringify(openDropdowns));
            });
        });

        // Save sidebar scroll position
        if (sidebarNav) {
            sidebarNav.addEventListener('scroll', () => {
                localStorage.setItem('sidebarScroll', sidebarNav.scrollTop);
            });
        }

        // Hide sidebar on link click (mobile)
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768 && sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                }
            });
        });

        // Toggle notification modal
        if (notificationBell) {
            notificationBell.addEventListener('click', (event) => {
                event.stopPropagation(); 
                notificationModal.classList.toggle('active');
            });
        }

        // Close the modal when clicking outside of it
        document.addEventListener('click', (event) => {
            if (notificationModal.classList.contains('active') && !notificationContainer.contains(event.target)) {
                notificationModal.classList.remove('active');
            }
        });
    });
    </script>
</body>
</html>