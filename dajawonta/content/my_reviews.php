<?php
session_start();
include '../db.php'; // ../ to go up one level to find db.php

// Ensure user is logged in and is a client
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_rules'] != '0') {
    // User is not a client, or not logged in
    echo "<p class='text-red-500 p-4'>Access denied. You must be logged in as a client to view this page.</p>";
    exit;
}

$customer_id = $_SESSION['user_id'];
$today = date("Y-m-d");

// Query to get completed bookings for this customer
// We JOIN with service_providers to get the company name
// We LEFT JOIN with provider_ratings to check if this booking_id has already been rated
$sql = "SELECT 
            b.id AS booking_id, 
            b.booking_date_to, 
            sp.company_name, 
            sp.id AS provider_id,
            pr.id AS rating_id,
            pr.rating,
            pr.comment
        FROM 
            bookings b
        JOIN 
            service_providers sp ON b.provider_id = sp.id
        LEFT JOIN 
            provider_ratings pr ON b.id = pr.booking_id
        WHERE 
            b.customer_id = ? 
            AND b.booking_status = 'approved'
            AND b.booking_date_to < ?
        ORDER BY 
            b.booking_date_to DESC";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("is", $customer_id, $today);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reviews</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
        }
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        .card:hover {
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        /* Star Rating Styles */
        .rating-stars {
            display: inline-flex;
            flex-direction: row-reverse; /* This makes stars fill from left to right */
            justify-content: center;
        }
        .rating-stars input[type="radio"] {
            display: none;
        }
        .rating-stars label {
            font-size: 2.5rem; /* Increased size */
            color: #d1d5db; /* gray-300 */
            cursor: pointer;
            transition: color 0.2s;
            padding: 0 0.25rem; /* Spacing between stars */
        }
        .rating-stars input[type="radio"]:checked ~ label,
        .rating-stars label:hover,
        .rating-stars label:hover ~ label {
            color: #f59e0b; /* amber-500 */
        }
        
        /* Static stars (for display) */
        .static-stars {
            color: #f59e0b; /* amber-500 */
        }
        .static-stars .fa-star-half-alt {
            color: #f59e0b;
        }
        .static-stars .far {
            color: #d1d5db; /* gray-300 */
        }
    </style>
</head>
<body class="p-4 md:p-8">

    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">My Reviews</h1>
        <p class="text-lg text-gray-600 mb-8">Rate your completed services to help providers and other users.</p>

        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                <strong class="font-bold">Success!</strong>
                <span class="block sm:inline"><?php echo htmlspecialchars($_GET['success']); ?></span>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline"><?php echo htmlspecialchars($_GET['error']); ?></span>
            </div>
        <?php endif; ?>

        <div class="space-y-6">
            <?php if (empty($bookings)): ?>
                <div class="card p-6 text-center">
                    <p class="text-gray-500">You have no completed bookings to review yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($bookings as $booking): ?>
                    <div class="card p-6">
                        <div class="flex flex-col md:flex-row justify-between md:items-center">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($booking['company_name']); ?></h2>
                                <p class="text-sm text-gray-500">Service completed on: <?php echo date("F j, Y", strtotime($booking['booking_date_to'])); ?></p>
                            </div>
                            <div class="mt-4 md:mt-0 md:ml-6">
                                <?php if ($booking['rating_id']): ?>
                                    <!-- Already Rated -->
                                    <div class="text-right">
                                        <h3 class="text-sm font-medium text-gray-700 mb-2">Your Rating:</h3>
                                        <div class="static-stars text-2xl">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="<?php echo $i <= $booking['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <?php if (!empty($booking['comment'])): ?>
                                            <p class="text-sm text-gray-600 mt-2 italic">"<?php echo htmlspecialchars($booking['comment']); ?>"</p>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <!-- Rating Form -->
                                    <form action="submit_rating.php" method="POST">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                        <input type="hidden" name="provider_id" value="<?php echo $booking['provider_id']; ?>">
                                        <input type="hidden" name="customer_id" value="<?php echo $customer_id; ?>">
                                        
                                        <div class="rating-stars">
                                            <input type="radio" id="star5-<?php echo $booking['booking_id']; ?>" name="rating" value="5" required><label for="star5-<?php echo $booking['booking_id']; ?>">&#9733;</label>
                                            <input type="radio" id="star4-<?php echo $booking['booking_id']; ?>" name="rating" value="4"><label for="star4-<?php echo $booking['booking_id']; ?>">&#9733;</label>
                                            <input type="radio" id="star3-<?php echo $booking['booking_id']; ?>" name="rating" value="3"><label for="star3-<?php echo $booking['booking_id']; ?>">&#9733;</label>
                                            <input type="radio" id="star2-<?php echo $booking['booking_id']; ?>" name="rating" value="2"><label for="star2-<?php echo $booking['booking_id']; ?>">&#9733;</label>
                                            <input type="radio" id="star1-<?php echo $booking['booking_id']; ?>" name="rating" value="1"><label for="star1-<?php echo $booking['booking_id']; ?>">&#9733;</label>
                                        </div>
                                        
                                        <div class="mt-4">
                                            <label for="comment-<?php echo $booking['booking_id']; ?>" class="block text-sm font-medium text-gray-700">Add a comment (optional):</label>
                                            <textarea name="comment" id="comment-<?php echo $booking['booking_id']; ?>" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="Share your experience..."></textarea>
                                        </div>
                                        
                                        <div class="mt-4 text-right">
                                            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                Submit Review
                                            </button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
