<?php
session_start();

// 1. Include your database connection
require_once '../db.php'; 

// 2. Get customer ID from session
$customer_id = $_SESSION['user_id'] ?? 0; // Default to 0 if not set

// --- AJAX Endpoint Simulation for Detail Fetching ---
if (isset($_GET['action']) && $_GET['action'] === 'get_booking_details' && isset($_GET['booking_id'])) {
    header('Content-Type: application/json');
    $booking_id = intval($_GET['booking_id']);

    if ($booking_id > 0 && $customer_id > 0) { // Also check if customer_id is valid
        // --- !! MODIFIED SQL QUERY (Fix for "Failed to load") ---
        // We now join the 'users' table twice:
        // 1. as 'u_provider' to get the provider's name
        // 2. as 'u_customer' to get the customer's name
        $sql_detail = "SELECT 
            b.*,
            s.service_name,
            s.description AS service_description,
            CONCAT(u_provider.first_name, ' ', u_provider.last_name) AS provider_name,
            u_provider.email AS provider_email,
            u_provider.phone AS provider_phone,
            COALESCE(u_provider.profile_image, CONCAT('https://i.pravatar.cc/150?u=', u_provider.id)) AS provider_avatar,
            CONCAT(u_customer.first_name, ' ', u_customer.last_name) AS customer_name 
        FROM 
            bookings AS b
        JOIN 
            service_providers AS sp ON b.provider_id = sp.id
        JOIN 
            users AS u_provider ON sp.user_id = u_provider.id
        JOIN 
            services AS s ON sp.service_id = s.service_id
        JOIN
            users AS u_customer ON b.customer_id = u_customer.id
        WHERE 
            b.id = ? AND b.customer_id = ?"; // Important: Check customer_id for security

        $stmt_detail = $conn->prepare($sql_detail);
        
        if ($stmt_detail) {
            $stmt_detail->bind_param("ii", $booking_id, $customer_id);
            $stmt_detail->execute();
            $result_detail = $stmt_detail->get_result();
            
            if ($result_detail && $row = $result_detail->fetch_assoc()) {
                // Formatting dates and times for display
                $row['display_date_from'] = date('F j, Y', strtotime($row['booking_date_from']));
                $row['display_time_from'] = date('g:i A', strtotime($row['booking_time_from']));
                $row['display_date_to'] = date('F j, Y', strtotime($row['booking_date_to']));
                $row['display_time_to'] = date('g:i A', strtotime($row['booking_time_to']));
                
                echo json_encode(['success' => true, 'booking' => $row]);
            } else {
                // --- !! IMPROVED ERROR MESSAGE ---
                echo json_encode(['success' => false, 'message' => "Booking not found or access denied. (Booking ID: $booking_id, Customer ID: $customer_id)"]);
            }
            $stmt_detail->close();
        } else {
            // --- !! IMPROVED ERROR MESSAGE ---
            echo json_encode(['success' => false, 'message' => 'Database query preparation failed: ' . $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => "Invalid booking ID or customer is not logged in. (Customer ID: $customer_id)"]);
    }
    $conn->close();
    exit; // Stop further execution
}
// --- End of AJAX Endpoint Simulation ---


// 3. Prepare and execute the query to fetch real data for the main list
$bookings = [];

if ($customer_id > 0) {
    // --- !! MODIFIED SQL QUERY (Added b.booking_status) ---
    // We need 'b.booking_status' (e.g., 'pending') for the new cancel logic
    $sql = "SELECT 
                b.id,
                b.total_price, 
                b.booking_status, -- <-- ADDED THIS LINE
                b.payment_status,
                s.service_name,
                CONCAT(u.first_name, ' ', u.last_name) AS provider_name,
                COALESCE(u.profile_image, CONCAT('https://i.pravatar.cc/150?u=', u.id)) AS provider_avatar,
                CONCAT(b.booking_date_from, ' ', b.booking_time_from) AS schedule_date,
                CASE 
                    WHEN b.booking_status = 'pending' THEN 'Upcoming'
                    WHEN b.booking_status = 'approved' THEN 'Upcoming'
                    WHEN b.booking_status = 'completed' THEN 'Completed'
                    WHEN b.booking_status = 'cancelled' THEN 'Cancelled'
                    ELSE 'Upcoming'
                END AS status 
            FROM 
                bookings AS b
            JOIN 
                service_providers AS sp ON b.provider_id = sp.id
            JOIN 
                users AS u ON sp.user_id = u.id
            JOIN 
                services AS s ON sp.service_id = s.service_id
            WHERE 
                b.customer_id = ?
            ORDER BY 
                STR_TO_DATE(CONCAT(b.booking_date_from, ' ', b.booking_time_from), '%Y-%m-%d %H:%i:%s') DESC";


    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // Use the provider's avatar if available, otherwise fallback to a placeholder
                if (empty($row['provider_avatar'])) {
                    $row['provider_avatar'] = 'https://i.pravatar.cc/150?u=' . $row['id']; // Fallback
                }
                $bookings[] = $row;
            }
        }
        $stmt->close();
    } else {
        // Handle query preparation error if needed
        // error_log("Failed to prepare statement: " . $conn->error);
    }
}

