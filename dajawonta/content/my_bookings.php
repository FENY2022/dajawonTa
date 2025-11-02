<?php
ob_start();
session_start();

// Include database connection
require_once '../db.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get logged-in user ID
$customer_id = $_SESSION['user_id'];

// Fetch the latest booking (to display profile info)
$profile_query = $conn->prepare("SELECT customer_name, customer_email, customer_phone FROM bookings WHERE customer_id = ? ORDER BY created_at DESC LIMIT 1");
$profile_query->bind_param("i", $customer_id);
$profile_query->execute();
$profile_result = $profile_query->get_result();
$profile = $profile_result->fetch_assoc();

// Fetch all bookings for the user, joining with services table
$bookings_query = $conn->prepare("
    SELECT 
        b.*, 
        s.service_name 
    FROM 
        bookings AS b
    LEFT JOIN 
        services AS s ON b.service_id = s.service_id
    WHERE 
        b.customer_id = ? 
    ORDER BY 
        b.created_at DESC
");
$bookings_query->bind_param("i", $customer_id);
$bookings_query->execute();
$bookings = $bookings_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Bookings | Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4bb543;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --gray-color: #6c757d;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --hover-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            color: var(--dark-color);
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }
        
        .profile-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            position: relative;
            overflow: hidden;
        }
        
        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 40px;
        }
        
        .bookings-card {
            background: white;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            color: var(--gray-color);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table td {
            vertical-align: middle;
            padding: 1rem 0.75rem;
        }
        
        .badge {
            font-weight: 500;
            padding: 0.4em 0.8em;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.3);
        }
        
        .btn-outline-primary {
            border-radius: 6px;
            font-weight: 500;
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-bottom: none;
            border-radius: 12px 12px 0 0;
        }
        
        .modal-title {
            font-weight: 600;
        }
        
        .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: var(--gray-color);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .footer {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.5rem 0;
            margin-top: 3rem;
        }
        
        .action-btn {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .empty-state {
            padding: 3rem 1rem;
            text-align: center;
            color: var(--gray-color);
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }
        
        .status-badge {
            font-size: 0.8rem;
            padding: 0.35em 0.8em;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 fw-bold text-dark mb-1">My Bookings</h1>
                        <p class="text-muted">Manage and view your booking history</p>
                    </div>
                    <a href="new-booking.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i> New Booking
                    </a>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <?php
            // Count different booking statuses
            $pending_count = 0;
            $approved_count = 0;
            $paid_count = 0;
            
            if ($bookings->num_rows > 0) {
                // Reset pointer to beginning
                $bookings->data_seek(0);
                
                while ($row = $bookings->fetch_assoc()) {
                    if ($row['booking_status'] == 'pending') $pending_count++;
                    if ($row['booking_status'] == 'approved') $approved_count++;
                    if ($row['payment_status'] == 'paid') $paid_count++;
                }
                
                // Reset pointer again for later use
                $bookings->data_seek(0);
            }
            ?>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="stat-value"><?= $bookings->num_rows ?></div>
                            <div class="stat-label">Total Bookings</div>
                        </div>
                        <div class="text-primary">
                            <i class="bi bi-journal-text" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="stat-value"><?= $approved_count ?></div>
                            <div class="stat-label">Approved</div>
                        </div>
                        <div class="text-success">
                            <i class="bi bi-check-circle" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="stat-value"><?= $pending_count ?></div>
                            <div class="stat-label">Pending</div>
                        </div>
                        <div class="text-warning">
                            <i class="bi bi-clock" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card profile-card">
                    <div class="card-body p-4">
                        <div class="profile-avatar">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <h5 class="card-title text-center mb-3">My Profile</h5>
                        <?php if ($profile): ?>
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-person me-2 text-primary"></i>
                                    <span class="fw-medium">Name:</span>
                                </div>
                                <p class="ms-4 mb-0"><?= htmlspecialchars($profile['customer_name'] ?? 'N/A') ?></p>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-envelope me-2 text-primary"></i>
                                    <span class="fw-medium">Email:</span>
                                </div>
                                <p class="ms-4 mb-0"><?= htmlspecialchars($profile['customer_email'] ?? 'N/A') ?></p>
                            </div>
                            <div class="mb-4">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-telephone me-2 text-primary"></i>
                                    <span class="fw-medium">Phone:</span>
                                </div>
                                <p class="ms-4 mb-0"><?= htmlspecialchars($profile['customer_phone'] ?? 'N/A') ?></p>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center">No profile found.</p>
                        <?php endif; ?>
                        <hr>
                        <div class="d-grid gap-2">
                            <a href="dashboard.php" class="btn btn-outline-primary">
                                <i class="bi bi-speedometer2 me-2"></i> Dashboard
                            </a>
                            <a href="logout.php" class="btn btn-outline-danger">
                                <i class="bi bi-box-arrow-right me-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card bookings-card">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title mb-0">Booking History</h5>
                            <div class="text-muted small">
                                <i class="bi bi-info-circle me-1"></i>
                                <?= $bookings->num_rows ?> bookings found
                            </div>
                        </div>
                        
                        <?php if ($bookings->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Service</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Status</th>
                                            <th>Payment</th>
                                            <th>Amount</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $count = 1;
                                        while ($row = $bookings->fetch_assoc()):
                                        ?>
                                            <tr>
                                                <td class="fw-medium"><?= $count++ ?></td>
                                                <td class="fw-medium"><?= htmlspecialchars($row['service_name'] ?? 'N/A') ?></td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-medium"><?= date('M d, Y', strtotime($row['booking_date_from'])) ?></span>
                                                        <?php if ($row['booking_date_to'] && $row['booking_date_to'] !== $row['booking_date_from']): ?>
                                                            <small class="text-muted">to <?= date('M d, Y', strtotime($row['booking_date_to'])) ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span><?= date('h:i A', strtotime($row['booking_time_from'])) ?></span>
                                                        <?php if ($row['booking_time_to'] && $row['booking_time_to'] !== $row['booking_time_from']): ?>
                                                            <small class="text-muted">to <?= date('h:i A', strtotime($row['booking_time_to'])) ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if ($row['booking_status'] == 'approved'): ?>
                                                        <span class="badge bg-success status-badge">Approved</span>
                                                    <?php elseif ($row['booking_status'] == 'pending'): ?>
                                                        <span class="badge bg-warning text-dark status-badge">Pending</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary status-badge"><?= htmlspecialchars($row['booking_status']) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($row['payment_status'] == 'paid'): ?>
                                                        <span class="badge bg-success status-badge">Paid</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger status-badge">Unpaid</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="fw-bold">₱<?= number_format($row['total_price'], 2) ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary action-btn" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#bookingDetailsModal"
                                                            data-bs-id="<?= $row['id'] ?>"
                                                            data-bs-service-name="<?= htmlspecialchars($row['service_name'] ?? 'N/A') ?>"
                                                            data-bs-name="<?= htmlspecialchars($row['customer_name']) ?>"
                                                            data-bs-email="<?= htmlspecialchars($row['customer_email']) ?>"
                                                            data-bs-phone="<?= htmlspecialchars($row['customer_phone']) ?>"
                                                            data-bs-date-from="<?= htmlspecialchars($row['booking_date_from']) ?>"
                                                            data-bs-date-to="<?= htmlspecialchars($row['booking_date_to']) ?>"
                                                            data-bs-time-from="<?= htmlspecialchars($row['booking_time_from']) ?>"
                                                            data-bs-time-to="<?= htmlspecialchars($row['booking_time_to']) ?>"
                                                            data-bs-price="<?= number_format($row['total_price'], 2) ?>"
                                                            data-bs-status="<?= htmlspecialchars($row['booking_status']) ?>"
                                                            data-bs-payment-status="<?= htmlspecialchars($row['payment_status']) ?>"
                                                            data-bs-special-request="<?= htmlspecialchars($row['special_request'] ?? 'None') ?>"
                                                            data-bs-payment-id="<?= htmlspecialchars($row['payment_id'] ?? 'N/A') ?>"
                                                            data-bs-payment-link="<?= htmlspecialchars($row['payment_link'] ?? '') ?>"
                                                            data-bs-created-at="<?= htmlspecialchars($row['created_at']) ?>">
                                                        <i class="bi bi-eye me-1"></i> Details
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-calendar-x"></i>
                                <h4 class="h5">No Bookings Yet</h4>
                                <p class="text-muted">You haven't made any bookings yet.</p>
                                <a href="new-booking.php" class="btn btn-primary mt-2">
                                    <i class="bi bi-plus-circle me-2"></i> Make Your First Booking
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?= date('Y') ?> Booking System. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">Need help? <a href="contact.php" class="text-primary">Contact Support</a></p>
                </div>
            </div>
        </div>
    </footer>

    <div class="modal fade" id="bookingDetailsModal" tabindex="-1" aria-labelledby="bookingDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bookingDetailsModalLabel">
                        <i class="bi bi-journal-text me-2"></i>
                        Booking Details (ID: <span id="modal-booking-id"></span>)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="bi bi-person me-2"></i> Customer Information
                            </h6>
                            <div class="mb-2">
                                <span class="fw-medium">Name:</span>
                                <span id="modal-customer-name" class="ms-2"></span>
                            </div>
                            <div class="mb-2">
                                <span class="fw-medium">Email:</span>
                                <span id="modal-customer-email" class="ms-2"></span>
                            </div>
                            <div class="mb-2">
                                <span class="fw-medium">Phone:</span>
                                <span id="modal-customer-phone" class="ms-2"></span>
                            </div>
                            <div class="mt-3">
                                <span class="fw-medium">Special Request:</span>
                                <p id="modal-special-request" class="mt-1 p-2 bg-light rounded"></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="bi bi-calendar-event me-2"></i> Booking Details
                            </h6>
                            <div class="mb-2">
                                <span class="fw-medium">Service:</span>
                                <span id="modal-service-name" class="ms-2 fw-bold"></span>
                            </div>
                            <div class="mb-2">
                                <span class="fw-medium">Booking Dates:</span>
                                <span id="modal-booking-dates" class="ms-2"></span>
                            </div>
                            <div class="mb-2">
                                <span class="fw-medium">Booking Times:</span>
                                <span id="modal-booking-times" class="ms-2"></span>
                            </div>
                            <div class="mb-2">
                                <span class="fw-medium">Total Price:</span>
                                <span class="ms-2 fw-bold">₱<span id="modal-total-price"></span></span>
                            </div>
                            <div class="mb-2">
                                <span class="fw-medium">Booking Created:</span>
                                <span id="modal-created-at" class="ms-2"></span>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="bi bi-info-circle me-2"></i> Status Information
                            </h6>
                            <div class="mb-2">
                                <span class="fw-medium">Booking Status:</span>
                                <span id="modal-booking-status" class="ms-2 badge bg-success"></span>
                            </div>
                            <div class="mb-2">
                                <span class="fw-medium">Payment Status:</span>
                                <span id="modal-payment-status" class="ms-2 badge bg-success"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="bi bi-credit-card me-2"></i> Payment Information
                            </h6>
                            <div class="mb-2">
                                <span class="fw-medium">Payment ID:</span>
                                <span id="modal-payment-id" class="ms-2"></span>
                            </div>
                            <div class="mb-2">
                                <span class="fw-medium">Payment Link:</span>
                                <span id="modal-payment-link-container" class="ms-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var bookingDetailsModal = document.getElementById('bookingDetailsModal');
        bookingDetailsModal.addEventListener('show.bs.modal', function (event) {
            // Button that triggered the modal
            var button = event.relatedTarget;

            // Extract info from data-bs-* attributes
            var id = button.getAttribute('data-bs-id');
            var serviceName = button.getAttribute('data-bs-service-name');
            var name = button.getAttribute('data-bs-name');
            var email = button.getAttribute('data-bs-email');
            var phone = button.getAttribute('data-bs-phone');
            var dateFrom = button.getAttribute('data-bs-date-from');
            var dateTo = button.getAttribute('data-bs-date-to');
            var timeFrom = button.getAttribute('data-bs-time-from');
            var timeTo = button.getAttribute('data-bs-time-to');
            var price = button.getAttribute('data-bs-price');
            var status = button.getAttribute('data-bs-status');
            var paymentStatus = button.getAttribute('data-bs-payment-status');
            var specialRequest = button.getAttribute('data-bs-special-request');
            var paymentId = button.getAttribute('data-bs-payment-id');
            var paymentLink = button.getAttribute('data-bs-payment-link');
            var createdAt = button.getAttribute('data-bs-created-at');

            // Update the modal's content
            var modal = this;
            modal.querySelector('#modal-booking-id').textContent = id;
            modal.querySelector('#modal-service-name').textContent = serviceName;
            modal.querySelector('#modal-customer-name').textContent = name;
            modal.querySelector('#modal-customer-email').textContent = email;
            modal.querySelector('#modal-customer-phone').textContent = phone;
            modal.querySelector('#modal-booking-dates').textContent = formatDate(dateFrom) + ' to ' + formatDate(dateTo);
            modal.querySelector('#modal-booking-times').textContent = formatTime(timeFrom) + ' to ' + formatTime(timeTo);
            modal.querySelector('#modal-total-price').textContent = price;
            modal.querySelector('#modal-created-at').textContent = formatDateTime(createdAt);
            modal.querySelector('#modal-special-request').textContent = specialRequest;
            modal.querySelector('#modal-payment-id').textContent = paymentId;

            // Handle status badges
            var bookingStatusBadge = modal.querySelector('#modal-booking-status');
            bookingStatusBadge.textContent = status;
            bookingStatusBadge.className = 'ms-2 badge ' + getStatusClass(status);
            
            var paymentStatusBadge = modal.querySelector('#modal-payment-status');
            paymentStatusBadge.textContent = paymentStatus;
            paymentStatusBadge.className = 'ms-2 badge ' + getPaymentStatusClass(paymentStatus);

            // Handle the payment link
            var paymentLinkContainer = modal.querySelector('#modal-payment-link-container');
            if (paymentLink && paymentLink !== '') {
                paymentLinkContainer.innerHTML = '<a href="' + paymentLink + '" target="_blank" class="text-primary">View Payment Link <i class="bi bi-box-arrow-up-right ms-1"></i></a>';
            } else {
                paymentLinkContainer.textContent = 'N/A';
            }
        });

        // Helper functions for formatting
        function formatDate(dateString) {
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return new Date(dateString).toLocaleDateString('en-US', options);
        }

        function formatTime(timeString) {
            return new Date('1970-01-01T' + timeString).toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit',
                hour12: true 
            });
        }

        function formatDateTime(dateTimeString) {
            const options = { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true 
            };
            return new Date(dateTimeString).toLocaleDateString('en-US', options);
        }

        function getStatusClass(status) {
            switch(status) {
                case 'approved': return 'bg-success';
                case 'pending': return 'bg-warning text-dark';
                default: return 'bg-secondary';
            }
        }

        function getPaymentStatusClass(status) {
            switch(status) {
                case 'paid': return 'bg-success';
                default: return 'bg-danger';
            }
        }
    });
    </script>

</body>
</html>

<?php ob_end_flush(); ?>