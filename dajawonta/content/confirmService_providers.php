<?php
// Start the session to get user details
session_start();

// Include our database connection file
require '../db.php';

// Check if user_id is set in the session. If not, redirect them to the login page.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle the form submission for approval
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['provider_id'])) {
    $provider_id = $_POST['provider_id'];
    
    $provider_user_id = null;
    $company_name = '';

    // Step 1: Get the user_id and company_name of the provider being approved for the notification
    $sql_get_user = "SELECT user_id, company_name FROM service_providers WHERE id = ?";
    $stmt_get_user = $conn->prepare($sql_get_user);
    $stmt_get_user->bind_param("i", $provider_id);
    if ($stmt_get_user->execute()) {
        $result_user = $stmt_get_user->get_result();
        if ($row_user = $result_user->fetch_assoc()) {
            $provider_user_id = $row_user['user_id'];
            $company_name = $row_user['company_name'];
        }
    }
    $stmt_get_user->close();

    // Proceed with approval only if we found the provider's details
    if ($provider_user_id) {
        // Step 2: Update the provider's status to approved
        $sql_update = "UPDATE service_providers SET is_approved = 1 WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("i", $provider_id);

        if ($stmt_update->execute()) {
            // Mark the oldest unread admin notification about provider confirmation as read
            $admin_user_id = $_SESSION['user_id'];
            $link_pattern = '%dashboard.php?action=confirmService_providers%';
            $sql_mark_read = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND link LIKE ? AND is_read = 0 LIMIT 1";
            $stmt_mark_read = $conn->prepare($sql_mark_read);
            if ($stmt_mark_read) {
                $stmt_mark_read->bind_param("is", $admin_user_id, $link_pattern);
                $stmt_mark_read->execute();
                $stmt_mark_read->close();
            }

            // Step 3: Create a notification for the approved service provider
            $message = "Congratulations! Your service '" . htmlspecialchars($company_name) . "' has been approved.";
            $link = "dashboard.php?action=view_listings&provider_id=" . $provider_user_id;
            $role_for_notification = 1;
            $sql_notify = "INSERT INTO notifications (user_id, message, link, role) VALUES (?, ?, ?, ?)";
            $stmt_notify = $conn->prepare($sql_notify);
            $stmt_notify->bind_param("issi", $provider_user_id, $message, $link, $role_for_notification);
            $stmt_notify->execute();
            $stmt_notify->close();

            // Redirect with a success status
            header('Location: ' . $_SERVER['PHP_SELF'] . '?status=success');
            exit();
        } else {
            // Handle DB update error
            header('Location: ' . $_SERVER['PHP_SELF'] . '?status=error');
            exit();
        }
        $stmt_update->close();
    } else {
        // Handle case where provider ID was not found
        header('Location: ' . $_SERVER['PHP_SELF'] . '?status=notfound');
        exit();
    }
}