// Close connection only if it hasn't been closed in the AJAX block
if ($conn->connect_errno === 0) {
    $conn->close();
}


// --- End of Data Fetching ---


function getStatusBadgeClass($status) {
    switch ($status) {
        case 'Upcoming': return 'bg-blue-100 text-blue-800';
        case 'Completed': return 'bg-green-100 text-green-800';
        case 'Cancelled': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

// NEW function to style the payment status
function getPaymentStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'paid': return 'bg-green-100 text-green-800';
        case 'unpaid': return 'bg-yellow-100 text-yellow-800';
        case 'failed': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f5f7fa; }
        .tab-button.active { border-color: #0D6EFD; color: #0D6EFD; background-color: white; }
        .modal-backdrop { transition: opacity 0.3s ease; }
        .modal-panel { transition: all 0.3s ease; }
    </style>
</head>
<body class="p-6 md:p-8">

    <div class="max-w-7xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">My Bookings</h1>
            <p class="text-gray-500 mt-1">Review and manage all your service appointments.</p>
        </div>

        <?php if (empty($bookings)): ?>
            <div class="text-center bg-white p-12 rounded-lg shadow-sm">
                <i class="fas fa-calendar-times text-5xl text-gray-300 mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-700">No Bookings Yet</h2>
                <p class="text-gray-500 mt-2 mb-6">You haven't booked any services. When you do, they'll appear here.</p>
                <a href="../dashboard.php?action=browse_services" target="_parent" class="bg-blue-600 text-white font-semibold px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                    Browse Services
                </a>
            </div>
        <?php else: ?>
            <div class="mb-6 border-b border-gray-200">
                <nav class="-mb-px flex space-x-6" id="booking-tabs">
                    <button data-tab="upcoming" class="tab-button active whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm text-gray-500 hover:border-gray-300">Upcoming</button>
                    <button data-tab="completed" class="tab-button whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm text-gray-500 hover:border-gray-300">Completed</button>
                    <button data-tab="cancelled" class="tab-button whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm text-gray-500 hover:border-gray-300">Cancelled</button>
                </nav>
            </div>

            <div id="booking-lists">
                <?php foreach ($bookings as $booking): ?>
                <div class="booking-card bg-white p-5 rounded-lg shadow-sm mb-4 flex-col md:flex-row flex items-start md:items-center justify-between space-y-4 md:space-y-0" data-status="<?php echo strtolower($booking['status']); ?>">
                    <div class="flex items-center w-full md:w-auto">
                        <img src="<?php echo htmlspecialchars($booking['provider_avatar']); ?>" alt="Provider" class="h-12 w-12 rounded-full object-cover mr-4">
                        <div>
                            <p class="font-bold text-gray-800"><?php echo htmlspecialchars($booking['service_name']); ?></p>
                            <p class="text-sm text-gray-500">with <?php echo htmlspecialchars($booking['provider_name']); ?></p>
                        </div>
                    </div>
                    <div class="flex flex-col md:items-center text-left md:text-center w-full md:w-auto">
                            <p class="font-semibold text-gray-700"><?php echo date('D, M j, Y', strtotime($booking['schedule_date'])); ?></p>
                            <p class="text-sm text-gray-500"><?php echo date('g:i A', strtotime($booking['schedule_date'])); ?></p>
                    </div>

                    <div class="w-full md:w-auto flex flex-col space-y-2 md:items-end">
                        <span class="px-3 py-1 text-sm font-medium rounded-full <?php echo getStatusBadgeClass($booking['status']); ?>">
                            <?php echo htmlspecialchars($booking['status']); ?>
                        </span>
                        <span class="px-3 py-1 text-sm font-medium rounded-full <?php echo getPaymentStatusBadgeClass($booking['payment_status']); ?>">
                            <?php echo htmlspecialchars(ucfirst($booking['payment_status'])); ?>
                        </span>
                    </div>
                    <div class="flex items-center justify-end space-x-2 w-full md:w-auto">
                        
                        <?php 
                        // Condition: Can cancel if payment is 'unpaid' OR booking status is 'pending'
                        $can_cancel = (strtolower($booking['payment_status']) == 'unpaid' || strtolower($booking['booking_status']) == 'pending');
                        
                        if ($can_cancel && strtolower($booking['status']) != 'cancelled'): 
                        ?>
                            <button onclick="openCancelModal(<?php echo $booking['id']; ?>)" class="px-4 py-2 text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-lg">Cancel</button>
                            <button onclick="openDetailsModal(<?php echo $booking['id']; ?>)" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">Details</button>
                        
                        <?php elseif ($booking['status'] == 'Upcoming'): // Upcoming but cannot be cancelled (e.g., paid and approved) ?>
                            <button onclick="openDetailsModal(<?php echo $booking['id']; ?>)" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">Details</button>
                        
                        <?php elseif ($booking['status'] == 'Completed'): ?>
                            <button class="px-4 py-2 text-sm font-medium text-yellow-600 bg-yellow-50 hover:bg-yellow-100 rounded-lg">Leave a Review</button>
                            <button class="px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg">Book Again</button>
                        
                        <?php else: // Cancelled ?>
                            <button onclick="openDetailsModal(<?php echo $booking['id']; ?>)" class_name="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">View Details</button>
                        <?php endif; ?>

                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div id="cancel-modal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div id="modal-backdrop" class="fixed inset-0 bg-gray-500 bg-opacity-75 modal-backdrop" style="opacity: 0;"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div id="modal-panel" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform sm:my-8 sm:align-middle sm:max-w-lg sm:w-full modal-panel" style="opacity: 0; transform: translateY(20px);">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-exclamation-triangle text-red-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Cancel Booking</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">Are you sure you want to cancel this booking? This action cannot be undone.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <form action="path/to/cancel_booking_handler.php" method="POST">
                        <input type="hidden" name="booking_id" id="booking_id_to_cancel">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">
                            Yes, Cancel
                        </button>
                    </form>
                    <button type="button" onclick="closeCancelModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">
                        Go Back
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div id="details-modal" class="fixed inset-0 z-50 hidden" aria-labelledby="details-modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div id="details-modal-backdrop" class="fixed inset-0 bg-gray-500 bg-opacity-75 modal-backdrop" style="opacity: 0;"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div id="details-modal-panel" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform sm:my-8 sm:align-middle sm:max-w-xl sm:w-full modal-panel" style="opacity: 0; transform: translateY(20px);">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-xl font-bold text-gray-900 mb-4" id="details-modal-title">Booking Details</h3>
                    
                    <div id="booking-details-content" class="space-y-4">
                        <div class="text-center py-8" id="details-loading">
                            <i class="fas fa-spinner fa-spin text-2xl text-blue-500"></i>
                            <p class="text-gray-500 mt-2">Loading details...</p>
                        </div>
                        <div id="details-error" class="hidden text-center py-8">
                            <i class="fas fa-times-circle text-2xl text-red-500"></i>
                            <p class="text-red-500 mt-2" id="details-error-message">Failed to load booking details.</p>
                        </div>
                        
                        <div id="details-data" class="hidden">
                            <div class="border-b pb-3 mb-3">
                                <p class="text-lg font-bold text-blue-600" id="detail-service-name"></p>
                                <div class="flex items-center mt-2">
                                    <img id="detail-provider-avatar" src="" alt="Provider" class="h-10 w-10 rounded-full object-cover mr-3">
                                    <p class="text-md text-gray-600">Provider: <span class="font-semibold" id="detail-provider-name"></span></p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Schedule (From)</p>
                                    <p class="font-semibold text-gray-800" id="detail-schedule-from"></p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Schedule (To)</p>
                                    <p class="font-semibold text-gray-800" id="detail-schedule-to"></p>
                                </div>
                            </div>

                            <div>
                                <p class="text-sm font-medium text-gray-500 mt-3">Customer Name</p>
                                <p class="font-semibold text-gray-800" id="detail-customer-name"></p>
                            </div>

                            <div class="grid grid-cols-3 gap-4 border-t pt-3 mt-3">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Total Price</p>
                                    <p class="font-bold text-xl text-green-700" id="detail-total-price"></p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Booking Status</p>
                                    <span id="detail-booking-status" class="px-3 py-1 text-xs font-medium rounded-full"></span>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Payment Status</p>
                                    <span id="detail-payment-status" class="px-3 py-1 text-xs font-medium rounded-full"></span>
                                </div>
                            </div>

                            <div class="border-t pt-3 mt-3">
                                <p class="text-sm font-medium text-gray-500">Special Request</p>
                                <p class="text-gray-800 italic" id="detail-special-request"></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" onclick="closeDetailsModal()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
   <script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.tab-button');
        const bookingCards = document.querySelectorAll('.booking-card');

        // --- Tab Filtering Logic ---
        function filterBookings(tab) {
            const status = tab.dataset.tab;

            // Update tab styling
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            // Show/Hide booking cards based on selected status
            bookingCards.forEach(card => {
                if (card.dataset.status === status) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        tabs.forEach(tab => {
            tab.addEventListener('click', () => filterBookings(tab));
        });
        
        // **Initial filter on load**: Ensures only 'Upcoming' bookings are shown when the page first loads.
        if (document.querySelector('.tab-button.active')) {
            filterBookings(document.querySelector('.tab-button.active'));
        }
    });

    // --- Modal Utility Functions (Must be global to be called by inline onclick) ---

    function getStatusBadgeClass(status) {
        status = status.toLowerCase();
        // Matching the PHP logic for status colors
        if (status === 'upcoming' || status === 'approved' || status === 'pending') return 'bg-blue-100 text-blue-800';
        if (status === 'completed') return 'bg-green-100 text-green-800';
        if (status === 'cancelled') return 'bg-red-100 text-red-800';
        return 'bg-gray-100 text-gray-800';
    }
    
    function getPaymentStatusBadgeClass(status) {
        status = status.toLowerCase();
        // Matching the PHP logic for payment status colors
        if (status === 'paid') return 'bg-green-100 text-green-800';
        if (status === 'unpaid') return 'bg-yellow-100 text-yellow-800';
        if (status === 'failed') return 'bg-red-100 text-red-800';
        return 'bg-gray-100 text-gray-800';
    }


    // --- 1. Cancel Modal Logic ---
    const cancelModal = document.getElementById('cancel-modal');
    const cancelModalBackdrop = document.getElementById('modal-backdrop');
    const cancelModalPanel = document.getElementById('modal-panel');
    const bookingIdInput = document.getElementById('booking_id_to_cancel');

    window.openCancelModal = function(bookingId) {
        bookingIdInput.value = bookingId;
        cancelModal.classList.remove('hidden');
        setTimeout(() => {
            cancelModalBackdrop.style.opacity = 1;
            cancelModalPanel.style.opacity = 1;
            cancelModalPanel.style.transform = 'translateY(0)';
        }, 10);
    }

    window.closeCancelModal = function() {
        cancelModalBackdrop.style.opacity = 0;
        cancelModalPanel.style.opacity = 0;
        cancelModalPanel.style.transform = 'translateY(20px)';
        setTimeout(() => {
            cancelModal.classList.add('hidden');
        }, 300);
    }


    // --- 2. Details Modal Logic (AJAX Fetching) ---
    const detailsModal = document.getElementById('details-modal');
    const detailsModalBackdrop = document.getElementById('details-modal-backdrop');
    const detailsModalPanel = document.getElementById('details-modal-panel');
    const detailsLoading = document.getElementById('details-loading');
    const detailsError = document.getElementById('details-error');
    const detailsErrorMessage = document.getElementById('details-error-message');
    const detailsData = document.getElementById('details-data');
    
    window.openDetailsModal = async function(bookingId) {
        // 1. Setup initial modal state (show loading)
        detailsModal.classList.remove('hidden');
        detailsLoading.classList.remove('hidden');
        detailsError.classList.add('hidden');
        detailsData.classList.add('hidden');
        detailsErrorMessage.textContent = 'Failed to load booking details.'; // Reset error message
        
        // Animate modal open
        setTimeout(() => {
            detailsModalBackdrop.style.opacity = 1;
            detailsModalPanel.style.opacity = 1;
            detailsModalPanel.style.transform = 'translateY(0)';
        }, 10);

        try {
            // 2. Fetch data via AJAX (calls the PHP endpoint at the top of the file)
            const response = await fetch(`?action=get_booking_details&booking_id=${bookingId}`);
            const data = await response.json();
            
            detailsLoading.classList.add('hidden');

            if (data.success) {
                const booking = data.booking;
                
                // 3. Populate modal fields with fetched data
                document.getElementById('detail-service-name').textContent = booking.service_name;
                document.getElementById('detail-provider-avatar').src = booking.provider_avatar;
                document.getElementById('detail-provider-name').textContent = booking.provider_name;
                
                document.getElementById('detail-schedule-from').textContent = `${booking.display_date_from} @ ${booking.display_time_from}`;
                document.getElementById('detail-schedule-to').textContent = `${booking.display_date_to} @ ${booking.display_time_to}`;

                // **Fixed field**: Customer Name (thanks to the updated SQL query!)
                document.getElementById('detail-customer-name').textContent = booking.customer_name; 
                
                document.getElementById('detail-total-price').textContent = '₱' + parseFloat(booking.total_price).toFixed(2);
                
                // Status Badges
                const bookingStatusEl = document.getElementById('detail-booking-status');
                bookingStatusEl.textContent = booking.booking_status.charAt(0).toUpperCase() + booking.booking_status.slice(1);
                bookingStatusEl.className = `px-3 py-1 text-xs font-medium rounded-full ${getStatusBadgeClass(booking.booking_status)}`;

                const paymentStatusEl = document.getElementById('detail-payment-status');
                paymentStatusEl.textContent = booking.payment_status.charAt(0).toUpperCase() + booking.payment_status.slice(1);
                paymentStatusEl.className = `px-3 py-1 text-xs font-medium rounded-full ${getPaymentStatusBadgeClass(booking.payment_status)}`;

                document.getElementById('detail-special-request').textContent = booking.special_request || 'None';
                
                // Show the data section
                detailsData.classList.remove('hidden');

            } else {
                // Handle API error (e.g., "Booking not found or access denied")
                detailsErrorMessage.textContent = data.message || 'Failed to load booking details due to server issue.';
                detailsError.classList.remove('hidden');
                console.error('API Error:', data.message);
            }

        } catch (error) {
            // Handle network or JSON parsing error
            detailsLoading.classList.add('hidden');
            detailsErrorMessage.textContent = 'A network error or unexpected response occurred.';
            detailsError.classList.remove('hidden');
            console.error('Fetch Error:', error);
        }
    }

    window.closeDetailsModal = function() {
        detailsModalBackdrop.style.opacity = 0;
        detailsModalPanel.style.opacity = 0;
        detailsModalPanel.style.transform = 'translateY(20px)';
        setTimeout(() => {
            detailsModal.classList.add('hidden');
        }, 300);
    }
</script>

</body>
</html>