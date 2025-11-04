<?php
session_start();
// Go up one directory to include the database connection
include '../db.php'; 

// Redirect to login if not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // We are in an iframe, so we must redirect the top-level window
    echo "<script>window.top.location.href = '../login.php';</script>";
    exit;
}

// Check if user is a Service Provider (Role '1')
if (!isset($_SESSION['user_rules']) || $_SESSION['user_rules'] != '1') {
    die('<div class="p-8 text-red-500">Access Denied: You do not have permission to view this page.</div>');
}

$user_id = $_SESSION['user_id'];
$bookings = [];
$provider_ids = [];

// 1. Find all provider accounts (service_provider IDs) associated with this user
$stmt_provider = $conn->prepare("SELECT id FROM service_providers WHERE user_id = ?");
$stmt_provider->bind_param("i", $user_id);
$stmt_provider->execute();
$result_provider = $stmt_provider->get_result();

while ($row = $result_provider->fetch_assoc()) {
    $provider_ids[] = $row['id'];
}
$stmt_provider->close();

// 2. Fetch all bookings for those provider IDs
if (!empty($provider_ids)) {
    // Create placeholders for the IN clause (e.g., "?, ?, ?")
    $placeholders = implode(',', array_fill(0, count($provider_ids), '?'));
    // Create types string for bind_param (e.g., "iii")
    $types = str_repeat('i', count($provider_ids));

    // Join with service_providers to get the service/company name for each booking
    $sql_bookings = "SELECT b.*, sp.service_name 
                     FROM bookings b
                     JOIN service_providers sp ON b.provider_id = sp.id
                     WHERE b.provider_id IN ($placeholders)
                     ORDER BY b.created_at DESC";
    
    $stmt_bookings = $conn->prepare($sql_bookings);
    // Bind all provider IDs
    $stmt_bookings->bind_param($types, ...$provider_ids);
    $stmt_bookings->execute();
    $bookings_result = $stmt_bookings->get_result();

    if ($bookings_result) {
        $bookings = $bookings_result->fetch_all(MYSQLI_ASSOC);
    }
    $stmt_bookings->close();
}
$conn->close();

// Helper function to format status badges (using Tailwind classes)
function getStatusBadge($status) {
    switch (strtolower($status)) {
        case 'approved':
            return 'bg-green-100 text-green-800';
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'declined':
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        case 'completed':
            return 'bg-blue-100 text-blue-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Add Inter font, matching your dashboard */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa; /* Match dashboard main content bg */
        }
        /* Your theme's primary color for links */
        :root {
            --primary: #0D6EFD;
        }
        .text-primary { color: var(--primary); }
        .hover\:text-primary-dark:hover { color: #0B5ED7; }
    </style>
</head>
<body class="p-4 md:p-8">
    <div class="max-w-7xl mx-auto">
        
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Booking Details</h1>
            <a href="../dashboard.php?action=view_booking_history" target="_top" class="text-sm text-primary hover:text-primary-dark">
                View Full History <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Price</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-book-open text-4xl text-gray-300 mb-4"></i>
                                    <p>You have no bookings yet.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        #<?php echo htmlspecialchars($booking['id']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <?php echo htmlspecialchars($booking['customer_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($booking['service_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M d, Y', strtotime($booking['booking_date_from'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        ₱<?php echo number_format($booking['total_price'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusBadge($booking['booking_status']); ?>">
                                            <?php echo htmlspecialchars(ucfirst($booking['booking_status'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="../dashboard.php?action=provider_booking_details&provider_id=<?php echo $booking['provider_id']; ?>&booking_id=<?php echo $booking['id']; ?>" 
                                           target="_top" 
                                           class="text-primary hover:text-primary-dark hover:underline">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <p class="text-center text-gray-500 text-xs mt-8">
            This page shows all bookings associated with your provider accounts.
        </p>
    </div>
</body>
</html>