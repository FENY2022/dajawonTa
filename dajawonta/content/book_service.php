<?php
session_start();
require '../db.php';

// Check if provider_id is set
if (!isset($_GET['provider_id']) || empty($_GET['provider_id'])) {
    header("Location: index.php");
    exit;
}

$provider_id = $_GET['provider_id'];

// Fetch the service provider's details
$sql = "SELECT * FROM service_providers WHERE id = ? AND is_approved = 1 AND is_available = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Service provider not found or is currently unavailable.");
}

$provider = $result->fetch_assoc();
$stmt->close();

// --- AUTOFILL LOGIC: Fetch Current User Details ---
$current_user = [
    'first_name' => '', 'last_name' => '', 'email' => '', 'phone_number' => ''
];

if (isset($_SESSION['user_id'])) {
    $u_sql = "SELECT first_name, last_name, email, phone_number FROM users WHERE id = ?";
    $u_stmt = $conn->prepare($u_sql);
    $u_stmt->bind_param("i", $_SESSION['user_id']);
    $u_stmt->execute();
    $u_result = $u_stmt->get_result();
    if ($u_result->num_rows > 0) {
        $current_user = $u_result->fetch_assoc();
    }
    $u_stmt->close();
}
// --------------------------------------------------

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Service - <?php echo htmlspecialchars($provider['service_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        /* ... (Your existing styles) ... */
        :root { --primary: #4361ee; --primary-dark: #3a56d4; --secondary: #7209b7; --light: #f8f9fa; --dark: #212529; --success: #28a745; --danger: #dc3545; --gray: #6c757d; --light-gray: #e9ecef; --border-radius: 12px; --shadow: 0 4px 12px rgba(0, 0, 0, 0.08); --transition: all 0.3s ease; } * { margin: 0; padding: 0; box-sizing: border-box; } body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: var(--dark); background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%); min-height: 100vh; padding: 20px; } .container { max-width: 900px; margin: 20px auto; background: white; border-radius: var(--border-radius); box-shadow: var(--shadow); overflow: hidden; } header { background: linear-gradient(to right, var(--primary), var(--secondary)); color: white; padding: 2rem; text-align: center; } header h1 { font-size: 2.2rem; margin-bottom: 0.5rem; } .booking-section { padding: 2.5rem; } .booking-section .back-link { display: inline-block; margin-bottom: 1.5rem; color: var(--primary); text-decoration: none; font-weight: 600; } .booking-section .back-link:hover { text-decoration: underline; } .booking-details, .booking-form { background: var(--light); border: 1px solid var(--light-gray); border-radius: var(--border-radius); padding: 2rem; } .booking-details { margin-bottom: 2rem; } .booking-details h2 { color: var(--primary-dark); margin-bottom: 1rem; } .booking-details p { margin-bottom: 10px; font-size: 1.05rem; } .booking-details p strong { color: var(--dark); min-width: 120px; display: inline-block; } .booking-details .price { font-size: 1.5rem; font-weight: 700; color: var(--success); } .form-group { margin-bottom: 1.5rem; } .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--dark); } .form-control { width: 100%; padding: 12px 16px; border: 2px solid var(--light-gray); border-radius: var(--border-radius); font-size: 1rem; transition: var(--transition); } .form-control:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2); } textarea.form-control { line-height: 1.6; resize: vertical; } .btn { padding: 12px 24px; border: none; border-radius: var(--border-radius); font-size: 1rem; font-weight: 600; cursor: pointer; transition: var(--transition); display: inline-flex; align-items: center; gap: 8px; } .btn-primary { background: var(--primary); color: white; width: 100%; justify-content: center; } .btn-primary:hover { background: var(--primary-dark); transform: translateY(-2px); } footer { text-align: center; padding: 1.5rem; background: var(--light); color: var(--gray); border-top: 1px solid var(--light-gray); } .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); display: none; align-items: center; justify-content: center; z-index: 1000; opacity: 0; transition: opacity 0.3s ease; } .modal-overlay.show { display: flex; opacity: 1; } .modal-content { background: white; padding: 2.5rem; border-radius: var(--border-radius); box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2); max-width: 500px; width: 90%; text-align: center; transform: translateY(-30px); transition: all 0.3s ease; } .modal-overlay.show .modal-content { transform: translateY(0); } .modal-content h3 { color: var(--primary-dark); margin-bottom: 1rem; font-size: 1.5rem; } .modal-content p { font-size: 1.05rem; margin-bottom: 2rem; color: var(--dark); line-height: 1.7; } .modal-buttons { display: flex; justify-content: space-between; gap: 1rem; } .modal-buttons .btn { width: 100%; flex: 1; } .btn-secondary { background: var(--light-gray); color: var(--dark); border: 1px solid #ccc; } .btn-secondary:hover { background: #d4d4d4; }
    </style>
</head>
<body>
<div class="container">
    <header><h1><i class="fas fa-calendar-check"></i> Book a Service</h1></header>

    <section class="booking-section">
        <a href="serviceProvider.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Directory</a>

        <div class="booking-details">
            <h2>Service Details</h2>
            <p><strong>Provider:</strong> <?php echo htmlspecialchars($provider['company_name']); ?></p>
            <p><strong>Service:</strong> <?php echo htmlspecialchars($provider['service_name']); ?></p>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($provider['service_description']); ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($provider['company_address']); ?></p>
            <p><strong>Availability:</strong> <?php echo date("M j, Y", strtotime($provider['available_date_from'])) . ' to ' . date("M j, Y", strtotime($provider['available_date_to'])); ?></p>
            <p><strong>Hours:</strong> <?php echo date("g:i A", strtotime($provider['available_time_from'])) . ' to ' . date("g:i A", strtotime($provider['available_time_to'])); ?></p>
            <p class="price"><strong>Price:</strong> ₱<?php echo number_format($provider['price'], 2); ?></p>
        </div>

        <div class="booking-form">
            <h2>Enter Your Booking Details</h2>
            
            <form action="process_booking.php" method="POST" id="bookingForm">
                <input type="hidden" name="provider_id" value="<?php echo htmlspecialchars($provider['id']); ?>">
                <input type="hidden" name="service_id" value="<?php echo htmlspecialchars($provider['service_id']); ?>">

                <div class="form-group">
                    <label for="customer_name"><i class="fas fa-user"></i> Your Name</label>
                    <input type="text" id="customer_name" name="customer_name" class="form-control" required
                           value="<?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?>">
                </div>
                <div class="form-group">
                    <label for="customer_email"><i class="fas fa-envelope"></i> Your Email</label>
                    <input type="email" id="customer_email" name="customer_email" class="form-control" required
                           value="<?php echo htmlspecialchars($current_user['email']); ?>">
                </div>
                <div class="form-group">
                    <label for="customer_phone"><i class="fas fa-phone"></i> Your Phone</label>
                    <input type="tel" id="customer_phone" name="customer_phone" class="form-control" required
                           value="<?php echo htmlspecialchars($current_user['phone_number']); ?>">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-calendar-day"></i> Requested Date Range</label>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <div style="flex: 1;">
                            <label for="booking_date_from" style="font-weight: normal;">From</label>
                            <input type="date" id="booking_date_from" name="booking_date_from" class="form-control"
                                   min="<?php echo htmlspecialchars($provider['available_date_from']); ?>" 
                                   max="<?php echo htmlspecialchars($provider['available_date_to']); ?>" required>
                        </div>
                        <div style="flex: 1;">
                            <label for="booking_date_to" style="font-weight: normal;">To</label>
                            <input type="date" id="booking_date_to" name="booking_date_to" class="form-control"
                                   min="<?php echo htmlspecialchars($provider['available_date_from']); ?>" 
                                   max="<?php echo htmlspecialchars($provider['available_date_to']); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-clock"></i> Requested Time Range</label>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <div style="flex: 1;">
                            <label for="booking_time_from" style="font-weight: normal;">From</label>
                            <input type="time" id="booking_time_from" name="booking_time_from" class="form-control"
                                   min="<?php echo htmlspecialchars($provider['available_time_from']); ?>"
                                   max="<?php echo htmlspecialchars($provider['available_time_to']); ?>" required>
                        </div>
                        <div style="flex: 1;">
                            <label for="booking_time_to" style="font-weight: normal;">To</label>
                            <input type="time" id="booking_time_to" name="booking_time_to" class="form-control"
                                   min="<?php echo htmlspecialchars($provider['available_time_from']); ?>"
                                   max="<?php echo htmlspecialchars($provider['available_time_to']); ?>" required>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="special_request"><i class="fas fa-comment-alt"></i> Special Request (Optional)</label>
                    <textarea id="special_request" name="special_request" class="form-control" rows="4" placeholder="e.g., specific instructions, etc."></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-check-circle"></i> Submit Booking Request</button>
            </form>
        </div>
    </section>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Service Provider Directory. All rights reserved.</p>
    </footer>
</div>

<div id="bookingConfirmationModal" class="modal-overlay">
    <div class="modal-content">
        <h3><i class="fas fa-exclamation-triangle" style="color: #f0ad4e;"></i> Please Confirm</h3>
        <p>Note: Please be reminded that once the payment has been made, your booking is considered final. Cancellations will no longer be accepted, and payments are non-refundable.</p>
        <div class="modal-buttons">
            <button type="button" id="cancelBookingBtn" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</button>
            <button type="button" id="confirmBookingBtn" class="btn btn-primary"><i class="fas fa-check-circle"></i> Submit Booking Request</button>
        </div>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const fromDate = document.getElementById("booking_date_from");
    const toDate = document.getElementById("booking_date_to");
    const fromTime = document.getElementById("booking_time_from");
    const toTime = document.getElementById("booking_time_to");

    fromDate.addEventListener("change", function() {
        toDate.min = fromDate.value;
        if (toDate.value < fromDate.value) { toDate.value = ""; }
    });
    toDate.addEventListener("change", function() {
        if (toDate.value < fromDate.value) { alert("The 'To' date cannot be earlier than the 'From' date."); toDate.value = ""; }
    });
    fromTime.addEventListener("change", function() {
        toTime.min = fromTime.value;
        if (toTime.value && toTime.value < fromTime.value) { toTime.value = ""; }
    });
    toTime.addEventListener("change", function() {
        if (fromTime.value && toTime.value < fromTime.value) { alert("The 'To' time cannot be earlier than the 'From' time."); toTime.value = ""; }
    });

    const bookingForm = document.getElementById("bookingForm");
    const modal = document.getElementById("bookingConfirmationModal");
    const cancelBtn = document.getElementById("cancelBookingBtn");
    const confirmBtn = document.getElementById("confirmBookingBtn");

    if (bookingForm && modal && cancelBtn && confirmBtn) {
        bookingForm.addEventListener("submit", function(event) { event.preventDefault(); modal.classList.add("show"); });
        cancelBtn.addEventListener("click", function() { modal.classList.remove("show"); });
        confirmBtn.addEventListener("click", function() { bookingForm.submit(); });
    }
});
</script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<?php
if (isset($_SESSION['message'])) {
    $message = json_encode($_SESSION['message']);
    $msg_type = $_SESSION['msg_type'] ?? 'danger';
    $background_color = ($msg_type == 'success') ? '#28a745' : '#dc3545';
    echo "<script>
        Toastify({ text: $message, duration: 5000, close: true, gravity: 'top', position: 'right', stopOnFocus: true, style: { background: '$background_color', borderRadius: '12px' } }).showToast();
    </script>";
    unset($_SESSION['message']); unset($_SESSION['msg_type']);
}
?>
</body>
</html>