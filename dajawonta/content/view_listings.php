<?php
// Start session and connect to DB ONCE at the very top.
session_start();
require '../db.php';

// Check if a user is logged in. This check protects all subsequent actions.
if (!isset($_SESSION['user_id'])) {
    // If it's an AJAX request, send a 401 Unauthorized error.
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required.']);
        exit();
    }
    // For regular requests, redirect to login.
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// ACTION 1: Handle notification update from GET parameter. This runs first.
if (isset($_GET['provider_id']) && !empty($_GET['provider_id'])) {
    $provider_id_from_url = (int)$_GET['provider_id'];

    // SECURITY FIX: Update based on the LOGGED-IN user, not the provider_id.
    // This assumes the notification link is for the user viewing their own provider page.
    $update_notification_sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND link LIKE ? AND is_read = 0";
    
    // We still use the provider_id to find the specific notification link
    $link_pattern = '%' . 'provider_id=' . $provider_id_from_url . '%';
    
    $stmt_notify = $conn->prepare($update_notification_sql);
    if ($stmt_notify) {
        // Bind the LOGGED-IN user's ID
        $stmt_notify->bind_param("is", $user_id, $link_pattern);
        $stmt_notify->execute();
        $stmt_notify->close();
    }

    // Redirect to the same page without the query parameter to clean the URL.
    // strtok() is used to get the base URL before the '?'.
    header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

// ACTION 2: Handle POST request for editing a service.
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_service') {
    $service_id = (int)$_POST['service_id'];
    $company_name = trim($_POST['company_name']);
    $company_address = trim($_POST['company_address']);
    $service_description = trim($_POST['service_description']);
    $available_date_from = trim($_POST['available_date_from']);
    $available_date_to = trim($_POST['available_date_to']);
    $available_time_from = trim($_POST['available_time_from']);
    $available_time_to = trim($_POST['available_time_to']);
    $price = (float)$_POST['price'];

    $update_sql = "UPDATE service_providers SET 
                        company_name = ?, company_address = ?, service_description = ?, 
                        available_date_from = ?, available_date_to = ?, available_time_from = ?, 
                        available_time_to = ?, price = ? 
                    WHERE id = ? AND user_id = ?";
    
    $stmt_update = $conn->prepare($update_sql);
    $stmt_update->bind_param("ssssssssii", 
        $company_name, $company_address, $service_description,
        $available_date_from, $available_date_to, $available_time_from,
        $available_time_to, $price, $service_id, $user_id
    );

    if ($stmt_update->execute()) {
        $_SESSION['status_message'] = "Service updated successfully!";
        $_SESSION['status_type'] = "success";
    } else {
        $_SESSION['status_message'] = "Error updating service. Please try again.";
        $_SESSION['status_type'] = "error";
    }
    $stmt_update->close();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// ACTION 3: Handle AJAX request for toggling availability.
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'toggle_availability') {
    header('Content-Type: application/json');
    $service_id = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;
    $is_available = isset($_POST['is_available']) ? (int)$_POST['is_available'] : 0;

    if ($service_id > 0) {
        $toggle_sql = "UPDATE service_providers SET is_available = ? WHERE id = ? AND user_id = ?";
        $stmt_toggle = $conn->prepare($toggle_sql);
        $stmt_toggle->bind_param("iii", $is_available, $service_id, $user_id);
        
        if ($stmt_toggle->execute() && $stmt_toggle->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Availability updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update availability.']);
        }
        $stmt_toggle->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid service ID.']);
    }
    $conn->close();
    exit();
}

// If no actions were handled, proceed to fetch data for displaying the page.
// --- MODIFIED SQL QUERY ---
$sql = "SELECT 
            sp.id, sp.service_name, sp.company_name, sp.company_address, sp.service_description, sp.is_approved,
            sp.available_date_from, sp.available_date_to, sp.available_time_from, sp.available_time_to, sp.price,
            sp.is_available,
            COALESCE(AVG(pr.rating), 0) AS average_rating,
            COUNT(pr.id) AS rating_count
        FROM 
            service_providers AS sp
        LEFT JOIN 
            provider_ratings AS pr ON sp.id = pr.provider_id
        WHERE 
            sp.user_id = ?
        GROUP BY
            sp.id, sp.service_name, sp.company_name, sp.company_address, sp.service_description, sp.is_approved,
            sp.available_date_from, sp.available_date_to, sp.available_time_from, sp.available_time_to, sp.price,
            sp.is_available";