// SQL query to select all service providers that are not yet approved
$sql = "SELECT * FROM service_providers WHERE is_approved = 0";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Service Providers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754; /* Darker green for better contrast */
            --light-bg: #f8f9fa;
            --card-bg: #ffffff;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --card-hover-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }
        body {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--light-bg);
            color: #343a40;
            padding-top: 50px;
            padding-bottom: 50px;
        }
        .container { max-width: 1200px; }
        .header-section {
            padding-bottom: 3rem;
            text-align: center;
        }
        .header-section h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        .header-section p {
            font-size: 1.15rem;
            color: var(--secondary-color);
        }
        .card-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 2rem;
        }
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .card:hover {
            transform: translateY(-8px);
            box-shadow: var(--card-hover-shadow);
        }
        .card-body {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }
        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.25rem;
        }
        .card-text p {
            font-size: 0.95rem;
            margin-bottom: 0.85rem;
            display: flex;
            align-items: flex-start;
        }
        .card-text p strong {
            color: #212529;
            font-weight: 600;
        }
        .card-text p i {
            width: 24px;
            text-align: center;
            color: var(--primary-color);
            font-size: 1.1em;
            margin-right: 0.75rem;
            padding-top: 3px;
        }
        .btn-approve {
            background-color: var(--success-color);
            border-color: var(--success-color);
            color: white;
            font-weight: 600;
            width: 100%;
            margin-top: auto; /* Pushes the button to the bottom */
        }
        .alert-info {
            font-size: 1.2rem;
            padding: 2rem;
            border-radius: 1rem;
        }
        .modal-body p {
            font-size: 1.1rem;
        }
        /* Style for document thumbnails in modal */
        .doc-thumbnail {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border: 2px solid #eee;
            border-radius: 8px;
            transition: transform 0.2s;
        }
        .doc-thumbnail:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-section">
        <h1><i class="fas fa-handshake"></i> Pending Service Providers</h1>
        <p class="lead">Review and approve new service provider registrations.</p>
    </div>

    <div class="card-container">
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        ?>
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($row['company_name']); ?></h5>
                        <div class="card-text">
                            <p><i class="fas fa-briefcase"></i><strong>Service:</strong>&nbsp;<?php echo htmlspecialchars($row['service_name']); ?></p>
                            <p><i class="fas fa-map-marker-alt"></i><strong>Address:</strong>&nbsp;<?php echo htmlspecialchars($row['company_address']); ?></p>
                            <p><i class="fas fa-phone"></i><strong>Contact:</strong>&nbsp;<?php echo htmlspecialchars($row['contact_number']); ?></p>
                            <p><i class="fas fa-calendar-alt"></i><strong>Available Dates:</strong>&nbsp;<?php echo date("M j, Y", strtotime($row['available_date_from'])) . " to " . date("M j, Y", strtotime($row['available_date_to'])); ?></p>
                            <p><i class="fas fa-clock"></i><strong>Available Times:</strong>&nbsp;<?php echo date("g:i A", strtotime($row['available_time_from'])) . " to " . date("g:i A", strtotime($row['available_time_to'])); ?></p>
                            <p><i class="fas fa-tag"></i><strong>Price:</strong>&nbsp;₱<?php echo number_format($row['price'], 2); ?></p>
                        </div>
                        <button type="button" class="btn btn-approve mt-3" data-bs-toggle="modal" data-bs-target="#confirmModal"
                                data-id="<?php echo htmlspecialchars($row['id']); ?>"
                                data-company="<?php echo htmlspecialchars($row['company_name']); ?>"
                                data-address="<?php echo htmlspecialchars($row['company_address']); ?>"
                                data-contact="<?php echo htmlspecialchars($row['contact_number']); ?>"
                                data-service="<?php echo htmlspecialchars($row['service_name']); ?>"
                                data-description="<?php echo htmlspecialchars($row['service_description']); ?>"
                                data-date-range="<?php echo date("F j, Y", strtotime($row['available_date_from'])) . " to " . date("F j, Y", strtotime($row['available_date_to'])); ?>"
                                data-time-range="<?php echo date("g:i A", strtotime($row['available_time_from'])) . " to " . date("g:i A", strtotime($row['available_time_to'])); ?>"
                                data-price="₱<?php echo number_format($row['price'], 2); ?>"
                                data-documents="<?php echo htmlspecialchars($row['legal_documents']); // ?>">
                            <i class="fas fa-clipboard-check"></i> Review and Approve
                        </button>
                    </div>
                </div>
        <?php
            }
        } else {
            echo '<div class="col-12"><div class="alert alert-info text-center" role="alert">
                      <p class="mb-0">✨ All service providers have been approved. You\'re all caught up! ✨</p>
                  </div></div>';
        }
        $conn->close();
        ?>
    </div>
</div>

