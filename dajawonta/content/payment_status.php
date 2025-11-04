<?php
session_start();
// Assumes db.php is in the root directory, one level up from 'content'
require_once '../db.php';

// Check if user is logged in and is a provider
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_rules'] != '1') {
    // This page is loaded in an iframe, so just show an error message.
    die("<div style='font-family: sans-serif; padding: 20px; text-align: center; color: red;'>
            Access Denied. You must be logged in as a Service Provider to view this page.
         </div>");
}

$current_user_id = $_SESSION['user_id'];
$provider_ids = [];
$bookings = [];

// 1. Find all service_provider IDs for the current user
// A single provider (user) can have multiple service listings
$stmt = $conn->prepare("SELECT id FROM service_providers WHERE user_id = ?");
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $provider_ids[] = $row['id'];
    }
}
$stmt->close();

// 2. If provider IDs were found, fetch their bookings
if (!empty($provider_ids)) {
    // Create placeholders for the IN clause (e.g., ?, ?, ?)
    $placeholders = implode(',', array_fill(0, count($provider_ids), '?'));
    
    // Get all data requested, plus payment_status and booking ID
    $sql = "SELECT id, customer_name, customer_email, booking_status, is_approve, payment_status 
            FROM bookings 
            WHERE provider_id IN ($placeholders) 
            ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($sql);
    
    // Dynamically bind parameters
    // 'i' for each integer ID
    $types = str_repeat('i', count($provider_ids));
    $stmt->bind_param($types, ...$provider_ids);
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
    }
    $stmt->close();
}

$conn->close();

/**
 * Helper function to determine badge color based on status
 */
function getStatusBadge($status, $type) {
    $status_lower = strtolower($status);
    
    if ($type === 'payment') {
        switch ($status_lower) {
            case 'paid': return 'badge-green';
            case 'unpaid': return 'badge-red';
            default: return 'badge-gray';
        }
    }
    
    if ($type === 'approval') {
        // $status here is 1 (Approved) or 0 (Pending)
        return $status ? 'badge-green' : 'badge-yellow';
    }

    if ($type === 'booking') {
         switch ($status_lower) {
            case 'approved': return 'badge-green';
            case 'completed': return 'badge-blue';
            case 'pending': return 'badge-yellow';
            case 'rescheduled': return 'badge-blue';
            case 'declined': return 'badge-red';
            case 'cancelled': return 'badge-red';
            default: return 'badge-gray';
        }
    }

    return 'badge-gray';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Status</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa; /* Match dashboard main content bg */
        }
        /* Helper classes for status badges */
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem; /* 12px */
            line-height: 1rem; /* 16px */
            font-weight: 600;
            text-transform: capitalize;
            display: inline-block;
        }
        .badge-green { background-color: #d1fae5; color: #065f46; }
        .badge-red { background-color: #fee2e2; color: #991b1b; }
        .badge-yellow { background-color: #fef3c7; color: #92400e; }
        .badge-blue { background-color: #dbeafe; color: #1d4ed8; }
        .badge-gray { background-color: #f3f4f6; color: #374151; }
    </style>
</head>
<body class="p-4 md:p-6">
    <div class="container mx-auto max-w-7xl">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Payment & Booking Status</h1>
            <p class="text-sm text-gray-500">Overview of all booking statuses and payments for your services.</p>
        </div>

        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approval Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                    You do not have any booking records yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($booking['customer_name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($booking['customer_email']); ?></div>
                                    </td>
                                     <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $approval_status = $booking['is_approve'] ? 'Approved' : 'Pending';
                                        $approval_class = getStatusBadge($booking['is_approve'], 'approval');
                                        ?>
                                        <span class="badge <?php echo $approval_class; ?>"><?php echo $approval_status; ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $booking_status = htmlspecialchars($booking['booking_status']);
                                        $booking_class = getStatusBadge($booking_status, 'booking');
                                        ?>
                                        <span class="badge <?php echo $booking_class; ?>"><?php echo $booking_status; ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $payment_status = htmlspecialchars($booking['payment_status']);
                                        $payment_class = getStatusBadge($payment_status, 'payment');
                                        ?>
                                        <span class="badge <?php echo $payment_class; ?>"><?php echo $payment_status; ?></span>
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