<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Provider Directory</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #28a745; /* Green for available */
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --warning-star: #f39c12; /* Yellow/Gold for stars */
            --border-radius: 12px;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: var(--dark); background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: var(--border-radius); box-shadow: var(--shadow); overflow: hidden; }
        header { background: linear-gradient(to right, var(--primary), var(--secondary)); color: white; padding: 2rem; text-align: center; }
        header h1 { font-size: 2.2rem; margin-bottom: 0.5rem; display: flex; align-items: center; justify-content: center; gap: 10px; }
        header p { font-size: 1.1rem; opacity: 0.9; max-width: 600px; margin: 0 auto; }
        .filter-section { padding: 2rem; background: var(--light); border-bottom: 1px solid var(--light-gray); }
        .filter-form { display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; }
        .form-group { flex: 1; min-width: 250px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--dark); }
        .form-control { width: 100%; padding: 12px 16px; border: 2px solid var(--light-gray); border-radius: var(--border-radius); font-size: 1rem; transition: var(--transition); }
        .form-control:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2); }
        .btn { padding: 12px 24px; border: none; border-radius: var(--border-radius); font-size: 1rem; font-weight: 600; cursor: pointer; transition: var(--transition); display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-2px); }
        .btn-outline { background: transparent; color: var(--gray); border: 2px solid var(--light-gray); }
        .btn-outline:hover { background: var(--light-gray); color: var(--dark); }
        .results-section { padding: 2rem; }
        .results-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .results-count { font-size: 1.1rem; color: var(--gray); }

        /* CARD STYLES */
        .card-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
        .provider-card { background: white; border: 1px solid var(--light-gray); border-radius: var(--border-radius); box-shadow: var(--shadow); padding: 25px; transition: var(--transition); position: relative; display: flex; flex-direction: column; }
        .provider-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1); }
        .card-header { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
        .company-avatar { width: 60px; height: 60px; border-radius: 50%; background: var(--primary); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.5rem; flex-shrink: 0; }
        
        /* MODIFIED: Wrapper for Name + Rating */
        .company-info {
            display: flex;
            flex-direction: column;
        }
        .company-name { font-size: 1.5rem; font-weight: 700; color: var(--primary-dark); }
        
        /* NEW: Rating Styles */
        .rating-stars {
            font-size: 0.9rem;
            color: var(--warning-star);
        }
        .rating-stars .rating-text {
            color: var(--gray);
            font-size: 0.9rem;
            margin-left: 8px;
            font-weight: 500;
        }
        .rating-stars .no-rating {
            font-style: italic;
            color: var(--gray);
        }
        /* END NEW STYLES */

        .card-body { border-top: 1px solid var(--light-gray); padding-top: 15px; flex-grow: 1; }
        .card-body p { margin-bottom: 0; }
        .company-address { color: var(--gray); font-size: 0.9rem; margin-bottom: 15px; }
        .company-address i { margin-right: 8px; color: var(--primary); }
        .service-badge { display: inline-block; padding: 6px 14px; background: rgba(67, 97, 238, 0.1); color: var(--primary); border-radius: 20px; font-size: 0.9rem; font-weight: 600; margin-bottom: 10px; }
        .provider-id { position: absolute; top: 20px; right: 20px; font-size: 0.9rem; color: var(--gray); background: var(--light-gray); padding: 4px 10px; border-radius: 20px; }
        .no-results { text-align: center; padding: 3rem; color: var(--gray); }

        /* NEW & MODIFIED STYLES */
        .status-badge { display: inline-block; padding: 6px 14px; border-radius: 20px; font-size: 0.9rem; font-weight: 600; margin-bottom: 15px; margin-left: 5px; }
        .status-badge.available { background: rgba(40, 167, 69, 0.1); color: var(--success); }
        .status-badge.unavailable { background: var(--light-gray); color: var(--gray); }
        .service-details p { display: flex; align-items: flex-start; margin-bottom: 10px; font-size: 0.95rem; }
        .service-details i { width: 20px; text-align: center; margin-right: 8px; color: var(--primary); padding-top: 3px; }
        .card-footer { border-top: 1px solid var(--light-gray); padding-top: 15px; margin-top: auto; text-align: right; }
        .btn-book-now { padding: 10px 20px; font-size: 0.9rem; text-decoration: none; display: inline-block; }
        .btn-disabled { background-color: var(--gray); color: white; cursor: not-allowed; }
        .btn-disabled:hover { background-color: var(--gray); transform: none; }

        footer { text-align: center; padding: 1.5rem; background: var(--light); color: var(--gray); border-top: 1px solid var(--light-gray); }
        @media (max-width: 768px) { .filter-form { flex-direction: column; } .form-group { min-width: 100%; } }
    </style>
</head>
<body>
<div class="container">
    <header>
        <h1><i class="fas fa-handshake"></i> Service Provider Directory</h1>
        <p>Find the perfect service providers for your needs</p>
    </header>
    <section class="filter-section">
        <form action="" method="GET" class="filter-form">
            <div class="form-group">
                <label for="service_name"><i class="fas fa-search"></i> Search by Service Name</label>
                <input type="text" id="service_name" name="service_name" class="form-control" placeholder="Enter service name..." value="<?php echo htmlspecialchars($_GET['service_name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="service_id"><i class="fas fa-filter"></i> Filter by Service ID</label>
                <input type="text" id="service_id" name="service_id" class="form-control" placeholder="Enter service ID..." value="<?php echo htmlspecialchars($_GET['service_id'] ?? ''); ?>">
            </div>
            <div class="btn-group">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply Filters</button>
                <a href="?" class="btn btn-outline"><i class="fas fa-redo"></i> Reset</a>
            </div>
        </form>
    </section>
    <section class="results-section">
        <div class="results-header">
            <h2>Service Providers</h2>
            <div class="results-count">
                <?php
                require '../db.php';
                
                // --- MODIFIED SQL QUERY ---
                // We use a LEFT JOIN to get rating data (average and count)
                // for each provider. We join on a subquery that groups ratings by provider.
                $sql = "SELECT 
                            sp.*, 
                            r.avg_rating, 
                            r.rating_count
                        FROM 
                            service_providers AS sp
                        LEFT JOIN 
                            (SELECT 
                                 provider_id, 
                                 AVG(rating) as avg_rating, 
                                 COUNT(id) as rating_count 
                             FROM 
                                 provider_ratings 
                             GROUP BY 
                                 provider_id
                            ) AS r ON sp.id = r.provider_id";
                // --- END MODIFIED SQL ---

                $params = [];
                $types = "";
                $where_clauses = ["sp.is_approved = 1"]; // Base condition: only show approved providers (aliased 'sp')

                if (isset($_GET['service_id']) && !empty($_GET['service_id'])) {
                    $where_clauses[] = "sp.service_id = ?"; // aliased 'sp'
                    $params[] = $_GET['service_id'];
                    $types .= "i";
                }
                if (isset($_GET['service_name']) && !empty($_GET['service_name'])) {
                    $service_name_input = '%' . $_GET['service_name'] . '%';
                    $where_clauses[] = "sp.service_name LIKE ?"; // aliased 'sp'
                    $params[] = $service_name_input;
                    $types .= "s";
                }
                
                $sql .= " WHERE " . implode(" AND ", $where_clauses);
                
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    if (!empty($params)) {
                        $stmt->bind_param($types, ...$params);
                    }
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $count = $result->num_rows;
                    echo "<span><i class='fas fa-list'></i> " . $count . " provider(s) found</span>";
                } else {
                    // Handle query preparation error
                    echo "<span>Error preparing query.</span>";
                    $result = false; // Ensure result is false so the next block doesn't run
                }
                ?>
            </div>
        </div>
        <?php
        if ($result && $result->num_rows > 0) {
            echo '<div class="card-container">';
            while($row = $result->fetch_assoc()) {
                $is_available = $row['is_available'] ?? 0;

                echo '<div class="provider-card">';
                echo '<div class="provider-id">ID: ' . htmlspecialchars($row['id']) . '</div>';
                
                // --- MODIFIED CARD HEADER ---
                echo '<div class="card-header">';
                echo '<div class="company-avatar">' . substr(htmlspecialchars($row['company_name']), 0, 1) . '</div>';
                
                // Wrapper for name and rating
                echo '<div class="company-info">'; 
                echo '<h3 class="company-name">' . htmlspecialchars($row['company_name']) . '</h3>';

                // --- NEW: RATING STARS LOGIC ---
                // These values come from the new SQL query
                $avg_rating = $row['avg_rating'] ?? 0;
                $rating_count = $row['rating_count'] ?? 0;

                echo '<div class="rating-stars">';
                if ($rating_count > 0) {
                    $full_stars = floor($avg_rating);
                    $half_star = ($avg_rating - $full_stars) >= 0.5 ? 1 : 0;
                    $empty_stars = 5 - $full_stars - $half_star;

                    // 1. Print Full Stars
                    for ($j = 0; $j < $full_stars; $j++) {
                        echo '<i class="fas fa-star"></i>';
                    }
                    // 2. Print Half Star
                    if ($half_star) {
                        echo '<i class="fas fa-star-half-alt"></i>';
                    }
                    // 3. Print Empty Stars
                    for ($j = 0; $j < $empty_stars; $j++) {
                        echo '<i class="far fa-star"></i>';
                    }
                    
                    // 4. Print Text (e.g., "4.5 (10 reviews)")
                    $review_text = ($rating_count == 1) ? 'review' : 'reviews';
                    echo '<span class="rating-text">' . number_format($avg_rating, 1) . ' (' . $rating_count . ' ' . $review_text . ')</span>';

                } else {
                    // Case for no ratings
                    echo '<span class="rating-text no-rating">No ratings yet</span>';
                }
                echo '</div>'; // end .rating-stars
                // --- END RATING LOGIC ---

                echo '</div>'; // end .company-info
                echo '</div>'; // end .card-header
                // --- END MODIFIED CARD HEADER ---

                echo '<div class="card-body">';
                echo '<span class="service-badge">' . htmlspecialchars($row['service_name']) . '</span>';
                
                if ($is_available) {
                    echo '<span class="status-badge available"><i class="fas fa-check-circle"></i> Available</span>';
                } else {
                    echo '<span class="status-badge unavailable"><i class="fas fa-times-circle"></i> Not Available</span>';
                }
                
                echo '<p class="company-address"><i class="fas fa-map-marker-alt"></i> ' . htmlspecialchars($row['company_address']) . '</p>';
                echo '<div class="service-details">';
                echo '<p><i class="fas fa-info-circle"></i><span>' . htmlspecialchars($row['service_description']) . '</span></p>';
                echo '<hr style="margin: 15px 0; border: 0; border-top: 1px solid var(--light-gray);">';
                
                echo '<p><i class="fas fa-tag"></i><span><strong>Price:</strong> ₱' . number_format($row['price'], 2) . '</span></p>';
                echo '<p><i class="fas fa-calendar-alt"></i><span><strong>Dates:</strong> ' . date("M j, Y", strtotime($row['available_date_from'])) . ' to ' . date("M j, Y", strtotime($row['available_date_to'])) . '</span></p>';
                echo '<p><i class="fas fa-clock"></i><span><strong>Time:</strong> ' . date("g:i A", strtotime($row['available_time_from'])) . ' to ' . date("g:i A", strtotime($row['available_time_to'])) . '</span></p>';
                echo '</div>'; 
                
                echo '</div>'; 
                echo '<div class="card-footer">';
                
                if ($is_available) {
                    echo '<a href="book_service.php?provider_id=' . htmlspecialchars($row['id']) . '" class="btn btn-primary btn-book-now">';
                    echo '<i class="fas fa-calendar-check"></i> Book Now';
                    echo '</a>';
                } else {
                    echo '<button class="btn btn-disabled btn-book-now" disabled>';
                    echo '<i class="fas fa-calendar-times"></i> Unavailable';
                    echo '</button>';
                }
                
                echo '</div>';
                echo '</div>';
            }
            echo '</div>';
        } else {
            echo '<div class="no-results">';
            echo '<i class="fas fa-search"></i>';
            echo '<h3>No service providers found</h3>';
            echo '<p>Try adjusting your search criteria or browse all providers.</p>';
            echo '</div>';
        }
        if (isset($stmt)) {
            $stmt->close();
        }
        $conn->close();
        ?>
    </section>
    <footer>
        <p>&copy; <?php echo date("Y"); ?> Service Provider Directory. All rights reserved.</p>
    </footer>
</div>
</body>
</html>