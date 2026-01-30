<?php
session_start();
require "../config/db.php";
require "../algorithms/tripRecommendation.php";
require "../algorithms/placeRecommendation.php";
require "../algorithms/companionRecommendation.php";

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

$recommendedTrips = getPersonalizedTripRecommendations($conn, $user_id, '', 8, 40);
$recommendedCompanions = getCompanionRecommendations($conn, $user_id, 6, 30);
$recommendedPlaces = recommendPlacesForUser($conn, $user_id, 8);


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recommendations - TCFS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', sans-serif;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
            min-height: 100vh;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .page-subtitle {
            color: #666;
            font-size: 14px;
        }

        .section-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #57C785;
        }

        .recommendation-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .recommendation-card {
            background: #fff;
            border: 2px solid #f0f0f0;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s ease;
            position: relative;
        }

        .recommendation-card:hover {
            border-color: #57C785;
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(234,88,12,0.15);
        }

        .match-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, #2A7B9B 0%, #57C785 50%, #EDDD53 100%);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            padding-right: 80px;
        }

        .card-meta {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 15px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #666;
        }

        .meta-item i {
            color: #57C785;
            font-size: 14px;
        }

        .card-action {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-view {
            background: #57C785;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex: 1;
            justify-content: center;
            min-width: 120px;
        }

        .btn-view:hover {
            background: #2A7B9B;
            color: white;
        }

        .btn-apply {
            background: #2A7B9B;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex: 1;
            justify-content: center;
            min-width: 120px;
        }

        .btn-apply:hover {
            background: #1a5f7a;
            color: white;
        }

        .companion-card {
            display: flex;
            gap: 15px;
            align-items: start;
        }

        .companion-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2A7B9B 0%, #57C785 50%, #EDDD53 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .companion-info {
            flex: 1;
        }

        .place-card {
            text-align: center;
        }

        .place-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 15px;
            background: linear-gradient(135deg, #E8F4F8, #D4EDE1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
        }

        .reason-badge {
            background: #E8F4F8;
            color: #57C785;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            margin-top: 10px;
        }

        .interests-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 10px;
        }

        .interest-tag {
            background: #E8F4F8;
            color: #57C785;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .recommendation-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">
                <i class="bi bi-lightbulb"></i> Smart Recommendations
            </h1>
            <p class="page-subtitle">Discover trips, companions, and places tailored just for you</p>
        </div>

        <div class="section-card">
            <h2 class="section-title">
                <i class="bi bi-airplane"></i> Recommended Trips
            </h2>
            
            <?php if (!empty($recommendedTrips)): ?>
                <div class="recommendation-grid">
                    <?php foreach ($recommendedTrips as $trip): 
                        $score = isset($trip['compatibility_score']) ? round($trip['compatibility_score']) : 0;
                    ?>
                        <div class="recommendation-card">
                            <span class="match-badge"><?php echo $score; ?>% Match</span>
                            <h3 class="card-title"><?php echo htmlspecialchars($trip['trip_name']); ?></h3>
                            <div class="card-meta">
                                <div class="meta-item">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <span><?php echo htmlspecialchars($trip['destination']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="bi bi-calendar"></i>
                                    <span><?php echo date('M d', strtotime($trip['start_date'])); ?> - <?php echo date('M d, Y', strtotime($trip['end_date'])); ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="bi bi-person"></i>
                                    <span><?php echo htmlspecialchars($trip['host_name']); ?></span>
                                </div>
                            </div>
                            <div class="card-action">
                                <a href="viewTrip.php?id=<?php echo $trip['id']; ?>" class="btn-view">
                                    <i class="bi bi-eye"></i> View Details
                                </a>
                                <a href="applyTrip.php?trip_id=<?php echo $trip['id']; ?>" class="btn-apply">
                                    <i class="bi bi-send-fill"></i> Apply
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p>No trip recommendations available. Create your first trip to get started!</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="section-card">
            <h2 class="section-title">
                <i class="bi bi-people"></i> Compatible Companions
            </h2>
            
            <?php if (!empty($recommendedCompanions)): ?>
                <div class="recommendation-grid">
                    <?php foreach ($recommendedCompanions as $companion): ?>
                        <div class="recommendation-card companion-card">
                            <div class="companion-avatar">
                                <?php echo strtoupper(substr($companion['name'], 0, 1)); ?>
                            </div>
                            <div class="companion-info">
                                <span class="match-badge"><?php echo round($companion['compatibility_score']); ?>% Match</span>
                                <h3 class="card-title"><?php echo htmlspecialchars($companion['name']); ?></h3>
                                <?php if (!empty($companion['common_interests'])): ?>
                                    <div class="interests-tags">
                                        <?php foreach (array_slice($companion['common_interests'], 0, 3) as $interest): ?>
                                            <span class="interest-tag"><?php echo htmlspecialchars($interest); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="card-action">
                                    <a href="viewProfile.php?host_id=<?php echo $companion['id']; ?>" class="btn-view">
                                        <i class="bi bi-person"></i> View Profile
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p>No companion recommendations available. Create a trip to find compatible travelers!</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="section-card">
            <h2 class="section-title">
                <i class="bi bi-compass"></i> Discover New Places
            </h2>
            
            <?php if (!empty($recommendedPlaces)): ?>
                <div class="recommendation-grid">
                    <?php foreach ($recommendedPlaces as $place): ?>
                        <div class="recommendation-card place-card">
                            <div class="place-icon">
                                <i class="bi bi-pin-map-fill" style="color: #57C785;"></i>
                            </div>
                            <h3 class="card-title" style="justify-content: center; margin-left: 80px;"><?php echo htmlspecialchars($place['destination']); ?></h3>
                            <!--<div class="meta-item" style="justify-content: center;">
                                <i class="bi bi-map"></i>
                                <span><?php echo htmlspecialchars($place['region']); ?></span>
                            </div>-->
                            <span class="reason-badge"><?php echo htmlspecialchars($place['reason']); ?></span>
                            <?php if (!empty($place['compatible_users'])): ?>
                                <div class="meta-item" style="justify-content: center; margin-top: 10px;">
                                    <small>Compatible travelers: <?php echo implode(', ', array_slice($place['compatible_users'], 0, 2)); ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p>No place recommendations yet. Start traveling to discover new destinations!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
