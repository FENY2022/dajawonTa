<?php
session_start();

// Basic user info
$userName = htmlspecialchars($_SESSION['first_name'] ?? 'User');
$userRole = $_SESSION['user_rules'] ?? null;
$userID = $_SESSION['user_id'] ?? 0; // Get user ID for queries

// Require database connection
require_once '../db.php';

// Function to get a welcome message based on role
function getWelcomeMessage($role) {
    switch ($role) {
        case '0': return "Welcome back! Find and book trusted local service providers for your next project.";
        case '1': return "Welcome back, Service Provider! Here's an overview of your services and bookings.";
        case '2': return "Welcome, Administrator. Here's the current status of the DajawonTa platform.";
        default: return "Welcome to your Dashboard.";
    }
}

// Function to get stats based on role
function getStats($role, $conn, $userID) {
    
    // Helper function for prepared statements (COUNT)
    $fetchCount = function($conn, $sql, $params = [], $types = "") {
        $stmt = $conn->prepare($sql);
        if (!$stmt) { return 0; } // Handle error
        if ($params && $types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['count'] ?? 0;
    };

    // Helper function for single value (SUM, AVG)
    $fetchValue = function($conn, $sql, $params = [], $types = "") {
        $stmt = $conn->prepare($sql);
        if (!$stmt) { return 0; } // Handle error
        if ($params && $types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        // Assuming the alias is 'value'
        return $row['value'] ?? 0;
    };

    switch ($role) {
        case '0': // Client
            // Active Bookings: 'pending', 'approved', 'rescheduled'
            $activeBookingsSql = "SELECT COUNT(*) as count FROM bookings WHERE customer_id = ? AND booking_status IN ('pending', 'approved', 'rescheduled')";
            $activeBookings = $fetchCount($conn, $activeBookingsSql, [$userID], "i");

            // Completed Services
            $completedSql = "SELECT COUNT(*) as count FROM bookings WHERE customer_id = ? AND booking_status = 'completed'";
            $completedServices = $fetchCount($conn, $completedSql, [$userID], "i");

            // Services Available: Count of approved and available providers
            $servicesSql = "SELECT COUNT(*) as count FROM service_providers WHERE is_approved = 1 AND is_available = 1";
            $servicesAvailable = $fetchCount($conn, $servicesSql);

            // Reviews Submitted
            $reviewsSql = "SELECT COUNT(*) as count FROM provider_ratings WHERE customer_id = ?";
            $reviewsSubmitted = $fetchCount($conn, $reviewsSql, [$userID], "i");

            return [
                ['icon' => 'fa-calendar-check', 'value' => $activeBookings, 'label' => 'Active Bookings', 'color' => 'blue'],
                ['icon' => 'fa-history', 'value' => $completedServices, 'label' => 'Completed Services', 'color' => 'green'],
                ['icon' => 'fa-search', 'value' => $servicesAvailable . '+', 'label' => 'Services Available', 'color' => 'yellow'],
                ['icon' => 'fa-comments', 'value' => $reviewsSubmitted, 'label' => 'Reviews Submitted', 'color' => 'purple']
            ];
            
        case '1': // Provider (Dynamic Data)
            // Active Listings: Count of provider's own approved services
            $activeListingsSql = "SELECT COUNT(*) as count FROM service_providers WHERE user_id = ? AND is_approved = 1";
            $activeListings = $fetchCount($conn, $activeListingsSql, [$userID], "i");

            // Pending Bookings: Count of bookings for the provider's services that are 'pending'
            $pendingBookingsSql = "SELECT COUNT(*) as count FROM bookings 
                                   WHERE booking_status = 'pending' 
                                   AND provider_id IN (SELECT id FROM service_providers WHERE user_id = ?)";
            $pendingBookings = $fetchCount($conn, $pendingBookingsSql, [$userID], "i");

            // Average Rating: Average rating across all of the provider's services
            $avgRatingSql = "SELECT AVG(rating) as value FROM provider_ratings 
                             WHERE provider_id IN (SELECT id FROM service_providers WHERE user_id = ?)";
            $avgRating = $fetchValue($conn, $avgRatingSql, [$userID], "i");
            $avgRatingFormatted = ($avgRating > 0) ? number_format($avgRating, 1) . '/5' : 'N/A';

            // Earnings (Month): Sum of 'total_price' for 'paid' bookings this month for the provider
            $earningsSql = "SELECT SUM(total_price) as value FROM bookings 
                            WHERE payment_status = 'paid' 
                            AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
                            AND YEAR(created_at) = YEAR(CURRENT_DATE()) 
                            AND provider_id IN (SELECT id FROM service_providers WHERE user_id = ?)";
            $earnings = $fetchValue($conn, $earningsSql, [$userID], "i");
            $earningsFormatted = '₱' . number_format($earnings, 2);

            return [
                ['icon' => 'fa-briefcase', 'value' => $activeListings, 'label' => 'Active Listings', 'color' => 'blue'],
                ['icon' => 'fa-calendar-check', 'value' => $pendingBookings, 'label' => 'Pending Bookings', 'color' => 'yellow'],
                ['icon' => 'fa-star', 'value' => $avgRatingFormatted, 'label' => 'Average Rating', 'color' => 'green'],
                ['icon' => 'fa-wallet', 'value' => $earningsFormatted, 'label' => 'Earnings (Month)', 'color' => 'purple']
            ];
            
        case '2': // Admin (Dynamic Data)
            // Total Users
            $totalUsers = $fetchCount($conn, "SELECT COUNT(*) as count FROM users");
            
            // Listed Services (from the 'services' table which defines service types)
            $listedServices = $fetchCount($conn, "SELECT COUNT(*) as count FROM services");
            
            // Total Bookings
            $totalBookings = $fetchCount($conn, "SELECT COUNT(*) as count FROM bookings");
            
            // Pending Reports (Interpreted as Pending Bookings)
            $pendingReports = $fetchCount($conn, "SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'pending'");

            return [
                ['icon' => 'fa-users', 'value' => $totalUsers, 'label' => 'Total Users', 'color' => 'blue'],
                ['icon' => 'fa-tools', 'value' => $listedServices, 'label' => 'Listed Services', 'color' => 'yellow'],
                ['icon' => 'fa-book', 'value' => $totalBookings, 'label' => 'Total Bookings', 'color' => 'green'],
                ['icon' => 'fa-exclamation-triangle', 'value' => $pendingReports, 'label' => 'Pending Reports', 'color' => 'red']
            ];
            
        default: return [];
    }
}

// Function to get quick actions based on role
function getQuickActions($role) {
    switch ($role) {
        case '0': // Client
            return [
                ['icon' => 'fa-search', 'label' => 'Find a Service', 'link' => 'browse_services.php'],
                ['icon' => 'fa-calendar-alt', 'label' => 'View My Bookings', 'link' => 'my_bookings.php'],
                ['icon' => 'fa-user-circle', 'label' => 'Manage Profile', 'link' => 'profile.php']
            ];
        case '1': // Provider
            return [
                ['icon' => 'fa-plus', 'label' => 'Add New Service', 'link' => 'addNewservice.php', 'modal' => true],
                ['icon' => 'fa-tasks', 'label' => 'View Bookings', 'link' => 'view_booking_history.php'],
                ['icon' => 'fa-user-edit', 'label' => 'Update Profile', 'link' => 'profile.php']
            ];
        case '2': // Admin
            return [
                ['icon' => 'fa-users-cog', 'label' => 'Manage Users', 'link' => 'user_management.php'],
                ['icon' => 'fa-list-alt', 'label' => 'Manage Services', 'link' => 'service_management.php'],
                ['icon' => 'fa-chart-line', 'label' => 'View Analytics', 'link' => 'analytics.php']
            ];
        default: return [];
    }
}

$stats = getStats($userRole, $conn, $userID); // Pass $conn and $userID
$quickActions = getQuickActions($userRole);

// Fetch all services from the database
$services = [];
$sql = "SELECT service_id, service_name, description FROM services ORDER BY service_name";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
}

$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | DajawonTa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --info: #4895ef;
            --warning: #f72585;
            --danger: #e63946;
            --light: #f8f9fa;
            --dark: #212529;
        }

        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .dashboard-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .stat-card {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
            z-index: 1;
        }

        .stat-icon {
            position: absolute;
            bottom: -10px;
            right: -10px;
            font-size: 5rem;
            opacity: 0.1;
            transform: rotate(-15deg);
            transition: all 0.3s ease;
        }

        .stat-card:hover .stat-icon {
            transform: rotate(0deg) scale(1.2);
            opacity: 0.15;
        }

        .quick-action-item {
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }

        .quick-action-item:hover {
            border-left-color: var(--primary);
            background: linear-gradient(90deg, rgba(67, 97, 238, 0.05) 0%, rgba(255,255,255,0) 100%);
            transform: translateX(5px);
        }

        .service-card {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 0;
            background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%);
            transition: all 0.3s ease;
        }

        .service-card:hover::before {
            height: 100%;
        }

        .service-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .color-blue { background: linear-gradient(135deg, #4361ee 0%, #3a56d4 100%); color: white; }
        .color-yellow { background: linear-gradient(135deg, #ffb800 0%, #f5a300 100%); color: white; }
        .color-green { background: linear-gradient(135deg, #07c98a 0%, #06b57c 100%); color: white; }
        .color-purple { background: linear-gradient(135deg, #9d4edd 0%, #7b2cbf 100%); color: white; }
        .color-red { background: linear-gradient(135deg, #e63946 0%, #d00000 100%); color: white; }

        .welcome-header {
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        @media (max-width: 768px) {
            .stat-icon {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body class="p-4 md:p-6 lg:p-8">

    <div class="max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 p-4 dashboard-card">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold welcome-header">Hello, <?php echo $userName; ?>! 👋</h1>
                <p class="text-gray-600 mt-2"><?php echo getWelcomeMessage($userRole); ?></p>
            </div>
            <div class="mt-4 md:mt-0 flex items-center space-x-4">
                <div class="hidden md:block h-10 w-10 rounded-full bg-gradient-to-r from-blue-400 to-purple-500 flex items-center justify-center text-white font-bold">
                    <?php echo strtoupper(substr($userName, 0, 1)); ?>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <?php foreach ($stats as $stat): ?>
            <div class="stat-card p-6 rounded-2xl color-<?php echo $stat['color']; ?> text-white">
                <div class="relative z-10">
                    <p class="text-sm font-medium opacity-90"><?php echo $stat['label']; ?></p>
                    <p class="text-2xl md:text-3xl font-bold mt-2"><?php echo $stat['value']; ?></p>
                </div>
                <div class="stat-icon">
                    <i class="fas <?php echo $stat['icon']; ?>"></i>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-1 dashboard-card p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                    <i class="fas fa-bolt text-yellow-500 mr-2"></i>
                    Quick Actions
                </h2>
                <div class="space-y-4">
                    <?php foreach ($quickActions as $action): ?>
                    <a href="<?php echo $action['link']; ?>"
                       class="quick-action-item flex items-center p-4 rounded-lg transition-all <?php echo isset($action['modal']) ? 'quick-action-modal' : ''; ?>">
                        <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center mr-4">
                            <i class="fas <?php echo $action['icon']; ?> text-blue-500"></i>
                        </div>
                        <span class="font-medium text-gray-800"><?php echo $action['label']; ?></span>
                        <i class="fas fa-chevron-right text-gray-400 ml-auto"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
                
                <div class="mt-8 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-100">
                    <div class="flex items-start">
                        <div class="mr-3 mt-1">
                            <i class="fas fa-info-circle text-blue-500"></i>
                        </div>
                        <div>
                            <h3 class="font-medium text-blue-800">Need help?</h3>
                            <p class="text-sm text-blue-600 mt-1">Check our documentation or contact support for assistance.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2 dashboard-card p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center">
                        <i class="fas fa-concierge-bell text-purple-500 mr-2"></i>
                        Available Services
                    </h2>
                    <a href="#" class="text-sm text-blue-500 hover:text-blue-700 flex items-center mt-2 sm:mt-0">
                        View all services
                        <i class="fas fa-arrow-right ml-1 text-xs"></i>
                    </a>
                </div>
                
                <?php if (!empty($services)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($services as $service): ?>
                    <a href="serviceProvider.php?service_id=<?php echo $service['service_id']; ?>"
                       class="service-card bg-white p-5 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all quick-action-modal">
                        <h3 class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($service['service_name']); ?></h3>
                        <p class="text-sm text-gray-600 mt-2 line-clamp-2"><?php echo htmlspecialchars($service['description']); ?></p>
                        <div class="flex items-center mt-4 text-sm text-blue-500">
                            <span>Book now</span>
                            <i class="fas fa-arrow-right ml-2 text-xs"></i>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-10">
                    <i class="fas fa-tools text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500">No services are currently listed.</p>
                </div>
                <?php endif; ?>
                
                <div class="mt-8 pt-6 border-t border-gray-100">
                    <h3 class="font-medium text-gray-700 mb-4">Recent Activity</h3>
                    <div class="flex items-center text-sm text-gray-500">
                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center mr-3">
                            <i class="fas fa-check text-green-500 text-xs"></i>
                        </div>
                        <p>You booked <span class="font-medium">Home Cleaning Service</span> 2 days ago</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="iframeModal" class="fixed inset-0 z-50 hidden overflow-auto bg-gray-900 bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl md:max-w-3xl lg:max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
            <div class="flex justify-between items-center p-4 border-b border-gray-200">
                <h3 id="modal-title" class="text-xl font-semibold text-gray-800"></h3>
                <button id="closeModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="flex-grow">
                <iframe id="modalIframe" src="" frameborder="0" class="w-full h-[80vh]"></iframe>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('iframeModal');
            const iframe = document.getElementById('modalIframe');
            const closeModalBtn = document.getElementById('closeModal');
            const modalTitle = document.getElementById('modal-title');
            
            // Select all links that should open the modal
            const modalLinks = document.querySelectorAll('.quick-action-modal');

            // Open modal and load iframe
            modalLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault(); // Prevent the browser from navigating
                    
                    const url = this.getAttribute('href');
                    let title = this.getAttribute('data-title');
                    
                    // Get the title from either a data attribute or a child element
                    if (!title) {
                        const titleElement = this.querySelector('span') || this.querySelector('h3');
                        title = titleElement ? titleElement.textContent : 'Details';
                    }
                    
                    modalTitle.textContent = title;
                    iframe.src = url;
                    modal.classList.remove('hidden');
                    
                    // Add a one-time event listener to close the modal when clicking outside
                    modal.addEventListener('click', function(e) {
                        if (e.target === this) {
                            closeModal();
                        }
                    }, { once: true });
                });
            });

            // Close modal function
            function closeModal() {
                modal.classList.add('hidden');
                iframe.src = ''; // Clear iframe content
            }

            // Close modal with button
            closeModalBtn.addEventListener('click', closeModal);
        });
    </script>
</body>
</html>