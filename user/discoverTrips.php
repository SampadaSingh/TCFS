<?php
session_start();
require "../config/db.php";
require "../algorithms/weightedMatch.php";

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$filter_destination = $_GET['destination'] ?? '';
$filter_budget = $_GET['budget'] ?? '';

$query = "SELECT t.*, 
                (SELECT COUNT(*) FROM trip_applications WHERE trip_id = t.id AND status = 'accepted') as accepted_count
          FROM trips t 
          WHERE t.host_id != ? AND t.status IN ('pending', 'confirmed') AND t.start_date >= CURDATE()";
$params = [$user_id];
$types = "i";

if ($filter_destination) {
    $query .= " AND t.destination LIKE ?";
    $search = "%$filter_destination%";
    $params = array_merge($params, [$search]);
    $types .= "s";
}

if ($filter_budget) {
    if (strpos($filter_budget, '+') !== false) {
        $min = (int)str_replace([',', '+'], '', $filter_budget);
        $query .= " AND t.budget_min >= ?";
        $params = array_merge($params, [$min]);
        $types .= "i";
    } else {
        list($min, $max) = explode('-', $filter_budget);
        $min = (int)str_replace(',', '', $min);
        $max = (int)str_replace(',', '', $max);
        $query .= " AND ((t.budget_min >= ? AND t.budget_min <= ?) OR (t.budget_max >= ? AND t.budget_max <= ?) OR (t.budget_min <= ? AND t.budget_max >= ?))";
        $params = array_merge($params, [$min, $max, $min, $max, $min, $max]);
        $types .= "iiiiii";
    }
}

$query .= " ORDER BY t.start_date ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$trips = $stmt->get_result();

// Fetch all trips into array and get trip IDs
$trip_ids = [];
$trips_array = [];
while ($trip = $trips->fetch_assoc()) {
    $trips_array[] = $trip;
    $trip_ids[] = $trip['id'];
}

// Fetch all user applications in one query to avoid N+1 problem
$user_applications = [];
if (!empty($trip_ids)) {
    $placeholders = implode(',', array_fill(0, count($trip_ids), '?'));
    $app_query = "SELECT trip_id FROM trip_applications WHERE user_id = ? AND trip_id IN ($placeholders)";
    $app_stmt = $conn->prepare($app_query);
    $app_params = array_merge([$user_id], $trip_ids);
    $app_stmt->bind_param(str_repeat('i', count($app_params)), ...$app_params);
    $app_stmt->execute();
    $app_result = $app_stmt->get_result();
    while ($row = $app_result->fetch_assoc()) {
        $user_applications[$row['trip_id']] = true;
    }
}

// Get current user details for compatibility scoring
$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

foreach ($trips_array as &$trip) {
    $trip['compatibility_score'] = calculateTripCompatibility($user, $trip);
}
unset($trip);

