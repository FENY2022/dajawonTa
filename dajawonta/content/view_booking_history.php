<?php
session_start();
include '../db.php'; // Adjust path if db.php is in a different location (e.g., ../db.php)

// Check if user is logged in and is a service provider (role '1')
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_rules'] != '1') {
    // If not logged in or not a provider, display an error and exit
    // We'll style this error to look good within the iframe
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Access Denied</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-gray-100 flex items-center justify-center h-screen"><div class="text-center p-8 bg-white shadow-lg rounded-lg"><i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4"></i><h1 class="text-2xl font-bold text-gray-800">Access Denied</h1><p class="text-gray-600 mt-2">You do not have permission to view this page. Please log in as a service provider.</p></div></body></html>';
    exit;
}

// Get the logged-in user's ID
$user_id = $_SESSION['user_id'];

// Prepare and execute the SQL query
// We join bookings with service_providers
// We filter where the service_provider's user_id matches the logged-in user
$bookings = [];
$sql = "SELECT b.id, b.provider_id, b.customer_name, b.booking_date_from, b.booking_date_to, 
               b.total_price, b.booking_status, b.payment_status, 
               sp.service_name, sp.company_name
        FROM bookings b
        INNER JOIN service_providers sp ON b.provider_id = sp.id
        WHERE sp.user_id = ?
        ORDER BY b.created_at DESC";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $bookings = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        // Handle query error
        echo "Error fetching bookings: " . $conn->error;
    }
    $stmt->close();
} else {
    // Handle statement preparation error
    echo "Error preparing statement: " . $conn->error;
}
$conn->close();

// Helper function to get status colors
function getStatusClass($status) {
    switch (strtolower($status)) {
        case 'approved':
        case 'paid':
            return 'bg-green-100 text-green-800';
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'cancelled':
        case 'failed':
            return 'bg-red-100 text-red-800';
        case 'unpaid':
            return 'bg-gray-100 text-gray-800';
        default:
            return 'bg-blue-100 text-blue-800';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking History</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa; /* Match dashboard bg */
        }
        /* Style for table */
        th {
            background-color: #f8f9fa; /* Light gray header */
        }
    </style>
</head>
<body class="p-4 md:p-8">
    <div class="max-w-7xl mx-auto">
        
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Booking History</h1>
            <p class="text-gray-500 mt-1">Review all past and current bookings for your services.</p>
        </div>

        <!-- Bookings Table -->
        <div class="bg-white shadow-lg rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Price</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-calendar-times text-4xl text-gray-400 mb-3"></i>
                                    <p class="text-lg font-medium">No bookings found</p>
                                    <p class="text-sm">You do not have any booking history at this time.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $booking): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        #<?php echo htmlspecialchars($booking['id']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <?php echo htmlspecialchars($booking['customer_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <?php echo htmlspecialchars($booking['service_name']); ?>
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($booking['company_name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <?php echo htmlspecialchars(date("M d, Y", strtotime($booking['booking_date_from']))); ?> - 
                                        <?php echo htmlspecialchars(date("M d, Y", strtotime($booking['booking_date_to']))); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-800">
                                        ₱<?php echo htmlspecialchars(number_format($booking['total_price'], 2)); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusClass($booking['booking_status']); ?>">
                                            <?php echo htmlspecialchars(ucfirst($booking['booking_status'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusClass($booking['payment_status']); ?>">
                                            <?php echo htmlspecialchars(ucfirst($booking['payment_status'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <!-- This link targets the parent dashboard to change the content of the iframe -->
                                        <a href="../dashboard.php?action=provider_booking_details&provider_id=<?php echo htmlspecialchars($booking['provider_id']); ?>&booking_id=<?php echo htmlspecialchars($booking['id']); ?>" 
                                           target="_parent" 
                                           class="text-blue-600 hover:text-blue-800 transition-colors">
                                            View Details <i class="fas fa-arrow-right ml-1 text-xs"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</body>
</html>
