<?php
session_start();
// Go up one directory to include the database connection
// Make sure '../db.php' is the correct path from this file's location
include '../db.php'; 

// --- Initialize variables for messages ---
$success_message = null;
$error_message = null;

// --- Handle POST request (Mark as Completed) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Check if user is logged in and is a Service Provider
    if (isset($_SESSION['loggedin'], $_SESSION['user_rules']) && $_SESSION['loggedin'] === true && $_SESSION['user_rules'] == '1') {
        
        if (isset($_POST['booking_id'], $_POST['new_status'])) {
            
            $booking_id = $_POST['booking_id'];
            $new_status = $_POST['new_status'];
            $user_id = $_SESSION['user_id'];

            // Only allow 'completed' status via this form
            if ($new_status === 'completed') {
                
                // --- Authorization Check: Does this provider own this booking? ---
                
                // 1. Get all provider IDs for the logged-in user
                $provider_ids = [];
                $stmt_provider = $conn->prepare("SELECT id FROM service_providers WHERE user_id = ?");
                $stmt_provider->bind_param("i", $user_id);
                $stmt_provider->execute();
                $result_provider = $stmt_provider->get_result();
                while ($row = $result_provider->fetch_assoc()) {
                    $provider_ids[] = $row['id'];
                }
                $stmt_provider->close();

                if (empty($provider_ids)) {
                    $error_message = "Authorization Error: You have no provider accounts.";
                } else {
                    // 2. Get the provider_id and details for the booking being updated
                    $stmt_check = $conn->prepare("SELECT provider_id, booking_date_to, booking_status FROM bookings WHERE id = ?");
                    $stmt_check->bind_param("i", $booking_id);
                    $stmt_check->execute();
                    $result_check = $stmt_check->get_result();
                    
                    if ($result_check->num_rows > 0) {
                        $booking = $result_check->fetch_assoc();
                        $booking_provider_id = $booking['provider_id'];

                        // 3. Check if the booking's provider_id is in the user's list of provider_ids
                        if (in_array($booking_provider_id, $provider_ids)) {
                            
                            // 4. Server-side check: Is status 'approved' and date passed?
                            $current_date = date('Y-m-d');
                            if (strtolower($booking['booking_status']) == 'approved' && $booking['booking_date_to'] < $current_date) {
                                
                                // All checks passed: Update the booking status
                                $stmt_update = $conn->prepare("UPDATE bookings SET booking_status = ? WHERE id = ?");
                                $stmt_update->bind_param("si", $new_status, $booking_id);
                                
                                if ($stmt_update->execute()) {
                                    $success_message = "Booking #" . htmlspecialchars($booking_id) . " has been marked as completed.";
                                } else {
                                    $error_message = "Error updating booking: " . $stmt_update->error;
                                }
                                $stmt_update->close();

                            } else if (strtolower($booking['booking_status']) != 'approved') {
                                $error_message = "Cannot complete booking: It is not in an 'approved' state.";
                            } else {
                                $error_message = "Cannot complete booking: The booking end date has not passed yet.";
                            }

                        } else {
                            $error_message = "Authorization Error: You do not own this booking.";
                        }
                    } else {
                        $error_message = "Error: Booking not found.";
                    }
                    $stmt_check->close();
                }
            } else {
                $error_message = "Invalid action.";
            }
        } else {
            $error_message = "Invalid request.";
        }
    } else {
        // Not logged in or not a provider, redirect
        echo "<script>window.top.location.href = '../login.php';</script>";
        exit;
    }
}

// --- Page Load Logic (Always Runs) ---

// Redirect to login if not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
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

