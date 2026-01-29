<?php
session_start();
require "../config/db.php";
require "../algorithms/weightedMatch.php";

if (!isset($_SESSION['user_id']) || !isset($_GET['trip_id'])) {
    header('Location: discoverTrips.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$trip_id = (int)$_GET['trip_id'];

$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

$trip_stmt = $conn->prepare("SELECT * FROM trips WHERE id = ?");
$trip_stmt->bind_param("i", $trip_id);
$trip_stmt->execute();
$trip = $trip_stmt->get_result()->fetch_assoc();

if (!$trip || $trip['host_id'] == $user_id) {
    header('Location: discoverTrips.php');
    exit;
}

$check_stmt = $conn->prepare("SELECT id FROM trip_applications WHERE trip_id = ? AND user_id = ?");
$check_stmt->bind_param("ii", $trip_id, $user_id);
$check_stmt->execute();
if ($check_stmt->get_result()->num_rows > 0) {
    header('Location: tripApplications.php?applied=1');
    exit;
}

$score = calculateTripCompatibility($user, $trip);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $apply_stmt = $conn->prepare("INSERT INTO trip_applications(trip_id, user_id, compatibility_score, status) VALUES(?, ?, ?, 'pending')");
    $apply_stmt->bind_param("iii", $trip_id, $user_id, $score);
    if ($apply_stmt->execute()) {
        header('Location: tripApplications.php?success=1');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply Trip - TCFS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', sans-serif;
            padding: 30px 0;
        }
        .main-content {
            margin-left: 250px; 
            padding: 40px;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        .content-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
        }

        .trip-preview {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #e5e5e5;
        }

        .trip-title {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .trip-meta {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin: 20px 0;
        }

        .meta-item {
            padding: 15px;
            background: #f5f7fa;
            border-radius: 8px;
        }

        .meta-label {
            font-size: 12px;
            color: #999;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .meta-value {
            font-size: 16px;
            font-weight: 700;
            color: #333;
        }

        .score-section {
            background: linear-gradient(135deg, #2A7B9B 0%, #57C785 50%, #EDDD53 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            margin: 30px 0;
        }

        .score-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .score-value {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .score-text {
            font-size: 14px;
        }

        .score-breakdown {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .score-item {
            background: #f5f7fa;
            padding: 15px;
            border-radius: 8px;
        }

        .score-item-label {
            font-size: 12px;
            color: #999;
            margin-bottom: 5px;
        }

        .score-item-bar {
            width: 100%;
            height: 6px;
            background: #ddd;
            border-radius: 3px;
            overflow: hidden;
        }

        .score-item-fill {
            height: 100%;
            background: linear-gradient(135deg, #2A7B9B 0%, #57C785 50%, #EDDD53 100%);
            transition: width 0.3s;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn-apply {
            flex: 1;
            background: linear-gradient(135deg, #2A7B9B 0%, #57C785 50%, #EDDD53 100%);
            color: white;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 16px;
        }

        .btn-apply:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-cancel {
            flex: 1;
            background: white;
            color: #57C785;
            padding: 15px;
            border: 2px solid #57C785;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
        }

        .btn-cancel:hover {
            background: #f5f7fa;
            color: #57C785;
        }

        @media (max-width: 768px) {
            .trip-meta, .score-breakdown {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
    <div class="container">
        <a href="discoverTrips.php" class="btn btn-outline-secondary mb-4"><i class="fas fa-arrow-left"></i> Back</a>

        <div class="content-card">
            <div class="trip-preview">
                <h2 class="trip-title"><?php echo htmlspecialchars($trip['trip_name']); ?></h2>
                <p style="color: #999; margin: 10px 0;"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($trip['destination']); ?>, <?php echo htmlspecialchars($trip['region']); ?></p>

                <div class="trip-meta">
                    <div class="meta-item">
                        <div class="meta-label">Dates</div>
                        <div class="meta-value"><?php echo date('M d - d', strtotime($trip['start_date'])) . ' ' . date('Y', strtotime($trip['end_date'])); ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Budget</div>
                        <div class="meta-value">Rs.<?php echo number_format($trip['budget_min']); ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Group Size</div>
                        <div class="meta-value"><?php echo $trip['group_size_min']; ?>+ people</div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Travel Mode</div>
                        <div class="meta-value"><?php echo htmlspecialchars($trip['travel_mode']); ?></div>
                    </div>
                </div>

                <p style="color: #666; line-height: 1.6; margin-top: 20px;"><?php echo nl2br(htmlspecialchars($trip['description'])); ?></p>
            </div>

            <div class="score-section">
                <div class="score-label">YOUR COMPATIBILITY SCORE</div>
                <div class="score-value"><?php echo $score; ?>%</div>
                <div class="score-text">
                    <?php 
                    if ($score >= 80) echo "Excellent match! You're a great fit for this trip.";
                    elseif ($score >= 60) echo "Good match. You share many interests with this trip.";
                    elseif ($score >= 40) echo "Fair match. Some interests align with this trip.";
                    else echo "Low match. But you can still apply and meet new people!";
                    ?>
                </div>
            </div>

            <h5 class="mb-3">How Your Profile Matches</h5>
            <div class="score-breakdown">
                <div class="score-item">
                    <div class="score-item-label">Date Availability</div>
                    <div class="score-item-bar">
                        <div class="score-item-fill" style="width: <?php echo min(100, calculateDateOverlapScore($user['available_from'], $user['available_to'], $trip['start_date'], $trip['end_date'])); ?>%"></div>
                    </div>
                </div>
                <div class="score-item">
                    <div class="score-item-label">Trip Style Match</div>
                    <div class="score-item-bar">
                        <div class="score-item-fill" style="width: <?php echo min(100, calculateTripStyleScore($user['interests'] ?? '', $trip['trip_style'] ?? '')); ?>%"></div>
                    </div>
                </div>
                <div class="score-item">
                    <div class="score-item-label">Budget Range</div>
                    <div class="score-item-bar">
                        <div class="score-item-fill" style="width: <?php echo min(100, calculateBudgetOverlapScore($user['budget'] ?? 0, $trip['budget_min'] ?? 0, $trip['budget_max'] ?? 0)); ?>%"></div>
                    </div>
                </div>
                <div class="score-item">
                    <div class="score-item-label">Travel Mode</div>
                    <div class="score-item-bar">
                        <div class="score-item-fill" style="width: <?php echo min(100, calculateTravelModeScore($user['travel_mode'] ?? '', $trip['travel_mode'] ?? '')); ?>%"></div>
                    </div>
                </div>
            </div>

            <form method="post">
                <div class="action-buttons">
                    <button type="submit" class="btn-apply"><i class="fas fa-check-circle"></i> Confirm & Apply</button>
                    <a href="discoverTrips.php" class="btn-cancel"><i class="fas fa-times-circle"></i> Cancel</a>
                </div>
            </form>
        </div>
    </div>
    </div>
</body>

</html>
