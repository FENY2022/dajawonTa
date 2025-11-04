<?php
session_start();
include '../db.php'; // ../ to go up one level

// Ensure user is logged in and is a provider
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_rules'] != '1') {
    echo "<p class='text-red-500 p-4'>Access denied. You must be logged in as a provider to view this page.</p>";
    exit;
}

$provider_user_id = $_SESSION['user_id'];

// 1. Find the provider_id(s) associated with this user_id
// A provider might have multiple service listings, but they all share the same user_id.
// We need to get all ratings for all services offered by this user.
$sql_provider_ids = "SELECT id FROM service_providers WHERE user_id = ?";
$stmt_ids = $conn->prepare($sql_provider_ids);
$stmt_ids->bind_param("i", $provider_user_id);
$stmt_ids->execute();
$result_ids = $stmt_ids->get_result();
$provider_ids = [];
while ($row = $result_ids->fetch_assoc()) {
    $provider_ids[] = $row['id'];
}
$stmt_ids->close();

$ratings = [];
$average_rating = 0;
$total_ratings = 0;

if (!empty($provider_ids)) {
    // Create placeholders for the IN clause (e.g., ?,?,?)
    $placeholders = implode(',', array_fill(0, count($provider_ids), '?'));
    // Create the type string (e.g., "iii")
    $types = str_repeat('i', count($provider_ids));
    
    // 2. Get all ratings for these provider_ids
    $sql_ratings = "SELECT rating, comment, created_at FROM provider_ratings WHERE provider_id IN ($placeholders) ORDER BY created_at DESC";
    $stmt_ratings = $conn->prepare($sql_ratings);
    // Bind the provider IDs
    $stmt_ratings->bind_param($types, ...$provider_ids);
    $stmt_ratings->execute();
    $result_ratings = $stmt_ratings->get_result();
    
    $total_score = 0;
    while ($row = $result_ratings->fetch_assoc()) {
        $ratings[] = $row;
        $total_score += $row['rating'];
    }
    $total_ratings = count($ratings);
    
    if ($total_ratings > 0) {
        $average_rating = round($total_score / $total_ratings, 1);
    }
    $stmt_ratings->close();
}
$conn->close();

/**
 * Function to display stars based on a rating
 */
function display_stars($rating) {
    $stars_html = '<div class="static-stars text-xl text-amber-500">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars_html .= '<i class="fas fa-star"></i>'; // Full star
        } else {
            $stars_html .= '<i class="far fa-star text-gray-300"></i>'; // Empty star
        }
    }
    $stars_html .= '</div>';
    return $stars_html;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Ratings & Feedback</title>
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
        }
    </style>
</head>
<body class="p-4 md:p-8">

    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">My Ratings & Feedback</h1>

        <!-- Overall Rating Summary Card -->
        <div class="card p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Overall Rating</h2>
            <?php if ($total_ratings > 0): ?>
                <div class="flex items-center space-x-4">
                    <span class="text-5xl font-bold text-gray-800"><?php echo $average_rating; ?></span>
                    <div class="flex flex-col">
                        <?php echo display_stars($average_rating); ?>
                        <span class="text-gray-500 text-sm mt-1">Based on <?php echo $total_ratings; ?> review<?php echo $total_ratings > 1 ? 's' : ''; ?></span>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-gray-500">You have not received any ratings yet.</p>
            <?php endif; ?>
        </div>

        <!-- Individual Reviews -->
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">All Reviews</h2>
        <div class="space-y-5">
            <?php if (empty($ratings)): ?>
                <div class="card p-6 text-center">
                    <p class="text-gray-500">No comments or feedback have been left.</p>
                </div>
            <?php else: ?>
                <?php foreach ($ratings as $rating): ?>
                    <div class="card p-6">
                        <div class="flex justify-between items-start">
                            <?php echo display_stars($rating['rating']); ?>
                            <span class="text-sm text-gray-400"><?php echo date("F j, Y", strtotime($rating['created_at'])); ?></span>
                        </div>
                        <?php if (!empty($rating['comment'])): ?>
                            <p class="text-gray-700 mt-4 italic">"<?php echo htmlspecialchars($rating['comment']); ?>"</p>
                        <?php else: ?>
                            <p class="text-gray-500 mt-4 italic">No comment left.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
