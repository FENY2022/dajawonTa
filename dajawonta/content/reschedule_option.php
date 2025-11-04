<?php
session_start();
include '../db.php'; // Go up one directory to find db.php

// Ensure user is logged in and is a Service Provider (role '1')
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_rules'] != '1') {
    echo "<div class='p-4 text-red-600'>Access Denied. You must be logged in as a Service Provider to view this page.</div>";
    exit;
}

$provider_user_id = $_SESSION['user_id'];
$booking_id_to_reschedule = null;
$booking_details = null;
$provider_availability = null;
$reschedule_history = [];
$message = '';
$message_type = ''; // 'success' or 'error'

// --- FORM SUBMISSION (POST REQUEST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['booking_id'])) {
    $booking_id = (int)$_POST['booking_id'];
    $new_date_from = $_POST['new_date_from'];
    $new_date_to = $_POST['new_date_to'];
    $new_time_from = $_POST['new_time_from'];
    $new_time_to = $_POST['new_time_to'];
    $reschedule_reason = trim($_POST['reschedule_reason']);

    if (empty($reschedule_reason)) {
        $message = "A reason for rescheduling is required.";
        $message_type = 'error';
        $booking_id_to_reschedule = $booking_id; // Keep the form open
    } else {
        // Start transaction
        $conn->begin_transaction();
        try {
            // 1. Get old booking data for logging
            $stmt_old = $conn->prepare("SELECT booking_date_from, booking_date_to, booking_time_from, booking_time_to FROM bookings WHERE id = ?");
            $stmt_old->bind_param("i", $booking_id);
            $stmt_old->execute();
            $old_data = $stmt_old->get_result()->fetch_assoc();
            $stmt_old->close();

            // 2. Update the booking
            $stmt_update = $conn->prepare(
                "UPDATE bookings 
                 SET booking_date_from = ?, booking_date_to = ?, booking_time_from = ?, booking_time_to = ?, booking_status = 'rescheduled' 
                 WHERE id = ?"
            );
            $stmt_update->bind_param("ssssi", $new_date_from, $new_date_to, $new_time_from, $new_time_to, $booking_id);
            $stmt_update->execute();
            $stmt_update->close();

            // 3. Log the change in the new table
            $stmt_log = $conn->prepare(
                "INSERT INTO booking_reschedules 
                 (booking_id, rescheduled_by_user_id, reason, 
                  old_booking_date_from, old_booking_date_to, old_booking_time_from, old_booking_time_to, 
                  new_booking_date_from, new_booking_date_to, new_booking_time_from, new_booking_time_to) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt_log->bind_param(
                "iisssssssss",
                $booking_id,
                $provider_user_id,
                $reschedule_reason,
                $old_data['booking_date_from'],
                $old_data['booking_date_to'],
                $old_data['booking_time_from'],
                $old_data['booking_time_to'],
                $new_date_from,
                $new_date_to,
                $new_time_from,
                $new_time_to
            );
            $stmt_log->execute();
            $stmt_log->close();

            // 4. Commit transaction
            $conn->commit();
            $message = "Booking #$booking_id has been successfully rescheduled.";
            $message_type = 'success';
            // No booking ID set, so it will return to the list view

        } catch (Exception $e) {
            $conn->rollback();
            $message = "An error occurred while rescheduling: " . $e->getMessage();
            $message_type = 'error';
            $booking_id_to_reschedule = $booking_id; // Keep the form open
        }
    }
}
// --- END FORM SUBMISSION ---