<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="confirmModalLabel"><i class="fas fa-check-circle me-2"></i> Confirm Approval</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Are you sure you want to approve this service provider?</strong></p>
                <div id="modal-details" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                    <input type="hidden" name="provider_id" id="provider-id-input">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-user-check me-1"></i> Approve</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
    <div id="responseToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto" id="toast-title"></strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toast-body"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Handles populating the confirmation modal with provider data
    document.getElementById('confirmModal').addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const modalDetails = this.querySelector('#modal-details');
        
        // Get existing data attributes from the button
        const dateRange = button.getAttribute('data-date-range');
        const timeRange = button.getAttribute('data-time-range');
        const price = button.getAttribute('data-price');
        
        // --- MODIFIED SECTION: Handle legal documents ---
        const documentsJson = button.getAttribute('data-documents');
        const basePath = '../uploads/legal_documents/';
        let documentsHtml = '';

        if (documentsJson && documentsJson !== 'NULL') { // Check if not empty or the literal string "NULL"
            try {
                const docArray = JSON.parse(documentsJson);
                if (Array.isArray(docArray) && docArray.length > 0) {
                    documentsHtml = '<p><strong>Legal Documents:</strong></p><div class="d-flex flex-wrap gap-2">';
                    docArray.forEach(docName => {
                        const docUrl = basePath + encodeURIComponent(docName);
                        // Create a thumbnail image that links to the full document
                        documentsHtml += `
                            <a href="${docUrl}" target="_blank" title="View ${docName}">
                                <img src="${docUrl}" alt="Legal Document" class="doc-thumbnail">
                            </a>
                        `;
                    });
                    documentsHtml += '</div>';
                } else {
                    documentsHtml = '<p><strong>Legal Documents:</strong> None provided.</p>';
                }
            } catch (e) {
                console.error('Error parsing legal_documents JSON:', e, documentsJson);
                documentsHtml = '<p><strong>Legal Documents:</strong> <span class="text-danger">Error loading documents.</span></p>';
            }
        } else {
            documentsHtml = '<p><strong>Legal Documents:</strong> None provided.</p>';
        }
        // --- END OF MODIFIED SECTION ---

        // Populate modal with all data, including the new documents section
        modalDetails.innerHTML = `
            <p><strong>Company:</strong> ${button.getAttribute('data-company')}</p>
            <p><strong>Service:</strong> ${button.getAttribute('data-service')}</p>
            <p><strong>Description:</strong> ${button.getAttribute('data-description')}</p>
            <hr>
            <p><strong>Dates:</strong> ${dateRange}</p>
            <p><strong>Times:</strong> ${timeRange}</p>
            <p><strong>Price:</strong> ${price}</p>
            <hr>
            ${documentsHtml} 
        `;
        
        this.querySelector('#provider-id-input').value = button.getAttribute('data-id');
    });

    // Handles showing toast notifications based on URL status
    document.addEventListener('DOMContentLoaded', function() {
        const toastEl = document.getElementById('responseToast');
        if (!toastEl) return;
        
        const responseToast = new bootstrap.Toast(toastEl, { delay: 5000 });
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        
        const toastTitleEl = document.getElementById('toast-title');
        const toastBodyEl = document.getElementById('toast-body');
        const toastHeaderEl = toastEl.querySelector('.toast-header');

        if (status) {
            let message = '', title = '', headerClass = '';
            
            switch (status) {
                case 'success':
                    title = '<i class="fas fa-check-circle me-2"></i> Success';
                    message = 'Service provider approved and notified successfully!';
                    headerClass = 'bg-success text-white';
                    break;
                case 'error':
                    title = '<i class="fas fa-exclamation-triangle me-2"></i> Error';
                    message = 'An error occurred. Please try again.';
                    headerClass = 'bg-danger text-white';
                    break;
                case 'notfound':
                    title = '<i class="fas fa-question-circle me-2"></i> Not Found';
                    message = 'The specified provider could not be found.';
                    headerClass = 'bg-warning text-dark';
                    break;
            }

            toastHeaderEl.className = 'toast-header ' + headerClass;
            toastTitleEl.innerHTML = title;
            toastBodyEl.textContent = message;
            responseToast.show();

            // Clean the URL
            if (window.history.replaceState) {
                const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({ path: cleanUrl }, '', cleanUrl);
            }
        }
    });
</script>

</body>
</html>