// 2. Fetch all bookings for those provider IDs (will fetch updated data)
if (!empty($provider_ids)) {
    $placeholders = implode(',', array_fill(0, count($provider_ids), '?'));
    $types = str_repeat('i', count($provider_ids));

    $sql_bookings = "SELECT b.*, sp.service_name 
                     FROM bookings b
                     JOIN service_providers sp ON b.provider_id = sp.id
                     WHERE b.provider_id IN ($placeholders)
                     ORDER BY b.created_at DESC";
    
    $stmt_bookings = $conn->prepare($sql_bookings);
    $stmt_bookings->bind_param($types, ...$provider_ids);
    $stmt_bookings->execute();
    $bookings_result = $stmt_bookings->get_result();

    if ($bookings_result) {
        $bookings = $bookings_result->fetch_all(MYSQLI_ASSOC);
    }
    $stmt_bookings->close();
}
$conn->close();

// Helper function to format status badges
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
        case 'rescheduled':
            return 'bg-purple-100 text-purple-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

// Get current date once for comparison
$current_date = date('Y-m-d');
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
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
        }
        :root {
            --primary: #0D6EFD;
        }
        .text-primary { color: var(--primary); }
        .hover\:text-primary-dark:hover { color: #0B5ED7; }

        /* --- Toast Notification Styles --- */
        #toast-container {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .toast {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            color: white;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease-in-out;
            min-width: 300px;
        }
        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }
        .toast.hide {
            opacity: 0;
            transform: translateX(100%);
        }
        .toast-success {
            background-color: #10B981; /* Green */
        }
        .toast-error {
            background-color: #EF4444; /* Red */
        }
        .toast-icon {
            margin-right: 0.75rem;
            font-size: 1.25rem;
        }
    </style>
</head>
<body class="p-4 md:p-8">

    <!-- --- Toast Notification Container --- -->
    <div id="toast-container"></div>

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
                                        <?php if ($booking['booking_date_from'] != $booking['booking_date_to']): ?>
                                            - <?php echo date('M d, Y', strtotime($booking['booking_date_to'])); ?>
                                        <?php endif; ?>
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

                                        <?php
                                        // --- Show "Mark as Completed" button ---
                                        // This button is shown only if the booking is 'approved' AND the end date has passed.
                                        if (strtolower($booking['booking_status']) == 'approved' && $booking['booking_date_to'] < $current_date) : 
                                        ?>
                                            <!-- This form submits to this same page to trigger the POST logic at the top -->
                                            <form action="booking_details.php" method="POST" class="inline-block ml-4" onsubmit="return confirm('Are you sure you want to mark this booking as completed?');">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                <input type="hidden" name="new_status" value="completed">
                                                <button type="submit" class="text-green-600 hover:text-green-800 hover:underline">
                                                    Mark as Completed
                                                </button>
                                            </form>
                                        <?php endif; ?>
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

    <!-- --- Toast Notification Script --- -->
    <script>
        /**
         * Displays a toast notification.
         * @param {string} message The message to display.
         * @param {string} type 'success' or 'error'.
         */
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            if (!container) return;
            
            const toast = document.createElement('div');
            
            let iconClass = 'fa-check-circle';
            let toastClass = 'toast-success';
            
            if (type === 'error') {
                iconClass = 'fa-times-circle';
                toastClass = 'toast-error';
            }
            
            toast.className = `toast ${toastClass}`;
            toast.innerHTML = `
                <i class="fas ${iconClass} toast-icon"></i>
                <span>${message}</span>
            `;
            
            container.appendChild(toast);

            // Show toast
            setTimeout(() => {
                toast.classList.add('show');
            }, 100); // Small delay to allow transition

            // Hide toast after 4 seconds
            setTimeout(() => {
                toast.classList.add('hide');
                // Remove from DOM after transition
                toast.addEventListener('transitionend', () => {
                    if (toast.parentElement) {
                        toast.parentElement.removeChild(toast);
                    }
                });
            }, 4000);
        }

        // --- Check for PHP messages and show toasts ---
        // This PHP block will run on page load and check if the $success_message
        // or $error_message variables were set by the POST handling logic.
        <?php
        if (!empty($success_message)) {
            // Use addslashes to escape special characters (like quotes) for JavaScript
            echo "showToast('" . addslashes($success_message) . "', 'success');";
        }
        if (!empty($error_message)) {
            echo "showToast('" . addslashes($error_message) . "', 'error');";
        }
        ?>
    </script>
</body>
</html>