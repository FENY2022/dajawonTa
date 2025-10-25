<?php
session_start();
require '../db.php'; // Database connection - Assuming this file establishes $conn

// ==========================
// Initialize Toast Variables and Helpers (IMPROVED)
// ==========================
$toastMessage = '';
$toastColor = 'success'; // Default color
$toastIcon = '';
$toastTitle = '';

// Define icons and titles for contextual toast messages
$toast_map = [
    'success' => ['icon' => 'bi-check-circle-fill', 'title' => 'Success!'],
    'primary' => ['icon' => 'bi-info-circle-fill', 'title' => 'Information'],
    'danger'  => ['icon' => 'bi-exclamation-octagon-fill', 'title' => 'Error!'],
    'warning' => ['icon' => 'bi-exclamation-triangle-fill', 'title' => 'Warning'],
    'info'    => ['icon' => 'bi-lightbulb-fill', 'title' => 'Heads Up!']
];

// ✅ Safely retrieve and clear session toast
if (isset($_SESSION['toast_message'])) {
    $toastMessage = htmlspecialchars($_SESSION['toast_message']);
    $toastColor = htmlspecialchars($_SESSION['toast_color'] ?? 'success');
    
    // Set icon and title based on color
    $map_data = $toast_map[$toastColor] ?? $toast_map['success']; // Fallback to success
    $toastIcon = $map_data['icon'];
    $toastTitle = $map_data['title'];
    
    // Clear them so they don't reappear on refresh
    unset($_SESSION['toast_message'], $_SESSION['toast_color']);
}


// ==========================
// Handle Form Submissions
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        // Shared POST variables
        $company_name = $_POST['company_name'] ?? '';
        $company_address = $_POST['company_address'] ?? '';
        $company_email = $_POST['company_email'] ?? '';
        $contact_number = $_POST['contact_number'] ?? '';
        $service_name = $_POST['service_name'] ?? '';
        $description = $_POST['description'] ?? '';
        $price = (float)($_POST['price'] ?? 0.00); 
        $available_date_from = $_POST['available_date_from'] ?? '';
        $available_date_to = $_POST['available_date_to'] ?? '';
        $available_time_from = $_POST['available_time_from'] ?? '00:00:00';
        $available_time_to = $_POST['available_time_to'] ?? '23:59:59'; 
        $is_approved = isset($_POST['is_approved']) ? 1 : 0;
        $is_available = isset($_POST['is_available']) ? 1 : 0;

        // ADD SERVICE
        if ($_POST['action'] === 'add') {
            $user_id = (int)($_POST['user_id'] ?? 0);

            $sql = "INSERT INTO service_providers 
                    (user_id, company_name, company_address, company_email, contact_number, 
                    service_name, description, price, available_date_from, available_date_to, 
                    available_time_from, available_time_to, is_approved, is_available, registration_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "issssssdssssii",
                $user_id, $company_name, $company_address, $company_email, $contact_number,
                $service_name, $description, $price, $available_date_from, $available_date_to,
                $available_time_from, $available_time_to, $is_approved, $is_available
            );
            $stmt->execute();
            $toastMessage = "Service added successfully! 🎉";
            $toastColor = "success";
        }

        // EDIT SERVICE
        elseif ($_POST['action'] === 'edit') {
            $id = (int)($_POST['id'] ?? 0);

            $sql = "UPDATE service_providers SET
                    company_name=?, company_address=?, company_email=?, contact_number=?, 
                    service_name=?, description=?, price=?, available_date_from=?, available_date_to=?, 
                    available_time_from=?, available_time_to=?, is_approved=?, is_available=? 
                    WHERE id=?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "sssssssdssssii",
                $company_name, $company_address, $company_email, $contact_number,
                $service_name, $description, $price, $available_date_from, $available_date_to,
                $available_time_from, $available_time_to, $is_approved, $is_available, $id
            );
            $stmt->execute();

            $toastMessage = "Service updated successfully! ✅";
            $toastColor = "primary";
        }


        // DELETE SERVICE
        elseif ($_POST['action'] === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare("DELETE FROM service_providers WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $toastMessage = "Service deleted successfully! 🗑️";
            $toastColor = 'danger';
        }
    } catch (Exception $e) {
        // Store the raw message; it will be escaped on retrieval
        $toastMessage = "Operation failed: " . $e->getMessage(); 
        $toastColor = 'danger';
    }

    // Set toast data for the session before redirecting
    $_SESSION['toast_message'] = $toastMessage; 
    $_SESSION['toast_color'] = $toastColor;
    
    // Redirect to clear POST
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

