<?php
session_start();
require '../db.php'; // Assuming this file connects to your database

// ==========================
// Handle Form Submissions
// ==========================
$toastMessage = '';
$toastColor = 'success'; // Default color for success

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            // ADD USER
            if ($_POST['action'] === 'add') {
                // ... (Original PHP logic for ADD USER)
                $first_name = $_POST['first_name'];
                $last_name = $_POST['last_name'];
                $email = $_POST['email'];
                $phone = $_POST['phone_number'];
                $role = $_POST['role'];
                $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

                $sql = "INSERT INTO users (first_name, last_name, email, phone_number, role, password, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssss", $first_name, $last_name, $email, $phone, $role, $password);
                $stmt->execute();

                $toastMessage = "User added successfully! 🎉";
            }

            // EDIT USER
            elseif ($_POST['action'] === 'edit') {
                // ... (Original PHP logic for EDIT USER)
                $id = $_POST['id'];
                $first_name = $_POST['first_name'];
                $last_name = $_POST['last_name'];
                $email = $_POST['email'];
                $phone = $_POST['phone_number'];
                $role = $_POST['role'];

                $sql = "UPDATE users SET first_name=?, last_name=?, email=?, phone_number=?, role=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssi", $first_name, $last_name, $email, $phone, $role, $id);
                $stmt->execute();

                $toastMessage = "User updated successfully! ✅";
            }

            // DELETE USER
            elseif ($_POST['action'] === 'delete') {
                // ... (Original PHP logic for DELETE USER)
                $id = $_POST['id'];
                $sql = "DELETE FROM users WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();

                $toastMessage = "User deleted successfully! 🗑️";
                $toastColor = 'danger';
            }
        } catch (Exception $e) {
            $toastMessage = "An error occurred: " . $e->getMessage();
            $toastColor = 'danger';
        }

        // Redirect to clear POST data and prevent resubmission
        // This is a crucial UX fix for form handling
        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?') . "?toast=" . urlencode($toastMessage) . "&color=" . $toastColor);
        exit();
    }
}

// Check for toast message in GET (after redirect)
if (isset($_GET['toast']) && isset($_GET['color'])) {
    $toastMessage = htmlspecialchars($_GET['toast']);
    $toastColor = htmlspecialchars($_GET['color']);
}

// ==========================
// Fetch Users (With Search)
// ==========================
$search = isset($_GET['search']) ? $_GET['search'] : '';
$query = "SELECT * FROM users WHERE CONCAT(first_name, ' ', last_name, ' ', email, ' ', role) LIKE ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$like = "%$search%";
$stmt->bind_param("s", $like);
$stmt->execute();
$result = $stmt->get_result();

$conn->close(); // Close the connection after all operations
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --background-light: #f4f6f9;
        }

        body {
            background-color: var(--background-light);
            font-family: 'Inter', sans-serif; /* A more modern font choice */
        }

        .card {
            border-radius: 16px;
            border: none;
            overflow: hidden; /* Ensures border-radius applies to header/body */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); /* Softer shadow */
        }

        .card-header-custom {
            background-color: var(--primary-color);
            color: white;
            padding: 1.5rem 2rem;
            border-bottom: none;
        }

        .table thead {
            background-color: var(--primary-color); /* Kept your color */
            color: white;
            font-weight: 600;
        }

        .table > :not(caption) > * > * {
            padding: 1rem 1rem; /* Better padding in cells */
        }

        .table tbody tr {
            transition: background-color 0.2s ease;
        }

        .table tbody tr:hover {
            background-color: #e9ecef;
            cursor: pointer;
        }

        .profile-img {
            width: 40px; /* Slightly smaller for better table density */
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-color);
            padding: 1px;
        }

        .btn-rounded-pill {
            border-radius: 50rem; /* Full pill shape */
            padding: 0.5rem 1.5rem;
            font-weight: 500;
        }

        .modal-header-custom {
            border-bottom: none;
            padding-bottom: 0;
        }

        .toast-container {
            z-index: 2000;
        }
        
        /* Mobile-specific table styles */
        @media (max-width: 768px) {
            .table-responsive {
                border: 1px solid #dee2e6;
                border-radius: 10px;
            }
            .table thead {
                display: none; /* Hide header row on mobile */
            }
            .table, .table tbody, .table tr, .table td {
                display: block;
            }
            .table tr {
                margin-bottom: 1rem;
                border: 1px solid #dee2e6;
                border-radius: 8px;
            }
            .table td {
                text-align: right;
                padding-left: 50%;
                position: relative;
                border: none;
            }
            .table td::before {
                content: attr(data-label);
                position: absolute;
                left: 10px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                font-weight: 600;
                text-align: left;
            }
            .table td:first-child { border-top-left-radius: 8px; border-top-right-radius: 8px; }
            .table td:last-child { border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; }
        }

    </style>
</head>
<body>

