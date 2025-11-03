<?php
session_start();
// Assumes db.php is in the parent directory (e.g., ../db.php)
include '../db.php';

// Security Check: Ensure user is logged in AND is an Administrator (role '2')
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_rules'] != '2') {
    // Using die() is simple for an iframe. You could also redirect.
    die("<div style='font-family: sans-serif; text-align: center; padding: 40px;'>
            <h1 style='color: #dc3545;'>Access Denied</h1>
            <p style='color: #333;'>You do not have permission to view this page.</p>
         </div>");
}

// Get the current filter from the URL, default to 'all'
$filter = $_GET['filter'] ?? 'all';
$where_clause = '';
$params = [];
$types = '';

// Build the WHERE clause based on the filter
switch ($filter) {
    case 'pending':
        $where_clause = "WHERE b.booking_status = ?";
        $params[] = 'pending';
        $types .= 's';
        break;
    case 'approved':
        $where_clause = "WHERE b.booking_status = ?";
        $params[] = 'approved';
        $types .= 's';
        break;
    case 'cancelled':
        $where_clause = "WHERE b.booking_status = 'cancelled' OR b.booking_status = 'rejected'";
        break;
    case 'paid':
        $where_clause = "WHERE b.payment_status = ?";
        $params[] = 'paid';
        $types .= 's';
        break;
    case 'unpaid':
        $where_clause = "WHERE b.payment_status = ?";
        $params[] = 'unpaid';
        $types .= 's';
        break;
    default:
        $where_clause = ''; // 'all' filter means no WHERE clause
}

// Prepare the SQL statement to fetch bookings
// We JOIN with service_providers to get the company name
$sql = "SELECT 
            b.id, 
            b.customer_name, 
            sp.company_name, 
            b.booking_date_from, 
            b.booking_date_to, 
            b.total_price, 
            b.booking_status, 
            b.payment_status,
            b.created_at
        FROM bookings b
        JOIN service_providers sp ON b.provider_id = sp.id
        $where_clause
        ORDER BY b.created_at DESC";

$stmt = $conn->prepare($sql);

// Bind parameters if any
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

/**
 * Helper function to generate a status badge
 */
function getStatusBadge($status, $type = 'booking') {
    $color_class = 'bg-gray-100 text-gray-600'; // default
    if ($type == 'booking') {
        switch (strtolower($status)) {
            case 'approved':
                $color_class = 'bg-green-100 text-green-700';
                break;
            case 'pending':
                $color_class = 'bg-yellow-100 text-yellow-700';
                break;
            case 'cancelled':
            case 'rejected':
                $color_class = 'bg-red-100 text-red-700';
                break;
        }
    } elseif ($type == 'payment') {
         switch (strtolower($status)) {
            case 'paid':
                $color_class = 'bg-blue-100 text-blue-700';
                break;
            case 'unpaid':
                $color_class = 'bg-orange-100 text-orange-700';
                break;
        }
    }
    return "<span class='inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium " . $color_class . "'>" . htmlspecialchars(ucfirst($status)) . "</span>";
}

/**
 * Helper function to determine if a filter tab is active
 */
function getFilterClass($current_filter, $tab_name) {
    if ($current_filter == $tab_name) {
        return 'border-blue-500 text-blue-600';
    } else {
        return 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Ensures the content fits well within the iframe */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa; /* Match dashboard bg */
        }
    </style>
</head>
<body class="bg-gray-100">

    <div class="container mx-auto p-6 md:p-8">

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Booking Management</h1>
        </div>

        <div class="mb-4 border-b border-gray-200">
            <nav class="flex -mb-px space-x-6" aria-label="Tabs">
                <a href="booking_management.php?filter=all" 
                   class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm <?php echo getFilterClass($filter, 'all'); ?>">
                   All Bookings
                </a>
                <a href="booking_management.php?filter=pending" 
                   class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm <?php echo getFilterClass($filter, 'pending'); ?>">
                   Pending
                </a>
                <a href="booking_management.php?filter=approved" 
                   class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm <?php echo getFilterClass($filter, 'approved'); ?>">
                   Approved
                </a>
                 <a href="booking_management.php?filter=paid" 
                   class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm <?php echo getFilterClass($filter, 'paid'); ?>">
                   Paid
                </a>
                 <a href="booking_management.php?filter=unpaid" 
                   class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm <?php echo getFilterClass($filter, 'unpaid'); ?>">
                   Unpaid
                </a>
                <a href="booking_management.php?filter=cancelled" 
                   class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm <?php echo getFilterClass($filter, 'cancelled'); ?>">
                   Cancelled/Rejected
                </a>
            </nav>
        </div>

        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="py-3.5 px-4 text-left text-sm font-semibold text-gray-600">Booking ID</th>
                            <th scope="col" class="py-3.5 px-4 text-left text-sm font-semibold text-gray-600">Customer</th>
                            <th scope="col" class="py-3.5 px-4 text-left text-sm font-semibold text-gray-600">Provider</th>
                            <th scope="col" class="py-3.5 px-4 text-left text-sm font-semibold text-gray-600">Dates</th>
                            <th scope="col" class="py-3.5 px-4 text-left text-sm font-semibold text-gray-600">Total Price</th>
                            <th scope="col" class="py-3.5 px-4 text-left text-sm font-semibold text-gray-600">Booking Status</th>
                            <th scope="col" class="py-3.5 px-4 text-left text-sm font-semibold text-gray-600">Payment Status</th>
                            <th scope="col" class="py-3.5 px-4 text-left text-sm font-semibold text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm font-medium text-gray-900">#<?php echo htmlspecialchars($row['id']); ?></td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700"><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700"><?php echo htmlspecialchars($row['company_name']); ?></td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">
                                        <?php echo date('M d, Y', strtotime($row['booking_date_from'])); ?> - 
                                        <?php echo date('M d, Y', strtotime($row['booking_date_to'])); ?>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">₱<?php echo number_format($row['total_price'], 2); ?></td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm">
                                        <?php echo getStatusBadge($row['booking_status'], 'booking'); ?>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm">
                                        <?php echo getStatusBadge($row['payment_status'], 'payment'); ?>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm font-medium">
                                        <a href="../dashboard.php?action=admin_booking_details&booking_id=<?php echo $row['id']; ?>" 
                                           target="_top" 
                                           class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-eye mr-1"></i> View Details
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="px-4 py-6 text-center text-gray-500">
                                    No bookings found matching this filter.
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php
                        $stmt->close();
                        $conn->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>