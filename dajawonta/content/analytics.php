<?php
session_start();
// Assumes db.php is in the parent directory (e.g., ../db.php)
include '../db.php';

// Security Check: Ensure user is logged in AND is an Administrator (role '2')
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_rules'] != '2') {
    die("<div style='font-family: sans-serif; text-align: center; padding: 40px;'>
            <h1 style='color: #dc3545;'>Access Denied</h1>
            <p style='color: #333;'>You do not have permission to view this page.</p>
         </div>");
}

// 1. Fetch Total Revenue
$revenue_sql = "SELECT SUM(total_price) AS total_revenue FROM bookings WHERE payment_status = 'paid'";
$revenue_result = $conn->query($revenue_sql);
$total_revenue = $revenue_result->fetch_assoc()['total_revenue'] ?? 0;

// 2. Fetch Total Bookings
$bookings_sql = "SELECT COUNT(*) AS total_bookings FROM bookings";
$bookings_result = $conn->query($bookings_sql);
$total_bookings = $bookings_result->fetch_assoc()['total_bookings'] ?? 0;

// 3. Fetch Total Clients (user_rules = '0')
$clients_sql = "SELECT COUNT(*) AS total_clients FROM users WHERE user_rules = '0'";
$clients_result = $conn->query($clients_sql);
$total_clients = $clients_result->fetch_assoc()['total_clients'] ?? 0;

// 4. Fetch Total Providers (user_rules = '1')
$providers_sql = "SELECT COUNT(*) AS total_providers FROM users WHERE user_rules = '1'";
$providers_result = $conn->query($providers_sql);
$total_providers = $providers_result->fetch_assoc()['total_providers'] ?? 0;

// 5. Fetch Pending Service Providers (is_approved = 0)
$pending_providers_sql = "SELECT COUNT(*) AS pending_providers FROM service_providers WHERE is_approved = 0";
$pending_providers_result = $conn->query($pending_providers_sql);
$pending_providers = $pending_providers_result->fetch_assoc()['pending_providers'] ?? 0;

// 6. Fetch Pending Bookings
$pending_bookings_sql = "SELECT COUNT(*) AS pending_bookings FROM bookings WHERE booking_status = 'pending'";
$pending_bookings_result = $conn->query($pending_bookings_sql);
$pending_bookings = $pending_bookings_result->fetch_assoc()['pending_bookings'] ?? 0;

// 7. Fetch Bookings per Month for Chart
$chart_sql = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') AS month, 
                COUNT(*) AS booking_count
              FROM bookings 
              GROUP BY month 
              ORDER BY month ASC 
              LIMIT 12"; // Get last 12 months of data
$chart_result = $conn->query($chart_sql);

$chart_labels = [];
$chart_data = [];
if ($chart_result->num_rows > 0) {
    while ($row = $chart_result->fetch_assoc()) {
        // Format 'YYYY-MM' to 'M YYYY' (e.g., '10 2025')
        $date_obj = DateTime::createFromFormat('!Y-m', $row['month']);
        $chart_labels[] = $date_obj->format('M Y');
        $chart_data[] = $row['booking_count'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa; /* Match dashboard bg */
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        .stat-card-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
    </style>
</head>
<body class="bg-gray-100">

    <div class="container mx-auto p-6 md:p-8">

        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Analytics Dashboard</h1>
            <p class="text-gray-500 mt-1">Overview of your platform's performance.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            
            <div class="stat-card p-6 flex items-center space-x-5">
                <div class="stat-card-icon bg-green-100 text-green-600">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Revenue</p>
                    <p class="text-3xl font-bold text-gray-800">₱<?php echo number_format($total_revenue, 2); ?></p>
                </div>
            </div>

            <div class="stat-card p-6 flex items-center space-x-5">
                <div class="stat-card-icon bg-blue-100 text-blue-600">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Bookings</p>
                    <p class="text-3xl font-bold text-gray-800"><?php echo $total_bookings; ?></p>
                </div>
            </div>
            
            <div class="stat-card p-6 flex items-center space-x-5">
                <div class="stat-card-icon bg-purple-100 text-purple-600">
                    <i class="fas fa-user"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Clients</p>
                    <p class="text-3xl font-bold text-gray-800"><?php echo $total_clients; ?></p>
                </div>
            </div>

            <div class="stat-card p-6 flex items-center space-x-5">
                <div class="stat-card-icon bg-indigo-100 text-indigo-600">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Providers</p>
                    <p class="text-3xl font-bold text-gray-800"><?php echo $total_providers; ?></p>
                </div>
            </div>

            <div class="stat-card p-6 flex items-center space-x-5">
                <div class="stat-card-icon bg-yellow-100 text-yellow-600">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Pending Bookings</p>
                    <p class="text-3xl font-bold text-gray-800"><?php echo $pending_bookings; ?></p>
                </div>
            </div>

            <div class="stat-card p-6 flex items-center space-x-5">
                <div class="stat-card-icon bg-red-100 text-red-600">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Provider Approvals</p>
                    <p class="text-3xl font-bold text-gray-800"><?php echo $pending_providers; ?></p>
                </div>
            </div>

        </div>

        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Bookings Per Month</h2>
            <div class="h-80">
                <canvas id="bookingsChart"></canvas>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get the data from PHP
        const chartLabels = <?php echo json_encode($chart_labels); ?>;
        const chartData = <?php echo json_encode($chart_data); ?>;

        const ctx = document.getElementById('bookingsChart').getContext('2d');
        
        // Create the gradient
        const gradient = ctx.createLinearGradient(0, 0, 0, 320); // y-axis gradient
        gradient.addColorStop(0, 'rgba(13, 110, 253, 0.5)');   // Start color (blue, semi-transparent)
        gradient.addColorStop(1, 'rgba(13, 110, 253, 0)');     // End color (transparent)

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Bookings',
                    data: chartData,
                    backgroundColor: 'rgba(13, 110, 253, 0.8)', // --primary color
                    borderColor: 'rgba(13, 110, 253, 1)',
                    borderWidth: 2,
                    borderRadius: 5,
                    barPercentage: 0.6,
                    tension: 0.4, // Makes it curve (for line chart)
                    // For bar chart, we can use a gradient
                    backgroundColor: gradient 
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false,
                            color: '#e2e8f0' // gray-200
                        },
                        ticks: {
                            color: '#64748b', // gray-500
                            // Ensure only integers are shown
                            callback: function(value) {
                                if (value % 1 === 0) {
                                    return value;
                                }
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#64748b' // gray-500
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#1e293b', // gray-800
                        titleFont: { size: 14 },
                        bodyFont: { size: 12 },
                        padding: 12,
                        cornerRadius: 6,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return `Bookings: ${context.parsed.y}`;
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index',
                }
            }
        });
    });
    </script>

</body>
</html>