usort($trips_array, function ($a, $b) {
    return $b['compatibility_score'] <=> $a['compatibility_score'];
});
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discover Trips - TCFS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #E8F4F8;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }

        .main-content {
            margin-left: 250px;
            padding: 40px 30px;
            min-height: 100vh;
        }

        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .filter-input {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 10px 15px;
        }

        .filter-input:focus {
            border-color: #57C785;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .trip-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .trip-card:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
        }

        .trip-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .trip-title {
            font-size: 18px;
            font-weight: 700;
            color: #333;
        }

        .trip-score {
            background: linear-gradient(135deg, #2A7B9B 0%, #57C785 50%, #EDDD53 100%);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .trip-destination {
            color: #999;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .trip-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 15px;
            font-size: 13px;
            color: #666;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .detail-icon {
            color: #57C785;
            width: 16px;
            text-align: center;
        }

        .trip-description {
            color: #777;
            font-size: 14px;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .applicants-section {
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e5e5e5;
        }

        .applicants-label {
            font-size: 12px;
            color: #999;
            margin: 0 0 8px 0;
            font-weight: 600;
        }

        .applicants-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .applicant-badge {
            font-size: 11px;
            background: #E8F4F8;
            padding: 4px 8px;
            border-radius: 4px;
            color: #333;
        }

        .applicant-more {
            font-size: 11px;
            color: #999;
        }

        .trip-footer {
            padding-top: 15px;
            border-top: 1px solid #e5e5e5;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
        }

        .footer-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .group-progress {
            font-size: 12px;
            color: #999;
        }

        .btn-apply {
            background: #57C785;
            color: white;
            padding: 8px 20px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .btn-view {
            background: #fff;
            color: #2A7B9B;
            padding: 8px 14px;
            border-radius: 6px;
            border: 1px solid #2A7B9B;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .btn-apply:hover:not(:disabled) {
            background: #2A7B9B;
            color: white;
        }

        .btn-view:hover {
            background: #2A7B9B;
            color: #fff;
        }

        .btn-apply:disabled {
            background: #ccc !important;
            color: #666 !important;
            border: 2px solid #ccc !important;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
        }

        .empty-icon {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .trip-details {
                grid-template-columns: 1fr;
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h2 class="fw-bold mb-4">Discover Trips</h2>

        <div class="filter-section">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="destination" class="filter-input w-100" placeholder="Search destination..." value="<?php echo htmlspecialchars($filter_destination); ?>">
                </div>
                <div class="col-md-4">
                    <select name="budget" class="filter-input w-100">
                        <option value="">All Budgets</option>
                        <option value="1000-5000" <?php echo $filter_budget === '1000-5000' ? 'selected' : ''; ?>>NRS 1,000–5,000</option>
                        <option value="5000-10000" <?php echo $filter_budget === '5000-10000' ? 'selected' : ''; ?>>NRS 5,000–10,000</option>
                        <option value="10000-15000" <?php echo $filter_budget === '10000-15000' ? 'selected' : ''; ?>>NRS 10,000–15,000</option>
                        <option value="15000-25000" <?php echo $filter_budget === '15000-25000' ? 'selected' : ''; ?>>NRS 15,000–25,000</option>
                        <option value="25000-40000" <?php echo $filter_budget === '25000-40000' ? 'selected' : ''; ?>>NRS 25,000–40,000</option>
                        <option value="40000-60000" <?php echo $filter_budget === '40000-60000' ? 'selected' : ''; ?>>NRS 40,000–60,000</option>
                        <option value="60000-100000" <?php echo $filter_budget === '60000-100000' ? 'selected' : ''; ?>>NRS 60,000–100,000</option>
                        <option value="100000+" <?php echo $filter_budget === '100000+' ? 'selected' : ''; ?>>NRS 100,000+</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-apply w-100"><i class="fas fa-search"></i> Search</button>
                </div>
            </form>
        </div>

        <?php if (empty($trips_array)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-search"></i></div>
                <h4>No trips found</h4>
                <p class="text-muted">Try adjusting your filters</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($trips_array as $trip):
                    $trip_id = $trip['id'];
                    $hasApplied = isset($user_applications[$trip_id]);
                    $isFull = ($trip['accepted_count'] ?? 0) >= ($trip['group_size_min'] ?? 0);
                    $isPast = strtotime($trip['start_date']) < strtotime('today');
                    $score = $trip['compatibility_score'];
                ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="trip-card">
                            <?php
                            $imagePath = !empty($trip['trip_image']) 
                                ? '../assets/img/' . htmlspecialchars($trip['trip_image'])
                                : 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="400" height="150"%3E%3Crect fill="%23E8F4F8" width="400" height="150"/%3E%3Ctext x="50%25" y="50%25" font-family="Arial" font-size="14" fill="%232A7B9B" text-anchor="middle" dy=".3em"%3ENo Trip Image%3C/text%3E%3C/svg%3E';
                            ?>
                            <img
                                src="<?= $imagePath ?>"
                                alt="Trip Image"
                                style="width: 100%; height: 150px; object-fit: cover; border-radius: 8px; margin-bottom: 15px;"
                                onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22150%22%3E%3Crect fill=%22%23E8F4F8%22 width=%22400%22 height=%22150%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-family=%22Arial%22 font-size=%2214%22 fill=%22%232A7B9B%22 text-anchor=%22middle%22 dy=%22.3em%22%3EImage Not Found%3C/text%3E%3C/svg%3E'">

                            <div class="trip-body">
                                <div class="trip-header">
                                    <div>
                                        <h5 class="trip-title"><?php echo htmlspecialchars($trip['trip_name']); ?></h5>
                                        <p class="trip-destination"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($trip['destination']); ?></p>
                                    </div>
                                    <span class="trip-score"><?php echo $score; ?>%</span>
                                </div>
                                <div class="trip-details">
                                    <div class="detail-item">
                                        <span class="detail-icon"><i class="fas fa-calendar-alt"></i></span>
                                        <span><?php echo date('M d', strtotime($trip['start_date'])); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-icon"><i class="fas fa-dollar-sign"></i></span>
                                        <span>Rs.<?php echo number_format($trip['budget_min'] ?? 0); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-icon"><i class="fas fa-users"></i></span>
                                        <span><?php echo $trip['group_size_min'] ?? 0; ?> people</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-icon"><i class="fas fa-car"></i></span>
                                        <span><?php echo htmlspecialchars($trip['travel_mode'] ?? 'Not specified'); ?></span>
                                    </div>
                                </div>
                                <!--<p class="trip-description">
                                    <?php /*echo substr(htmlspecialchars($trip['description'] ?? ''), 0, 100) . '...'; */ ?>
                                </p>-->

                                <?php if ($trip['host_id'] == $user_id): ?>
                                    <div class="applicants-section">
                                        <p class="applicants-label">Applied by:</p>
                                        <?php
                                        $applicants_query = "SELECT u.id, u.name FROM trip_applications ta 
                                                        JOIN users u ON ta.user_id = u.id 
                                                        WHERE ta.trip_id = ? AND ta.status = 'accepted' LIMIT 5";
                                        $applicants_stmt = $conn->prepare($applicants_query);
                                        $applicants_stmt->bind_param("i", $trip_id);
                                        $applicants_stmt->execute();
                                        $applicants_result = $applicants_stmt->get_result();

                                        if ($applicants_result->num_rows > 0) {
                                            echo '<div class="applicants-list">';
                                            while ($applicant = $applicants_result->fetch_assoc()) {
                                                echo '<span class="applicant-badge">' . htmlspecialchars($applicant['name']) . '</span>';
                                            }
                                            if ($applicants_result->num_rows >= 5 && ($trip['accepted_count'] ?? 0) > 5) {
                                                echo '<span class="applicant-more">+' . (($trip['accepted_count'] ?? 0) - 5) . ' more</span>';
                                            }
                                            echo '</div>';
                                        } else {
                                            echo '<p style="font-size: 12px; color: #999; margin: 0;">No applicants yet</p>';
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>

                                <div class="trip-footer">
                                    <span class="group-progress">
                                        <?php echo ($trip['accepted_count'] ?? 0) . '/' . ($trip['group_size_min'] ?? 0); ?> joined
                                    </span>

                                    <div class="footer-actions">
                                        <a href="viewTrip.php?trip_id=<?php echo $trip['id']; ?>" class="btn-view" title="View trip details">
                                            <i class="fas fa-eye"></i> View
                                        </a>

                                        <?php if ($isPast): ?>
                                            <button class="btn-apply" disabled title="Trip date has passed">
                                                <i class="fas fa-times"></i> Trip Ended
                                            </button>
                                        <?php elseif ($isFull): ?>
                                            <button class="btn-apply" disabled title="This trip is full">
                                                <i class="fas fa-ban"></i> Trip Full
                                            </button>
                                        <?php elseif ($hasApplied): ?>
                                            <button class="btn-apply" disabled title="You have already applied">
                                                <i class="fas fa-check"></i> Applied
                                            </button>
                                        <?php else: ?>
                                            <a href="applyTrip.php?trip_id=<?php echo $trip['id']; ?>" class="btn-apply" title="Apply for this trip">
                                                <i class="fas fa-check"></i> Apply
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>