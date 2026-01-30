<?php
session_start();
require "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT t.*,
           CASE 
               WHEN t.host_id = ? THEN 'host'
               WHEN cr.status = 'accepted' THEN 'collaborator'
           END as user_role
    FROM trips t
    LEFT JOIN collaborator_requests cr ON t.id = cr.trip_id AND cr.collaborator_id = ? AND cr.status = 'accepted'
    WHERE t.host_id = ? OR (cr.collaborator_id = ? AND cr.status = 'accepted')
    ORDER BY t.start_date DESC
");
$stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$trips = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Trips - TCFS</title>
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

        .header {
            background: white;
            padding: 30px 0;
            border-bottom: 1px solid #e5e5e5;
        }

        .trip-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
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
            font-size: 20px;
            font-weight: 700;
            color: #333;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .role-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 10px;
            display: inline-block;
        }

        .role-host {
            background: #57C785;
            color: white;
        }

        .role-collaborator {
            background: #2A7B9B;
            color: white;
        }

        .trip-info {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #e5e5e5;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #666;
            font-size: 14px;
        }

        .info-icon {
            color: #57C785;
            font-size: 16px;
            width: 20px;
        }

        .trip-actions {
            display: flex;
            gap: 10px;
        }

        .btn-small {
            padding: 8px 15px;
            font-size: 13px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-view {
            background: #57C785;
            color: white;
        }

        .btn-view:hover {
            background: #2A7B9B;
            color: white;
        }

        .btn-edit {
            background: #f5f7fa;
            color: #57C785;
            border: 1px solid #57C785;
        }

        .btn-edit:hover {
            background: #57C785;
            color: white;
        }

        .btn-applicants {
            background: #f5f7fa;
            color: #57C785;
            border: 1px solid #57C785;
        }

        .btn-applicants:hover {
            background: #57C785;
            color: white;
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

        .empty-text {
            color: #999;
            margin-bottom: 20px;
        }

        .btn-create {
            background: linear-gradient(135deg, #2A7B9B 0%, #57C785 50%, #EDDD53 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }

        .btn-create:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        @media (max-width: 768px) {
            .trip-info {
                grid-template-columns: 1fr;
            }

            .trip-actions {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="header">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="fw-bold mb-0">My Trips</h2>
                    <a href="createTrip.php" class="btn btn-create"><i class="fas fa-plus"></i> Create Trip</a>
                </div>
            </div>
        </div>

        <div class="container mt-4 mb-5">
            <?php if ($trips->num_rows === 0): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-mountain"></i></div>
                    <h4 class="empty-text">No trips yet</h4>
                    <p class="text-muted mb-4">Start your journey by creating your first trip</p>
                    <a href="createTrip.php" class="btn btn-create"><i class="fas fa-plus"></i> Create Trip</a>
                </div>
            <?php else: ?>
                <?php while ($trip = $trips->fetch_assoc()): ?>
                    <?php
                    $stmt_count = $conn->prepare("SELECT COUNT(DISTINCT user_id) AS accepted_count FROM trip_applications WHERE trip_id = ? AND status = 'accepted'");
                    $stmt_count->bind_param("i", $trip['id']);
                    $stmt_count->execute();
                    $accepted_count_result = $stmt_count->get_result()->fetch_assoc();
                    $accepted_count = $accepted_count_result['accepted_count'] ?? 0;
                    ?>
                    <div class="trip-card">
                        <div class="trip-header">
                            <div>
                                <h4 class="trip-title">
                                    <?php echo htmlspecialchars($trip['trip_name']); ?>
                                    <?php if (isset($trip['user_role'])): ?>
                                        <span class="role-badge role-<?php echo $trip['user_role']; ?>">
                                            <?php echo ucfirst($trip['user_role']); ?>
                                        </span>
                                    <?php endif; ?>
                                </h4>
                                <p style="color: #999; margin: 5px 0; font-size: 14px;"><?php echo htmlspecialchars($trip['destination']); ?></p>
                            </div>
                            <span class="status-badge status-<?php echo strtolower($trip['status']); ?>">
                                <?php echo ucfirst($trip['status']); ?>
                            </span>
                        </div>

                        <div class="trip-info">
                            <div class="info-item">
                                <span class="info-icon"><i class="fas fa-calendar"></i></span>
                                <div>
                                    <small style="color: #999;">Dates</small><br>
                                    <?php echo date('M d, Y', strtotime($trip['start_date'])) . ' - ' . date('M d, Y', strtotime($trip['end_date'])); ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <span class="info-icon"><i class="fas fa-dollar-sign"></i></span>
                                <div>
                                    <small style="color: #999;">Budget</small><br>
                                    Rs.<?php echo number_format($trip['budget_min']); ?> - Rs.<?php echo number_format($trip['budget_max']); ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <span class="info-icon"><i class="fas fa-users"></i></span>
                                <div>
                                    <small style="color: #999;">Group Size</small><br>
                                    <?php echo $accepted_count; ?> accepted / <?php echo $trip['group_size_min']; ?> - <?php echo $trip['group_size_max']; ?> people
                                </div>
                            </div>
                        </div>

                        <div class="trip-actions">
                            <a href="viewTrip.php?trip_id=<?php echo $trip['id']; ?>" class="btn-small btn-view">
                                <i class="fas fa-eye"></i> View Details
                            </a>

                            <?php if ($trip['user_role'] === 'host'): ?>
                                <a href="editTrip.php?id=<?php echo $trip['id']; ?>" class="btn-small btn-edit">
                                    <i class="fas fa-pencil-alt"></i> Edit Trip
                                </a>
                            <?php endif; ?>

                            <?php if (in_array($trip['user_role'], ['host', 'collaborator'])): ?>
                                <a href="manageApplicants.php?trip_id=<?php echo $trip['id']; ?>" class="btn-small btn-applicants">
                                    <i class="fas fa-users"></i> Applicants (<?php echo $accepted_count; ?>)
                                </a>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>