$stmt = $conn->prepare($sql);
if ($stmt === false) { die("Error preparing statement: " . $conn->error); }
$stmt->bind_param("i", $user_id);
if (!$stmt->execute()) { die("Error executing query: " . $stmt->error); }
$result = $stmt->get_result();
$services = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

// --- NEW HELPER FUNCTION ---
/**
 * Renders star icons based on a rating.
 * @param float $rating The average rating.
 * @return string The HTML for the stars.
 */
function render_stars($rating) {
    $stars_html = '';
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5;
    $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);

    for ($i = 0; $i < $full_stars; $i++) {
        $stars_html .= '<i class="fas fa-star text-yellow-400"></i>';
    }
    if ($half_star) {
        $stars_html .= '<i class="fas fa-star-half-alt text-yellow-400"></i>';
    }
    for ($i = 0; $i < $empty_stars; $i++) {
        $stars_html .= '<i class="far fa-star text-gray-300"></i>'; // Use 'far' for empty
    }
    return $stars_html;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Service Listings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --secondary: #7c3aed;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            background-image: radial-gradient(circle at top right, #c7d2fe, transparent),
                              radial-gradient(circle at bottom left, #ddd6fe, transparent);
            min-height: 100vh;
            padding: 2rem;
        }
        .card {
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.1);
        }
        .header-gradient {
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .service-details p { display: flex; align-items: flex-start; margin-bottom: 0.5rem; color: #4b5563; }
        .service-details i { width: 20px; text-align: center; margin-right: 0.75rem; color: var(--primary); padding-top: 3px; }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); display: flex; justify-content: center; align-items: center; z-index: 50; padding: 1rem; }
        /* Add modal content styling */
        .modal-content { background-color: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto; }
        .modal-header { padding: 1.5rem; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 1.5rem; }
        .modal-footer { padding: 1rem 1.5rem; border-top: 1px solid #e5e7eb; background-color: #f9fafb; text-align: right; }
        .form-label { display: block; margin-bottom: 0.5rem; font-medium; color: #374151; }
        .form-input { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); }
        .form-input:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.3); }
        
        .toast-container { position: fixed; bottom: 2rem; right: 2rem; z-index: 60; display: flex; flex-direction: column-reverse; gap: 1rem; }
        .toast { opacity: 0; transform: translateY(20px); transition: opacity 0.4s ease, transform 0.4s ease; }
        .toast.show { opacity: 1; transform: translateY(0); }
        .toggle-checkbox:checked { right: 0; border-color: var(--primary); }
        .toggle-checkbox:checked + .toggle-label { background-color: var(--primary); }
    </style>