// --- PAGE LOAD (GET REQUEST) ---
if (isset($_GET['booking_id']) || $booking_id_to_reschedule !== null) {
    // If a booking_id is in the URL (or from a failed POST), show the specific form
    $booking_id = $booking_id_to_reschedule ?? (int)$_GET['booking_id'];

    // Fetch booking details AND provider's availability
    // We join service_providers to ensure the provider_user_id matches the logged-in user (for security)
    $stmt = $conn->prepare(
        "SELECT 
            b.id as booking_id, b.customer_name, b.customer_phone, b.booking_date_from, b.booking_date_to, b.booking_time_from, b.booking_time_to, b.booking_status,
            sp.id as provider_id, sp.available_date_from as provider_date_from, sp.available_date_to as provider_date_to, sp.available_time_from as provider_time_from, sp.available_time_to as provider_time_to
         FROM bookings b
         JOIN service_providers sp ON b.provider_id = sp.id
         WHERE b.id = ? AND sp.user_id = ?"
    );
    $stmt->bind_param("ii", $booking_id, $provider_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $booking_details = $result->fetch_assoc();
        
        // --- Handle invalid dates ---
        $date_from_raw = $booking_details['provider_date_from'];
        $date_to_raw = $booking_details['provider_date_to'];
        $time_from_raw = $booking_details['provider_time_from'];
        $time_to_raw = $booking_details['provider_time_to'];

        // Check for '0000-00-00' or null/empty
        $valid_date_from = ($date_from_raw && $date_from_raw !== '0000-00-00') ? $date_from_raw : null;
        $valid_date_to = ($date_to_raw && $date_to_raw !== '0000-00-00') ? $date_to_raw : null;
        
        // Use time as-is but format for display
        $display_time_from = ($time_from_raw) ? date('h:i A', strtotime($time_from_raw)) : 'N/A';
        $display_time_to = ($time_to_raw) ? date('h:i A', strtotime($time_to_raw)) : 'N/A';

        $provider_availability = [
            'date_from' => $valid_date_from, // Will be null if invalid
            'date_to' => $valid_date_to,     // Will be null if invalid
            'time_from' => $time_from_raw,   // Raw value for min/max
            'time_to' => $time_to_raw,       // Raw value for min/max
            'display_date_from' => $valid_date_from ?? 'Not Set', // Value for display
            'display_date_to' => $valid_date_to ?? 'Not Set',     // Value for display
            'display_time_from' => $display_time_from,
            'display_time_to' => $display_time_to,
        ];

        // Fetch reschedule history
        $stmt_history = $conn->prepare("SELECT * FROM booking_reschedules WHERE booking_id = ? ORDER BY created_at DESC");
        $stmt_history->bind_param("i", $booking_id);
        $stmt_history->execute();
        $reschedule_history = $stmt_history->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_history->close();

    } else {
        $message = "Booking not found or you do not have permission to edit it.";
        $message_type = 'error';
    }
    $stmt->close();
}
// --- END PAGE LOAD ---

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule Booking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
        }
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        .table-wrapper {
            overflow-x: auto;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th, .table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .table th {
            background-color: #f8fafc;
            font-weight: 600;
            color: #475569;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .table td {
            font-size: 0.875rem;
            color: #334155;
        }
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        .table tbody tr:hover {
            background-color: #f1f5f9;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 999px;
            font-weight: 500;
            font-size: 0.75rem;
        }
        .status-approved { background-color: #dcfce7; color: #166534; }
        .status-pending { background-color: #fef9c3; color: #854d0e; }
        .status-cancelled { background-color: #fee2e2; color: #991b1b; }
        .status-rescheduled { background-color: #e0e7ff; color: #3730a3; }
        
        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: none;
        }
        .btn-primary {
            background-color: #0D6EFD;
            color: white;
        }
        .btn-primary:hover {
            background-color: #0B5ED7;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .btn-outline {
            background-color: transparent;
            color: #0D6EFD;
            border: 1px solid #0D6EFD;
        }
        .btn-outline:hover {
            background-color: #e0e7ff;
        }
        .form-input {
            width: 100%;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            transition: all 0.2s ease;
        }
        .form-input:focus {
            outline: none;
            border-color: #0D6EFD;
            background-color: white;
            box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.2);
        }
        .form-label {
            display: block;
            font-weight: 500;
            color: #334155;
            margin-bottom: 6px;
            font-size: 0.875rem;
        }
    </style>
</head>
<body class="p-4 md:p-8">
    <div class="max-w-6xl mx-auto">

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($booking_details): ?>
            <!-- --- RESCHEDULE FORM --- -->
            <div class="mb-8">
                <a href="reschedule_option.php" class="btn btn-outline mb-4">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Bookings List
                </a>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Reschedule Booking #<?php echo htmlspecialchars($booking_details['booking_id']); ?></h1>
                <p class="text-gray-600">Customer: <?php echo htmlspecialchars($booking_details['customer_name']); ?></p>
            </div>

            <form action="reschedule_option.php" method="POST">
                <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking_details['booking_id']); ?>">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Provider Availability Card -->
                    <div class="card md:col-span-1">
                        <div class="p-5 border-b">
                            <h3 class="text-lg font-semibold text-gray-700">Your Availability</h3>
                        </div>
                        <div class="p-5 space-y-4">
                            <div>
                                <label class="form-label">Available From Date</label>
                                <input type="text" readonly class="form-input bg-gray-100" value="<?php echo htmlspecialchars($provider_availability['display_date_from']); ?>">
                            </div>
                            <div>
                                <label class="form-label">Available To Date</label>
                                <input type="text" readonly class="form-input bg-gray-100" value="<?php echo htmlspecialchars($provider_availability['display_date_to']); ?>">
                            </div>
                            <div>
                                <label class="form-label">Available From Time</label>
                                <input type="text" readonly class="form-input bg-gray-100" value="<?php echo htmlspecialchars($provider_availability['display_time_from']); ?>">
                            </div>
                            <div>
                                <label class="form-label">Available To Time</label>
                                <input type="text" readonly class="form-input bg-gray-100" value="<?php echo htmlspecialchars($provider_availability['display_time_to']); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Reschedule Form Card -->
                    <div class="card md:col-span-2">
                        <div class="p-5 border-b flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-700">Update Schedule</h3>
                            <span class="status-badge <?php echo 'status-' . strtolower(htmlspecialchars($booking_details['booking_status'])); ?>">
                                <?php echo htmlspecialchars(ucfirst($booking_details['booking_status'])); ?>
                            </span>
                        </div>
                        <div class="p-5">
                            <!-- Current Details -->
                            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <h4 class="font-semibold text-blue-800 mb-2">Current Booking Time</h4>
                                <p class="text-blue-700">
                                    <strong>Date:</strong> <?php echo htmlspecialchars($booking_details['booking_date_from']); ?> to <?php echo htmlspecialchars($booking_details['booking_date_to']); ?>
                                </p>
                                <p class="text-blue-700">
                                    <strong>Time:</strong> <?php echo htmlspecialchars(date('h:i A', strtotime($booking_details['booking_time_from']))); ?> to <?php echo htmlspecialchars(date('h:i A', strtotime($booking_details['booking_time_to']))); ?>
                                </p>
                            </div>
                            
                            <!-- New Details Form -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="new_date_from" class="form-label">New Booking Date From</label>
                                    <input type="date" id="new_date_from" name="new_date_from" class="form-input" 
                                           value="<?php echo htmlspecialchars($booking_details['booking_date_from']); ?>"
                                           <?php if ($provider_availability['date_from']): ?>
                                           min="<?php echo htmlspecialchars($provider_availability['date_from']); ?>" 
                                           <?php endif; ?>
                                           <?php if ($provider_availability['date_to']): ?>
                                           max="<?php echo htmlspecialchars($provider_availability['date_to']); ?>" 
                                           <?php endif; ?>
                                           required>
                                </div>
                                <div>
                                    <label for="new_date_to" class="form-label">New Booking Date To</label>
                                    <input type="date" id="new_date_to" name="new_date_to" class="form-input" 
                                           value="<?php echo htmlspecialchars($booking_details['booking_date_to']); ?>"
                                           <?php if ($provider_availability['date_from']): ?>
                                           min="<?php echo htmlspecialchars($provider_availability['date_from']); ?>" 
                                           <?php endif; ?>
                                           <?php if ($provider_availability['date_to']): ?>
                                           max="<?php echo htmlspecialchars($provider_availability['date_to']); ?>" 
                                           <?php endif; ?>
                                           required>
                                </div>
                                <div>
                                    <label for="new_time_from" class="form-label">New Booking Time From</label>
                                    <input type="time" id="new_time_from" name="new_time_from" class="form-input" 
                                           value="<?php echo htmlspecialchars($booking_details['booking_time_from']); ?>"
                                           <?php if ($provider_availability['time_from']): ?>
                                           min="<?php echo htmlspecialchars($provider_availability['time_from']); ?>" 
                                           <?php endif; ?>
                                           <?php if ($provider_availability['time_to']): ?>
                                           max="<?php echo htmlspecialchars($provider_availability['time_to']); ?>" 
                                           <?php endif; ?>
                                           required>
                                </div>
                                <div>
                                    <label for="new_time_to" class="form-label">New Booking Time To</label>
                                    <input type="time" id="new_time_to" name="new_time_to" class="form-input" 
                                           value="<?php echo htmlspecialchars($booking_details['booking_time_to']); ?>"
                                           <?php if ($provider_availability['time_from']): ?>
                                           min="<?php echo htmlspecialchars($provider_availability['time_from']); ?>" 
                                           <?php endif; ?>
                                           <?php if ($provider_availability['time_to']): ?>
                                           max="<?php echo htmlspecialchars($provider_availability['time_to']); ?>" 
                                           <?php endif; ?>
                                           required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="reschedule_reason" class="form-label">Reason for Rescheduling</label>
                                <textarea id="reschedule_reason" name="reschedule_reason" rows="3" class="form-input" placeholder="e.g., Client request, personal emergency, etc." required></textarea>
                            </div>
                            <div class="text-right">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-2"></i>
                                    Confirm & Reschedule
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Reschedule History -->
            <?php if (!empty($reschedule_history)): ?>
            <div class="card mt-8">
                <div class="p-5 border-b">
                    <h3 class="text-lg font-semibold text-gray-700">Reschedule History</h3>
                </div>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Changed On</th>
                                <th>Reason</th>
                                <th>Old Dates</th>
                                <th>Old Times</th>
                                <th>New Dates</th>
                                <th>New Times</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reschedule_history as $log): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($log['created_at']))); ?></td>
                                <td class="max-w-xs truncate"><?php echo htmlspecialchars($log['reason']); ?></td>
                                <td><?php echo htmlspecialchars($log['old_booking_date_from']); ?> to <?php echo htmlspecialchars($log['old_booking_date_to']); ?></td>
                                <td><?php echo htmlspecialchars(date('h:i A', strtotime($log['old_booking_time_from']))); ?> - <?php echo htmlspecialchars(date('h:i A', strtotime($log['old_booking_time_to']))); ?></td>
                                <td><?php echo htmlspecialchars($log['new_booking_date_from']); ?> to <?php echo htmlspecialchars($log['new_booking_date_to']); ?></td>
                                <td><?php echo htmlspecialchars(date('h:i A', strtotime($log['new_booking_time_from']))); ?> - <?php echo htmlspecialchars(date('h:i A', strtotime($log['new_booking_time_to']))); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- --- BOOKINGS LIST --- -->
            <div class="card">
                <div class="p-5 border-b">
                    <h1 class="text-2xl font-bold text-gray-800">Select a Booking to Reschedule</h1>
                    <p class="text-gray-600">Showing all 'pending', 'approved', and 'rescheduled' bookings.</p>
                </div>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Booking Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch all relevant bookings for this provider
                            $stmt_list = $conn->prepare(
                                "SELECT b.id, b.customer_name, b.booking_date_from, b.booking_date_to, b.booking_status 
                                 FROM bookings b
                                 JOIN service_providers sp ON b.provider_id = sp.id
                                 WHERE sp.user_id = ? AND b.booking_status IN ('pending', 'approved', 'rescheduled')
                                 ORDER BY b.booking_date_from DESC"
                            );
                            $stmt_list->bind_param("i", $provider_user_id);
                            $stmt_list->execute();
                            $result_list = $stmt_list->get_result();
                            
                            if ($result_list->num_rows > 0):
                                while ($row = $result_list->fetch_assoc()):
                            ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['booking_date_from']); ?> to <?php echo htmlspecialchars($row['booking_date_to']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo 'status-' . strtolower(htmlspecialchars($row['booking_status'])); ?>">
                                            <?php echo htmlspecialchars(ucfirst($row['booking_status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="reschedule_option.php?booking_id=<?php echo $row['id']; ?>" class="btn btn-outline">
                                            <i class="fas fa-edit mr-2"></i> Reschedule
                                        </a>
                                    </td>
                                </tr>
                            <?php
                                endwhile;
                            else:
                            ?>
                                <tr>
                                    <td colspan="5" class="text-center text-gray-500 py-6">No active bookings found.</td>
                                </tr>
                            <?php
                            endif;
                            $stmt_list->close();
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