// ==========================
// Fetch Services
// ==========================
$search = $_GET['search'] ?? '';
$query = "SELECT * FROM service_providers 
          WHERE CONCAT(company_name, ' ', service_name, ' ', company_email) LIKE ? 
          ORDER BY registration_date DESC";
$stmt = $conn->prepare($query);
$like = "%$search%";
$stmt->bind_param("s", $like);
$stmt->execute();
$result = $stmt->get_result();
$services = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Services</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background: #f0f2f5; font-family: 'Inter', sans-serif; }
.card { border-radius: 16px; border: none; box-shadow: 0 6px 16px rgba(0,0,0,0.1); transition: transform 0.2s; }
.card-header-custom { 
    background-color: #007bff; /* Primary Blue */
    background-image: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white; 
    padding: 1.5rem 2rem; 
    border-top-left-radius: 16px;
    border-top-right-radius: 16px;
}
.btn-rounded-pill { border-radius: 50rem; }
.toast-container { z-index: 2000; } /* Keep z-index high for toast */
.price-tag { font-size: 1.5rem; color: #28a745; font-weight: 700; }
.status-badge-container { position: absolute; top: 1rem; right: 1rem; }
.icon-detail { color: #6c757d; margin-right: 0.5rem; }

/* IMPROVED TOAST STYLES */
.toast-header.bg-danger { background-color: #dc3545 !important; }
.toast-header.bg-success { background-color: #28a745 !important; }
.toast-header.bg-primary { background-color: #007bff !important; }
.toast-header.bg-warning { background-color: #ffc107 !important; color: #333 !important; }
.toast-header.bg-info { background-color: #17a2b8 !important; }
.toast-header { border-bottom: 1px solid rgba(0, 0, 0, 0.05); }
.toast-body { border-bottom-left-radius: 0.5rem; border-bottom-right-radius: 0.5rem; }
</style>
</head>
<body>

<div class="container py-5">
    <div class="card shadow-lg">
        <div class="card-header-custom d-flex flex-wrap justify-content-between align-items-center">
            <h3 class="mb-2 mb-md-0"><i class="bi bi-gear-fill me-3"></i>Service Management</h3>
            <button class="btn btn-light btn-rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                <i class="bi bi-plus-circle me-1"></i> Add New Service
            </button>
        </div>

        <div class="card-body p-4">
            <form method="GET" class="mb-5">
                <div class="input-group input-group-lg shadow-sm">
                    <input type="search" name="search" class="form-control" placeholder="Search by company, service name, or email..." value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Search</button>
                    <?php if ($search): ?>
                    <a href="<?= strtok($_SERVER["REQUEST_URI"], '?') ?>" class="btn btn-outline-secondary" title="Clear Search"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </form>

            <h5 class="mb-4 text-muted">Total Services: <strong><?= count($services); ?></strong></h5>

            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php if (count($services) > 0): ?>
                <?php foreach ($services as $row): ?>
                
                <div class="col">
                    <div class="card h-100 position-relative">
                        <div class="status-badge-container">
                            <?php if ($row['is_available']): ?>
                                <span class="badge bg-success me-1"><i class="bi bi-check-circle-fill"></i> Available</span>
                            <?php else: ?>
                                <span class="badge bg-secondary me-1"><i class="bi bi-clock-fill"></i> Not Available</span>
                            <?php endif; ?>
                            <?php if ($row['is_approved']): ?>
                                <span class="badge bg-primary"><i class="bi bi-patch-check-fill"></i> Approved</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Pending</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-body pt-5">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="card-title fw-bold text-primary"><?= htmlspecialchars($row['service_name']); ?></h5>
                                <span class="price-tag">₱<?= number_format($row['price'], 2); ?></span>
                            </div>

                            <h6 class="card-subtitle mb-2 text-muted"><i class="icon-detail bi bi-building"></i><?= htmlspecialchars($row['company_name']); ?></h6>
                            
                            <p class="card-text text-truncate" title="<?= htmlspecialchars($row['description']); ?>"><?= htmlspecialchars($row['description']); ?></p>

                            <ul class="list-unstyled small mt-3">
                                <li><i class="icon-detail bi bi-calendar-range"></i><strong>Dates:</strong> <?= date('M d', strtotime($row['available_date_from'])) ?> - <?= date('M d, Y', strtotime($row['available_date_to'])) ?></li>
                                <li><i class="icon-detail bi bi-clock"></i><strong>Time:</strong> <?= date('h:i A', strtotime($row['available_time_from'])) ?> - <?= date('h:i A', strtotime($row['available_time_to'])) ?></li>
                                <li><i class="icon-detail bi bi-geo-alt"></i><strong>Address:</strong> <?= htmlspecialchars($row['company_address']); ?></li>
                                <li><i class="icon-detail bi bi-envelope"></i><?= htmlspecialchars($row['company_email']); ?></li>
                                <li><i class="icon-detail bi bi-phone"></i><?= htmlspecialchars($row['contact_number']); ?></li>
                                <li><i class="icon-detail bi bi-info-circle"></i><strong>Registered:</strong> <?= date("M d, Y", strtotime($row['registration_date'])); ?></li>
                            </ul>
                        </div>
                        <div class="card-footer bg-white border-0 pt-0 pb-3 d-flex justify-content-end">
                            <button class="btn btn-sm btn-outline-warning me-2" data-bs-toggle="modal" data-bs-target="#editServiceModal<?= $row['id']; ?>"><i class="bi bi-pencil"></i> Edit</button>
                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteServiceModal<?= $row['id']; ?>"><i class="bi bi-trash"></i> Delete</button>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="editServiceModal<?= $row['id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content">
                            <form method="POST">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Service: <strong><?= htmlspecialchars($row['service_name']); ?></strong></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <div class="row g-3">
                                        <div class="col-md-6"><label class="form-label fw-bold">Company Name</label><input type="text" name="company_name" value="<?= htmlspecialchars($row['company_name']); ?>" class="form-control" required></div>
                                        <div class="col-md-6"><label class="form-label fw-bold">Service Name</label><input type="text" name="service_name" value="<?= htmlspecialchars($row['service_name']); ?>" class="form-control" required></div>
                                        
                                        <div class="col-md-6"><label class="form-label fw-bold">Company Email</label><input type="email" name="company_email" value="<?= htmlspecialchars($row['company_email']); ?>" class="form-control" required></div>
                                        <div class="col-md-6"><label class="form-label fw-bold">Contact Number</label><input type="text" name="contact_number" value="<?= htmlspecialchars($row['contact_number']); ?>" class="form-control" required></div>

                                        <div class="col-md-12"><label class="form-label fw-bold">Company Address</label><input type="text" name="company_address" value="<?= htmlspecialchars($row['company_address']); ?>" class="form-control" required></div>

                                        <div class="col-md-12"><label class="form-label fw-bold">Description</label><textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($row['description']); ?></textarea></div>

                                        <div class="col-md-3"><label class="form-label fw-bold">Price (₱)</label><input type="number" step="0.01" name="price" value="<?= htmlspecialchars($row['price']); ?>" class="form-control" required></div>
                                        
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">Date From</label>
                                            <input type="date" 
                                                   name="available_date_from" 
                                                   value="<?= htmlspecialchars($row['available_date_from']); ?>" 
                                                   class="form-control" 
                                                   required>
                                        </div>
                                        <div class="col-md-3"><label class="form-label fw-bold">Date To</label><input type="date" name="available_date_to" value="<?= htmlspecialchars($row['available_date_to']); ?>" class="form-control" required></div>
                                        
                                        <div class="col-md-3"><label class="form-label fw-bold">Time From</label><input type="time" name="available_time_from" value="<?= htmlspecialchars($row['available_time_from']); ?>" class="form-control" required></div>
                                        <div class="col-md-3"><label class="form-label fw-bold">Time To</label><input type="time" name="available_time_to" value="<?= htmlspecialchars($row['available_time_to']); ?>" class="form-control" required></div>

                                        <div class="col-md-3 d-flex align-items-center"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_approved" role="switch" <?= $row['is_approved'] ? 'checked' : ''; ?>><label class="form-check-label fw-bold">Is Approved</label></div></div>
                                        <div class="col-md-3 d-flex align-items-center"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_available" role="switch" <?= $row['is_available'] ? 'checked' : ''; ?>><label class="form-check-label fw-bold">Is Available</label></div></div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary btn-rounded-pill"><i class="bi bi-save me-1"></i> Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="deleteServiceModal<?= $row['id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-sm modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg">
                            <form method="POST">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i> Delete Service</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-center p-4">
                                    <p class="lead">Are you sure you want to delete <strong><?= htmlspecialchars($row['service_name']); ?></strong>?</p>
                                    <small class="text-muted d-block">This action cannot be undone.</small>
                                </div>
                                <div class="modal-footer d-flex justify-content-between">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash-fill me-1"></i> Yes, Delete</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center py-5">
                        <i class="bi bi-info-circle-fill display-4 d-block mb-3"></i>
                        <h4>No services found.</h4>
                        <p class="mb-0">Try clearing the search filter or adding a new service.</p>
                    </div>
                </div>
            <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add New Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label fw-bold">User ID</label><input type="number" name="user_id" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label fw-bold">Company Name</label><input type="text" name="company_name" class="form-control" required></div>
                        
                        <div class="col-md-6"><label class="form-label fw-bold">Company Email</label><input type="email" name="company_email" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label fw-bold">Contact Number</label><input type="text" name="contact_number" class="form-control" required></div>

                        <div class="col-md-12"><label class="form-label fw-bold">Company Address</label><input type="text" name="company_address" class="form-control" required></div>
                        
                        <div class="col-md-12"><label class="form-label fw-bold">Service Name</label><input type="text" name="service_name" class="form-control" required></div>
                        <div class="col-md-12"><label class="form-label fw-bold">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                        
                        <div class="col-md-3"><label class="form-label fw-bold">Price (₱)</label><input type="number" step="0.01" name="price" class="form-control" required></div>
                        
                        <div class="col-md-3"><label class="form-label fw-bold">Available Date From</label><input type="date" name="available_date_from" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label fw-bold">Available Date To</label><input type="date" name="available_date_to" class="form-control" required></div>
                        
                        <div class="col-md-3"><label class="form-label fw-bold">Available Time From</label><input type="time" name="available_time_from" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label fw-bold">Available Time To</label><input type="time" name="available_time_to" class="form-control" required></div>

                        <div class="col-md-3 d-flex align-items-center"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_approved" role="switch"><label class="form-check-label fw-bold">Is Approved</label></div></div>
                        <div class="col-md-3 d-flex align-items-center"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_available" role="switch"><label class="form-check-label fw-bold">Is Available</label></div></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-rounded-pill"><i class="bi bi-plus-lg me-1"></i> Add Service</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (!empty($toastMessage)): ?>
<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="liveToast" 
         class="toast shadow-lg border-0" 
         role="alert" 
         aria-live="assertive" 
         aria-atomic="true" 
         data-bs-autohide="true" 
         data-bs-delay="7000"> 
        
        <div class="toast-header text-white bg-<?= $toastColor; ?>">
            <i class="bi <?= $toastIcon; ?> me-2 fs-5"></i>
            <strong class="me-auto"><?= $toastTitle; ?></strong>
            <small>Just now</small>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body bg-white text-dark fw-normal">
            <?= $toastMessage; // Already HTML escaped in PHP initialization ?>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    // Standard toast initializer
    const toastEl = document.getElementById('liveToast');
    if (toastEl) new bootstrap.Toast(toastEl).show();
});
</script>

</body>
</html>