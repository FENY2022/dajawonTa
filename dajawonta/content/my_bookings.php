<?php
session_start();

// This line is for demonstration purposes. In a real application, 
// you would include your database connection and fetch the user's bookings.
// include '../db.php'; 

// --- Placeholder Data ---
// In a real application, you would fetch this from your database based on $_SESSION['user_id']
$bookings = [
    [
        'id' => 1,
        'service_name' => 'Leaky Faucet Repair',
        'provider_name' => 'Juan Dela Cruz',
        'provider_avatar' => 'https://i.pravatar.cc/150?u=juan',
        'schedule_date' => date("Y-m-d H:i:s", strtotime("+3 days")),
        'status' => 'Upcoming' // Can be: Upcoming, Completed, Cancelled
    ],
    [
        'id' => 2,
        'service_name' => 'Electrical Wiring Inspection',
        'provider_name' => 'Maria Clara',
        'provider_avatar' => 'https://i.pravatar.cc/150?u=maria',
        'schedule_date' => date("Y-m-d H:i:s", strtotime("-1 week")),
        'status' => 'Completed'
    ],
    [
        'id' => 3,
        'service_name' => 'Full House Repainting',
        'provider_name' => 'Pedro Penduko',
        'provider_avatar' => 'https://i.pravatar.cc/150?u=pedro',
        'schedule_date' => date("Y-m-d H:i:s", strtotime("+10 days")),
        'status' => 'Upcoming'
    ],
    [
        'id' => 4,
        'service_name' => 'Aircon Cleaning Service',
        'provider_name' => 'Jose Rizal',
        'provider_avatar' => 'https://i.pravatar.cc/150?u=jose',
        'schedule_date' => date("Y-m-d H:i:s", strtotime("-2 days")),
        'status' => 'Cancelled'
    ],
    [
        'id' => 5,
        'service_name' => 'Garden Landscaping',
        'provider_name' => 'Gabriela Silang',
        'provider_avatar' => 'https://i.pravatar.cc/150?u=gabriela',
        'schedule_date' => date("Y-m-d H:i:s", strtotime("-1 month")),
        'status' => 'Completed'
    ],
];

function getStatusBadgeClass($status) {
    switch ($status) {
        case 'Upcoming': return 'bg-blue-100 text-blue-800';
        case 'Completed': return 'bg-green-100 text-green-800';
        case 'Cancelled': return 'bg-red-100 text-red-800';
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
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">My Bookings</h1>
            <p class="text-gray-500 mt-1">Review and manage all your service appointments.</p>
        </div>

        <?php if (empty($bookings)): ?>
            <!-- Empty State -->
            <div class="text-center bg-white p-12 rounded-lg shadow-sm">
                <i class="fas fa-calendar-times text-5xl text-gray-300 mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-700">No Bookings Yet</h2>
                <p class="text-gray-500 mt-2 mb-6">You haven't booked any services. When you do, they'll appear here.</p>
                <a href="../dashboard.php?action=browse_services" target="_parent" class="bg-blue-600 text-white font-semibold px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                    Browse Services
                </a>
            </div>
        <?php else: ?>
            <!-- Tabs -->
            <div class="mb-6 border-b border-gray-200">
                <nav class="-mb-px flex space-x-6" id="booking-tabs">
                    <button data-tab="upcoming" class="tab-button active whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm text-gray-500 hover:border-gray-300">Upcoming</button>
                    <button data-tab="completed" class="tab-button whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm text-gray-500 hover:border-gray-300">Completed</button>
                    <button data-tab="cancelled" class="tab-button whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm text-gray-500 hover:border-gray-300">Cancelled</button>
                </nav>
            </div>

            <!-- Booking Lists -->
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
                    <div class="w-full md:w-auto text-center">
                        <span class="px-3 py-1 text-sm font-medium rounded-full <?php echo getStatusBadgeClass($booking['status']); ?>">
                            <?php echo htmlspecialchars($booking['status']); ?>
                        </span>
                    </div>
                    <div class="flex items-center justify-end space-x-2 w-full md:w-auto">
                        <?php if ($booking['status'] == 'Upcoming'): ?>
                            <button onclick="openCancelModal(<?php echo $booking['id']; ?>)" class="px-4 py-2 text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-lg">Cancel</button>
                            <button class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">Details</button>
                        <?php elseif ($booking['status'] == 'Completed'): ?>
                            <button class="px-4 py-2 text-sm font-medium text-yellow-600 bg-yellow-50 hover:bg-yellow-100 rounded-lg">Leave a Review</button>
                            <button class="px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg">Book Again</button>
                        <?php else: // Cancelled ?>
                             <button class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">View Details</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Cancel Booking Modal -->
    <div id="cancel-modal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Backdrop -->
            <div id="modal-backdrop" class="fixed inset-0 bg-gray-500 bg-opacity-75 modal-backdrop" style="opacity: 0;"></div>
            <!-- Modal Panel -->
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab-button');
            const bookingCards = document.querySelectorAll('.booking-card');

            function filterBookings(tab) {
                const status = tab.dataset.tab;

                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

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
            
            // Initial filter on load
            filterBookings(document.querySelector('.tab-button.active'));
        });

        // Modal Logic
        const modal = document.getElementById('cancel-modal');
        const modalBackdrop = document.getElementById('modal-backdrop');
        const modalPanel = document.getElementById('modal-panel');
        const bookingIdInput = document.getElementById('booking_id_to_cancel');

        function openCancelModal(bookingId) {
            bookingIdInput.value = bookingId;
            modal.classList.remove('hidden');
            setTimeout(() => {
                modalBackdrop.style.opacity = 1;
                modalPanel.style.opacity = 1;
                modalPanel.style.transform = 'translateY(0)';
            }, 10);
        }

        function closeCancelModal() {
            modalBackdrop.style.opacity = 0;
            modalPanel.style.opacity = 0;
            modalPanel.style.transform = 'translateY(20px)';
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }
    </script>

</body>
</html>