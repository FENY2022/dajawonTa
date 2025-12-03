<?php
// 1. DATABASE CONNECTION
// Assuming db.php is one directory up, just like in your dashboard.php
require_once '../db.php'; 

/**
 * AI-Generated Icon Function:
 * Maps service names to relevant Font Awesome 6 icons for visual representation.
 * @param string $serviceName The name of the service (e.g., 'Plumbing', 'Electrical').
 * @return string The Font Awesome icon class (e.g., 'fa-toilet').
 */
function getServiceIcon($serviceName) {
    // Convert to lowercase for case-insensitive matching
    $name = strtolower($serviceName);

    // AI/Logic-based icon selection
    if (strpos($name, 'plumbing') !== false || strpos($name, 'leak') !== false) {
        return 'fa-faucet-drip text-blue-500'; // Plumbing icon
    } elseif (strpos($name, 'electrical') !== false || strpos($name, 'wiring') !== false) {
        return 'fa-lightbulb text-yellow-600'; // Electrical icon
    } elseif (strpos($name, 'carpentry') !== false || strpos($name, 'furniture') !== false) {
        return 'fa-hammer text-amber-700'; // Carpentry icon
    } elseif (strpos($name, 'painting') !== false || strpos($name, 'renovation') !== false) {
        return 'fa-paint-roller text-pink-500'; // Painting/Renovation icon
    } elseif (strpos($name, 'masonry') !== false || strpos($name, 'welding') !== false || strpos($name, 'construction') !== false) {
        return 'fa-helmet-safety text-gray-500'; // Construction/Masonry icon
    } elseif (strpos($name, 'shoe') !== false || strpos($name, 'repair') !== false) {
        return 'fa-shoe-prints text-red-600'; // Shoe Repair icon
    } else {
        return 'fa-screwdriver-wrench text-indigo-500'; // Default tool icon
    }
}

// 2. FETCH ALL SERVICES
$services = [];
$sql = "SELECT service_id, service_name, description FROM services ORDER BY service_name";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
}

$conn->close();

// Fallback link for the 'Back' button
$dashboard_link = 'dashboard.php'; 
// NOTE: I changed this back to 'dashboard.php' as per your original file structure for consistency.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Available Services | DajawonTa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
        }

        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .dashboard-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .service-card {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            background-color: #ffffff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            /* Add some padding to make room for the icon */
            padding-top: 2rem; 
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 0;
            background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%);
            transition: all 0.3s ease;
        }

        .service-card:hover::before {
            height: 100%;
        }

        .service-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="p-4 md:p-6 lg:p-8">

    <div class="max-w-7xl mx-auto">
        <div class="dashboard-card p-6 lg:p-8">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 pb-6 border-b border-gray-200">
                <h2 class="text-2xl lg:text-3xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-list-check text-purple-500 mr-3"></i>
                    All Available Services
                </h2>
                <a href="<?php echo $dashboard_link; ?>" class="text-sm text-blue-500 hover:text-blue-700 flex items-center mt-2 sm:mt-0">
                    <i class="fas fa-arrow-left mr-2 text-xs"></i>
                    Back to Dashboard
                </a>
            </div>
            
            <?php if (!empty($services)): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    
                    <?php foreach ($services as $service): ?>
                    <?php $icon_class = getServiceIcon($service['service_name']); ?>
                    
                    <a href="serviceProvider.php?service_id=<?php echo $service['service_id']; ?>"
                       class="service-card p-5 block quick-action-modal"
                       data-title="<?php echo htmlspecialchars($service['service_name']); ?>">
                        
                        <div class="mb-3">
                            <i class="fas <?php echo $icon_class; ?> text-3xl"></i>
                        </div>
                        
                        <h3 class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($service['service_name']); ?></h3>
                        <p class="text-sm text-gray-600 mt-2 line-clamp-3"><?php echo htmlspecialchars($service['description']); ?></p>
                        
                        <div class="flex items-center mt-4 text-sm text-blue-500 font-medium">
                            <span>Book now</span>
                            <i class="fas fa-arrow-right ml-2 text-xs"></i>
                        </div>
                    </a>
                    <?php endforeach; ?>
                
                </div>
            <?php else: ?>
                <div class="text-center py-16">
                    <i class="fas fa-tools text-5xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-medium text-gray-700">No Services Found</h3>
                    <p class="text-gray-500 mt-2">No services are currently listed. Please check back later or contact the administrator.</p>
                </div>
            <?php endif; ?>
        
        </div>
    </div>

    <div id="iframeModal" class="fixed inset-0 z-50 hidden overflow-auto bg-gray-900 bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl md:max-w-3xl lg:max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
            <div class="flex justify-between items-center p-4 border-b border-gray-200">
                <h3 id="modal-title" class="text-xl font-semibold text-gray-800">Service Details</h3>
                <button id="closeModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="flex-grow">
                <iframe id="modalIframe" src="" frameborder="0" class="w-full h-[80vh]"></iframe>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('iframeModal');
            const iframe = document.getElementById('modalIframe');
            const closeModalBtn = document.getElementById('closeModal');
            const modalTitle = document.getElementById('modal-title');
            
            // Select all links that should open the modal
            const modalLinks = document.querySelectorAll('.quick-action-modal');

            // Open modal and load iframe
            modalLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault(); // Prevent the browser from navigating
                    
                    const url = this.getAttribute('href');
                    // Get title from the h3 element inside the card, or use data-title
                    let title = this.getAttribute('data-title');
                    if (!title) {
                        const titleElement = this.querySelector('h3');
                        title = titleElement ? titleElement.textContent : 'Service Details';
                    }
                    
                    modalTitle.textContent = title;
                    iframe.src = url;
                    modal.classList.remove('hidden');
                    
                    // Add a one-time event listener to close the modal when clicking outside
                    modal.addEventListener('click', function(e) {
                        if (e.target === this) {
                            closeModal();
                        }
                    }, { once: true });
                });
            });

            // Close modal function
            function closeModal() {
                modal.classList.add('hidden');
                iframe.src = ''; // Clear iframe content
            }

            // Close modal with button
            closeModalBtn.addEventListener('click', closeModal);
        });
    </script>
    
</body>
</html>