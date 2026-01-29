<?php
session_start();
require "../config/db.php";

if (!isset($_GET['trip_id'])) {
    header('Location: discoverTrips.php');
    exit;
}

$trip_id = (int)$_GET['trip_id'];

$trip_stmt = $conn->prepare("SELECT * FROM trips WHERE id = ?");
$trip_stmt->bind_param("i", $trip_id);
$trip_stmt->execute();
$trip = $trip_stmt->get_result()->fetch_assoc();

if (!$trip) {
    header('Location: discoverTrips.php');
    exit;
}

$host_stmt = $conn->prepare("SELECT id, name FROM users WHERE id = ?");
$host_stmt->bind_param("i", $trip['host_id']);
$host_stmt->execute();
$host = $host_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($trip['trip_name']); ?> - TCFS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #E8F4F8;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-content {
            margin-left: 250px;
            padding: 40px;
            min-height: 100vh;
        }

        .trip-header {
            background: linear-gradient(135deg, #2A7B9B 0%, #57C785 50%, #EDDD53 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }

        .trip-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .trip-subtitle {
            opacity: 0.9;
            font-size: 16px;
        }

        .info-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .info-card h5 {
            font-weight: 700;
            margin-bottom: 20px;
            color: #333;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .info-item {
            padding: 15px;
            background: #f5f7fa;
            border-radius: 8px;
        }

        .info-label {
            font-size: 12px;
            color: #999;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .info-value {
            font-size: 16px;
            font-weight: 700;
            color: #333;
        }

        .description {
            line-height: 1.8;
            color: #666;
            white-space: pre-wrap;
        }

        .host-card {
            background: #f5f7fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .host-name {
            font-weight: 700;
            color: #333;
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .trip-title {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="trip-header">
            <div class="container">
                <a href="javascript:history.back()" style="color: white; text-decoration: none;"><i class="fas fa-arrow-left"></i> Back</a>
                <h1 class="trip-title"><?php echo htmlspecialchars($trip['trip_name']); ?></h1>
                <p class="trip-subtitle"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($trip['destination']); ?></p>
            </div>
        </div>

        <div class="container">
            <div class="info-card">
                <h5>Trip Details</h5>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Start Date</div>
                        <div class="info-value"><?php echo date('M d, Y', strtotime($trip['start_date'])); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">End Date</div>
                        <div class="info-value"><?php echo date('M d, Y', strtotime($trip['end_date'])); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Duration</div>
                        <div class="info-value"><?php echo htmlspecialchars($trip['duration_days']); ?> days</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Budget Range</div>
                        <div class="info-value">Rs.<?php echo number_format($trip['budget_min']); ?> - Rs.<?php echo number_format($trip['budget_max']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Travel Mode</div>
                        <div class="info-value"><?php echo htmlspecialchars($trip['travel_mode']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Trip Style</div>
                        <div class="info-value"><?php echo htmlspecialchars($trip['trip_style']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Starting Place</div>
                        <div class="info-value"><?php echo htmlspecialchars($trip['start_place']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Ending Place</div>
                        <div class="info-value"><?php echo htmlspecialchars($trip['end_place']); ?></div>
                    </div>

                </div>
            </div>

            <div class="info-card">
                <h5>About This Trip</h5>
                <p class="description"><?php echo htmlspecialchars($trip['description']); ?></p>
            </div>

            <div class="info-card">
                <h5>Collaborators</h5>
                <div style="display: flex; gap: 20px; flex-wrap: wrap; align-items: flex-start; margin-top: 10px;">


                    <?php if (!empty($trip['host_id'])):
                        $host_stmt=$conn->prepare("SELECT id, name FROM users WHERE id = ?");
                        $host_stmt->bind_param("i", $trip['host_id']);
                        $host_stmt->execute();
                        $host = $host_stmt->get_result()->fetch_assoc();
                        if ($host):
                    ?>
                    <a href="viewProfile.php?host_id=<?php echo $host['id']; ?>" style="text-decoration: none;">
                    <div class="host-card" style="width: 200px; text-align: center;">
                        <i class="fas fa-user" style="font-size: 30px; color: #57C785; margin-bottom: 10px; display: block;"></i>
                        <div class="host-name"><?php echo htmlspecialchars($host['name']); ?></div>
                        <p style="color: #999; margin: 5px 0; font-size: 14px;">Trip Host</p>
                    </div>
                    </a>    
                    <?php endif;
                    endif; ?>

                    <?php if (!empty($trip['collaborator_id'])):
                        $collab_stmt = $conn->prepare("SELECT id, name FROM users WHERE id = ?");
                        $collab_stmt->bind_param("i", $trip['collaborator_id']);
                        $collab_stmt->execute();
                        $collab = $collab_stmt->get_result()->fetch_assoc();
                        $collab_stmt->close();
                        if ($collab):
                    ?>
                            <a href="viewProfile.php?collaborator_id=<?php echo $collab['id']; ?>" style="text-decoration: none;">
                                <div class="host-card" style="width: 200px; text-align: center; cursor: pointer;">
                                    <i class="fas fa-user-gear" style="font-size: 26px; color: #FFB547; margin-bottom: 10px; display: block;"></i>
                                    <div class="host-name"><?php echo htmlspecialchars($collab['name']); ?></div>
                                    <p style="color: #999; margin: 5px 0; font-size: 13px;">Volunteer</p>
                                </div>
                            </a>
                    <?php endif;
                    endif; ?>

                </div>
            </div>


        </div>
    </div>
</body>

</html>