<div class="container py-5">
    <div class="card shadow-lg"> <div class="card-header-custom d-flex justify-content-between align-items-center">
            <h3 class="mb-0"><i class="bi bi-people-fill me-3"></i>User Management Dashboard</h3>
            <button class="btn btn-light btn-rounded-pill fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-person-plus-fill me-1"></i> Add New User
            </button>
        </div>

        <div class="card-body p-4">

            <form method="GET" class="mb-4">
                <div class="input-group input-group-lg shadow-sm">
                    <input type="search" name="search" class="form-control" placeholder="Search user by name, email, or role..." value="<?= htmlspecialchars($search) ?>" aria-label="Search users">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i> Search</button>
                    <?php if (!empty($search)): ?>
                    <a href="manage_users.php" class="btn btn-outline-secondary" title="Clear Search"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle table-hover caption-top">
                    <caption>Total Users: <?= $result->num_rows; ?></caption>
                    <thead>
                        <tr>
                            <th scope="col">User</th>
                            <th scope="col">Email / Phone</th>
                            <th scope="col">Role</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="d-none d-md-table-cell">Joined</th>
                            <th scope="col" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td data-label="User:">
                                        <div class="d-flex align-items-center">
                                            <img src="<?= !empty($row['profile_image']) ? 'uploads/profile_pictures/' . htmlspecialchars($row['profile_image']) : 'https://i.pravatar.cc/40?img=' . $row['id']; ?>" class="profile-img me-3" alt="Profile of <?= htmlspecialchars($row['first_name']); ?>">
                                            <div>
                                                <div class="fw-bold"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></div>
                                                <small class="text-muted d-md-none"><?= htmlspecialchars($row['email']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="Contact:">
                                        <div class="d-none d-md-block text-truncate" style="max-width: 200px;"><?= htmlspecialchars($row['email']); ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($row['phone_number']); ?></div>
                                    </td>
                                    <td data-label="Role:">
                                        <?php 
                                            $role_class = $row['role'] == 'provider' ? 'info' : ($row['role'] == 'admin' ? 'primary' : 'success');
                                        ?>
                                        <span class="badge bg-<?= $role_class; ?> rounded-pill px-3 py-2">
                                            <?= ucfirst(htmlspecialchars($row['role'])); ?>
                                        </span>
                                    </td>
                                    <td data-label="Status:">
                                        <?php if (isset($row['is_verified']) && $row['is_verified']): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Verified <i class="bi bi-check-circle-fill"></i></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Pending <i class="bi bi-x-circle-fill"></i></span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Joined:" class="text-muted d-none d-md-table-cell">
                                        <small><?= date("M d, Y", strtotime($row['created_at'])); ?></small>
                                    </td>
                                    <td class="text-center" data-label="Actions:">
                                        <button title="Edit User" class="btn btn-sm btn-outline-warning me-1" data-bs-toggle="modal" data-bs-target="#editUserModal<?= $row['id']; ?>"><i class="bi bi-pencil"></i><span class="d-inline d-md-none ms-1">Edit</span></button>
                                        <button title="Delete User" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal<?= $row['id']; ?>"><i class="bi bi-trash"></i><span class="d-inline d-md-none ms-1">Delete</span></button>
                                    </td>
                                </tr>

                                <div class="modal fade" id="editUserModal<?= $row['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg modal-dialog-centered">
                                        <div class="modal-content shadow-lg">
                                            <form method="POST">
                                                <input type="hidden" name="action" value="edit">
                                                <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                                <div class="modal-header modal-header-custom">
                                                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i> Edit User: <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold">First Name</label>
                                                            <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($row['first_name']); ?>" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold">Last Name</label>
                                                            <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($row['last_name']); ?>" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold">Email</label>
                                                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($row['email']); ?>" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold">Phone</label>
                                                            <input type="tel" name="phone_number" class="form-control" value="<?= htmlspecialchars($row['phone_number']); ?>" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold">Role</label>
                                                            <select name="role" class="form-select">
                                                                <option value="client" <?= $row['role'] == 'client' ? 'selected' : ''; ?>>Client</option>
                                                                <option value="provider" <?= $row['role'] == 'provider' ? 'selected' : ''; ?>>Provider</option>
                                                                <option value="admin" <?= $row['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <p class="mt-4 pt-2 text-muted fst-italic">Leave password field empty to keep current password.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary btn-rounded-pill">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal fade" id="deleteUserModal<?= $row['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-sm modal-dialog-centered">
                                        <div class="modal-content border-0 shadow-lg">
                                            <form method="POST">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i> Delete User</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body text-center p-4">
                                                    <i class="bi bi-person-x-fill text-danger display-4 mb-3"></i>
                                                    <p class="lead">Are you sure you want to delete **<?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>**?</p>
                                                    <small class="text-muted d-block">This action cannot be undone.</small>
                                                </div>
                                                <div class="modal-footer d-flex justify-content-between">
                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-danger">Yes, Delete</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-info-circle me-1"></i> No users found matching "<?= htmlspecialchars($search) ?>".</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-person-plus-fill me-2"></i> Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">First Name</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Last Name</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Phone</label>
                            <input type="tel" name="phone_number" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Role</label>
                            <select name="role" class="form-select" required>
                                <option value="client" selected>Client</option>
                                <option value="provider">Provider</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-rounded-pill">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (!empty($toastMessage)): ?>
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="liveToast" class="toast align-items-center text-white bg-<?= $toastColor; ?> border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
        <div class="d-flex">
            <div class="toast-body fw-bold">
                <?= $toastMessage; ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const toastEl = document.getElementById('liveToast');
    if (toastEl) {
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
        // Removed the automatic redirect timeout from your original script.
        // It's generally bad UX to redirect automatically after a success message.
        // The URL is already cleaned up via the PHP header redirect above.
    }
});
</script>

</body>
</html>