</head>
<body>
    <div class="container mx-auto p-4 md:p-8">
        <div class="text-center mb-10">
             <h1 class="text-3xl md:text-4xl font-bold header-gradient mb-2">My Registered Services</h1>
            <p class="text-gray-600">Here are all the services you have listed with us.</p>
        </div>

        <?php if (!empty($services)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($services as $service): ?>
                    <div class="card p-6">
                        <div class="flex-grow">
                             <div class="flex items-center mb-2"> <div class="bg-indigo-100 text-primary p-3 rounded-full mr-4"><i class="fas fa-concierge-bell fa-lg"></i></div>
                                <h2 class="text-2xl font-semibold text-gray-800"><?php echo htmlspecialchars($service['service_name']); ?></h2>
                            </div>
                           
                            <div class="mb-4 pl-16"> <?php if ($service['rating_count'] > 0): ?>
                                    <div class="flex items-center" title="Rated <?php echo number_format($service['average_rating'], 1); ?> out of 5">
                                        <div class="flex items-center space-x-0.5">
                                            <?php echo render_stars($service['average_rating']); ?>
                                        </div>
                                        <span class="ml-2 text-sm font-medium text-gray-700"><?php echo number_format($service['average_rating'], 1); ?></span>
                                        <span class="ml-1 text-sm text-gray-500">(<?php echo $service['rating_count']; ?> rating<?php echo $service['rating_count'] > 1 ? 's' : ''; ?>)</span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-sm text-gray-500">No ratings yet</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($service['is_approved'] == 0): ?>
                                <div class="mb-4 p-2 text-sm text-yellow-800 rounded-lg bg-yellow-100 flex items-center" role="alert"><i class="fas fa-clock fa-sm mr-2"></i><span class="font-medium">Status:</span>&nbsp;Pending Approval</div>
                            <?php else: ?>
                                <div class="mb-4 p-2 text-sm text-green-800 rounded-lg bg-green-100 flex items-center" role="alert"><i class="fas fa-check-circle fa-sm mr-2"></i><span class="font-medium">Status:</span>&nbsp;Active</div>
                            <?php endif; ?>

                            <div class="flex items-center justify-between mb-4 bg-gray-50 p-3 rounded-lg">
                                <span id="availability-label-<?php echo $service['id']; ?>" class="font-medium text-sm <?php echo $service['is_available'] ? 'text-green-700' : 'text-gray-500'; ?>">
                                    <?php echo $service['is_available'] ? 'Available for Booking' : 'Not Available'; ?>
                                </span>
                                <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                                    <input type="checkbox" name="toggle" id="toggle-<?php echo $service['id']; ?>" class="availability-toggle toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer" 
                                           data-id="<?php echo $service['id']; ?>" <?php echo $service['is_available'] ? 'checked' : ''; ?>/>
                                    <label for="toggle-<?php echo $service['id']; ?>" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer"></label>
                                </div>
                            </div>

                            <h3 class="text-lg font-medium text-gray-700 mb-3"><?php echo htmlspecialchars($service['company_name']); ?></h3>
                            <div class="service-details text-sm space-y-2">
                                <p><i class="fas fa-map-marker-alt"></i><span><?php echo htmlspecialchars($service['company_address']); ?></span></p>
                                <p><i class="fas fa-info-circle"></i><span><?php echo nl2br(htmlspecialchars($service['service_description'])); ?></span></p>
                                <hr class="my-3">
                                <p><i class="fas fa-calendar-alt"></i><span><strong>Dates:</strong> <?php echo date("M j, Y", strtotime($service['available_date_from'])) . " to " . date("M j, Y", strtotime($service['available_date_to'])); ?></span></p>
                                <p><i class="fas fa-clock"></i><span><strong>Times:</strong> <?php echo date("g:i A", strtotime($service['available_time_from'])) . " to " . date("g:i A", strtotime($service['available_time_to'])); ?></span></p>
                                <p><i class="fas fa-tag"></i><span><strong>Price:</strong> ₱<?php echo number_format($service['price'], 2); ?></span></p>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex justify-end items-center gap-4">
                            <button class="show-edit-modal inline-block px-4 py-2 text-sm font-semibold text-indigo-600 rounded-md hover:bg-indigo-100 transition-colors duration-200"
                                    data-id="<?php echo htmlspecialchars($service['id']); ?>"
                                    data-company-name="<?php echo htmlspecialchars($service['company_name']); ?>"
                                    data-company-address="<?php echo htmlspecialchars($service['company_address']); ?>"
                                    data-service-description="<?php echo htmlspecialchars($service['service_description']); ?>"
                                    data-date-from="<?php echo htmlspecialchars($service['available_date_from']); ?>"
                                    data-date-to="<?php echo htmlspecialchars($service['available_date_to']); ?>"
                                    data-time-from="<?php echo htmlspecialchars($service['available_time_from']); ?>"
                                    data-time-to="<?php echo htmlspecialchars($service['available_time_to']); ?>"
                                    data-price="<?php echo htmlspecialchars($service['price']); ?>">
                                <i class="fas fa-edit mr-1"></i> Edit
                            </button>
                             <a href="#" class="show-cancel-modal inline-block px-4 py-2 text-sm font-semibold text-red-600 rounded-md hover:bg-red-100 transition-colors duration-200"
                               data-service-id="<?php echo htmlspecialchars($service['id']); ?>">
                                <i class="fas fa-trash-alt mr-1"></i> Cancel
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
             <div class="text-center p-12 bg-white rounded-xl shadow-lg">
                <i class="fas fa-exclamation-circle text-gray-400 text-5xl mb-4"></i>
                <p class="text-gray-500 font-medium text-lg">You have not registered any services yet.</p>
                <a href="addNewservice.php" class="inline-block mt-6 px-6 py-2 bg-primary text-white rounded-lg font-semibold hover:bg-indigo-700 transition">
                    Add Your First Service
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div id="editModal" class="hidden modal-overlay">
        <div class="modal-content">
            <form id="edit-service-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="action" value="update_service">
                <input type="hidden" name="service_id" id="edit-service-id">

                <div class="modal-header">
                    <h3 class="text-lg font-medium text-gray-900">Edit Service Details</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-600" onclick="document.getElementById('editModal').classList.add('hidden')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="modal-body space-y-4">
                    <div>
                        <label for="edit-company-name" class="form-label">Company Name</label>
                        <input type="text" id="edit-company-name" name="company_name" class="form-input" required>
                    </div>
                    <div>
                        <label for="edit-company-address" class="form-label">Company Address</label>
                        <input type="text" id="edit-company-address" name="company_address" class="form-input" required>
                    </div>
                    <div>
                        <label for="edit-service-description" class="form-label">Service Description</label>
                        <textarea id="edit-service-description" name="service_description" rows="4" class="form-input" required></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="edit-date-from" class="form-label">Available Date From</label>
                            <input type="date" id="edit-date-from" name="available_date_from" class="form-input" required>
                        </div>
                        <div>
                            <label for="edit-date-to" class="form-label">Available Date To</label>
                            <input type="date" id="edit-date-to" name="available_date_to" class="form-input" required>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="edit-time-from" class="form-label">Available Time From</label>
                            <input type="time" id="edit-time-from" name="available_time_from" class="form-input" required>
                        </div>
                        <div>
                            <label for="edit-time-to" class="form-label">Available Time To</label>
                            <input type="time" id="edit-time-to" name="available_time_to" class="form-input" required>
                        </div>
                    </div>
                    <div>
                        <label for="edit-price" class="form-label">Price (₱)</label>
                        <input type="number" id="edit-price" name="price" class="form-input" step="0.01" min="0" required>
                    </div>
                </div>

                <div class="modal-footer space-x-3">
                    <button type="button" class="px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-md font-medium hover:bg-gray-50"
                            onclick="document.getElementById('editModal').classList.add('hidden')">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-md font-medium hover:bg-indigo-700">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div id="cancelModal" class="hidden modal-overlay"></div>
    <div id="toast-container" class="toast-container pointer-events-none"></div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Logic to restore scroll position after page reload
        const scrollPosition = sessionStorage.getItem('scrollPosition');
        if (scrollPosition) {
            window.scrollTo(0, parseInt(scrollPosition, 10));
            sessionStorage.removeItem('scrollPosition');
        }

        const cancelModal = document.getElementById('cancelModal');
        const showCancelModalLinks = document.querySelectorAll('.show-cancel-modal');
        const toastContainer = document.getElementById('toast-container');
        const editModal = document.getElementById('editModal');
        const showEditModalLinks = document.querySelectorAll('.show-edit-modal');
        const editForm = document.getElementById('edit-service-form'); // This ID now exists in the modal HTML

        // Logic for Edit Modal
        showEditModalLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                // Assumes your modal has inputs with these IDs
                document.getElementById('edit-service-id').value = this.dataset.id;
                document.getElementById('edit-company-name').value = this.dataset.companyName;
                document.getElementById('edit-company-address').value = this.dataset.companyAddress;
                document.getElementById('edit-service-description').value = this.dataset.serviceDescription;
                document.getElementById('edit-date-from').value = this.dataset.dateFrom;
                document.getElementById('edit-date-to').value = this.dataset.dateTo;
                document.getElementById('edit-time-from').value = this.dataset.timeFrom;
                document.getElementById('edit-time-to').value = this.dataset.timeTo;
                document.getElementById('edit-price').value = this.dataset.price;
                editModal.classList.remove('hidden');
            });
        });

        // Logic to save scroll position right before the edit form submits
        if (editForm) {
            editForm.addEventListener('submit', function() {
                sessionStorage.setItem('scrollPosition', window.scrollY);
            });
        }

        // Logic for Cancel Modal
        showCancelModalLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const serviceId = this.getAttribute('data-service-id');
                // You will need to add the HTML for your cancel modal, but this logic will work with it
                const confirmButton = cancelModal.querySelector('#confirmCancel'); // Assumes this ID exists in your modal
                if(confirmButton) {
                    confirmButton.href = `cancel_service.php?id=${serviceId}`;
                }
                cancelModal.classList.remove('hidden');
            });
        });
        
        // Logic to handle the availability toggle
        document.querySelectorAll('.availability-toggle').forEach(toggle => {
            toggle.addEventListener('change', function() {
                const serviceId = this.dataset.id;
                const isAvailable = this.checked ? 1 : 0;
                const label = document.getElementById('availability-label-' + serviceId);
                const formData = new FormData();
                formData.append('action', 'toggle_availability');
                formData.append('service_id', serviceId);
                formData.append('is_available', isAvailable);

                fetch('<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        if (isAvailable) {
                            label.textContent = 'Available for Booking';
                            label.classList.remove('text-gray-500');
                            label.classList.add('text-green-700');
                        } else {
                            label.textContent = 'Not Available';
                            label.classList.remove('text-green-700');
                            label.classList.add('text-gray-500');
                        }
                    } else {
                        // If it fails, revert the toggle and show an error
                        this.checked = !this.checked;
                        showToast(data.message || 'An error occurred.', 'error');
                    }
                })
                .catch(error => {
                    this.checked = !this.checked;
                    showToast('A network error occurred. Please try again.', 'error');
                });
            });
        });

        // Logic for Toast Notifications from PHP session
        <?php if (isset($_SESSION['status_message'])): ?>
            showToast('<?php echo addslashes($_SESSION['status_message']); ?>', '<?php echo addslashes($_SESSION['status_type']); ?>');
            <?php unset($_SESSION['status_message'], $_SESSION['status_type']); ?>
        <?php endif; ?>

        function showToast(message, type) {
            const toast = document.createElement('div');
            const typeClasses = {
                success: { bg: 'bg-green-100', text: 'text-green-800', icon: 'fas fa-check-circle', iconColor: 'text-green-600' },
                error:   { bg: 'bg-red-100',   text: 'text-red-800',   icon: 'fas fa-times-circle',  iconColor: 'text-red-600'   }
            };
            const config = typeClasses[type] || typeClasses.error;
            toast.className = `toast p-4 rounded-lg shadow-lg flex items-center gap-4 pointer-events-auto ${config.bg} ${config.text}`;
            toast.innerHTML = `<div class="${config.iconColor} text-lg"><i class="${config.icon}"></i></div><div>${message}</div>`;
            toastContainer.appendChild(toast);
            
            // Animate in
            setTimeout(() => toast.classList.add('show'), 100);

            // Animate out and remove after 5 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 400);
            }, 5000);
        }

        // Close modals when clicking outside
        [editModal, cancelModal].forEach(modal => {
            if (modal) {
                modal.addEventListener('click', function(e) {
                    // We check e.target to make sure we're clicking the overlay
                    // and not the modal content itself (which would bubble up)
                    if (e.target === this) {
                        this.classList.add('hidden');
                    }
                });
            }
        });
    });
    
</script>
</body>
</html>