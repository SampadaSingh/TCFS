<?php
session_start();
require "../config/db.php";

if (!isset($_SESSION['user_id']) || !isset($_GET['trip_id'])) {
    header('Location: myTrips.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$trip_id = (int)$_GET['trip_id'];

$is_host = false;
$is_collaborator = false;

$role_stmt = $conn->prepare("
    SELECT 
        t.*,
        CASE 
            WHEN t.host_id = ? THEN 'host'
            WHEN cr.status = 'accepted' THEN 'collaborator'
        END AS role
    FROM trips t
    LEFT JOIN collaborator_requests cr 
        ON t.id = cr.trip_id 
       AND cr.collaborator_id = ?
       AND cr.status = 'accepted'
    WHERE t.id = ?
");
$role_stmt->bind_param("iii", $user_id, $user_id, $trip_id);
$role_stmt->execute();
$trip = $role_stmt->get_result()->fetch_assoc();
$role_stmt->close();

if (!$trip || !$trip['role']) {
    header("Location: viewTrip.php?trip_id=$trip_id");
    exit;
}

$is_host = ($trip['role'] === 'host');
$is_collaborator = ($trip['role'] === 'collaborator');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!$is_host) {
        header("Location: manageApplicants.php?trip_id=$trip_id");
        exit;
    }

    $app_id = (int)$_POST['app_id'];
    $action = $_POST['action'];

    $stmt = $conn->prepare("SELECT status FROM trip_applications WHERE id = ? AND trip_id = ?");
    $stmt->bind_param("ii", $app_id, $trip_id);
    $stmt->execute();
    $application = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($application) {
        if ($action === 'accept' && $application['status'] !== 'accepted') {
            $u = $conn->prepare("UPDATE trip_applications SET status='accepted' WHERE id=?");
            $u->bind_param("i", $app_id);
            $u->execute();
        }

        if ($action === 'reject' && $application['status'] !== 'rejected') {
            $u = $conn->prepare("UPDATE trip_applications SET status='rejected' WHERE id=?");
            $u->bind_param("i", $app_id);
            $u->execute();
        }

        $count_stmt = $conn->prepare("
            SELECT COUNT(DISTINCT user_id) AS accepted_count, t.group_size_min
            FROM trip_applications a
            JOIN trips t ON a.trip_id = t.id
            WHERE a.trip_id = ? AND a.status = 'accepted'
        ");
        $count_stmt->bind_param("i", $trip_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result()->fetch_assoc();
        $count_stmt->close();

        if ($count_result['accepted_count'] >= $count_result['group_size_min']) {
            $confirm_stmt = $conn->prepare("UPDATE trips SET status='confirmed' WHERE id=?");
            $confirm_stmt->bind_param("i", $trip_id);
            $confirm_stmt->execute();
        }
    }

    header("Location: manageApplicants.php?trip_id=$trip_id");
    exit;
}

$accepted_stmt = $conn->prepare("
    SELECT COUNT(DISTINCT user_id) AS accepted_count 
    FROM trip_applications 
    WHERE trip_id = ? AND status = 'accepted'
");
$accepted_stmt->bind_param("i", $trip_id);
$accepted_stmt->execute();
$accepted_count = $accepted_stmt->get_result()->fetch_assoc()['accepted_count'];
$accepted_stmt->close();

$apps_stmt = $conn->prepare("
    SELECT a.*, u.name, u.email, u.age, u.gender 
    FROM trip_applications a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.trip_id = ?
    ORDER BY a.compatibility_score DESC
");
$apps_stmt->bind_param("i", $trip_id);
$apps_stmt->execute();
$applications = $apps_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Applicants - TCFS</title>
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

        .header-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .trip-title {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
        }

        .trip-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .stat {
            text-align: center;
        }

        .stat-value {
            font-size: 20px;
            font-weight: 700;
            color: #57C785;
        }

        .stat-label {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        .app-item {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .app-left {
            flex: 1;
        }

        .app-name {
            font-weight: 700;
            font-size: 16px;
            color: #333;
            margin-bottom: 8px;
        }

        .app-meta {
            display: flex;
            gap: 20px;
            font-size: 13px;
            color: #666;
        }

        .app-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .score {
            background: linear-gradient(135deg, #2A7B9B 0%, #57C785 50%, #EDDD53 100%);
            color: white;
            padding: 12px 18px;
            border-radius: 8px;
            text-align: center;
            min-width: 70px;
        }

        .score-num {
            font-size: 18px;
            font-weight: 700;
        }

        .score-label {
            font-size: 10px;
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

        .status-accepted {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .app-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
        }

        .btn-accept {
            background: #28a745;
            color: white;
        }

        .btn-accept:hover {
            background: #218838;
        }

        .btn-reject {
            background: #dc3545;
            color: white;
        }

        .btn-reject:hover {
            background: #c82333;
        }

        .empty {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 12px;
        }

        @media (max-width: 768px) {
            .trip-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .app-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .app-right {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <a href="myTrips.php" class="btn btn-outline-secondary mb-4"><i class="fas fa-arrow-left"></i> Back</a>

        <div class="header-card">
            <h2 class="trip-title"><?php echo htmlspecialchars($trip['trip_name']); ?></h2>
            <div class="trip-stats">
                <div class="stat">
                    <div class="stat-value"><?php echo $accepted_count ?? 0; ?></div>
                    <div class="stat-label">Accepted</div>
                </div>
                <div class="stat">
                    <div class="stat-value"><?php echo $trip['group_size_min']; ?></div>
                    <div class="stat-label">Target</div>
                </div>
                <div class="stat">
                    <div class="stat-value"><?php echo $applications->num_rows; ?></div>
                    <div class="stat-label">Total Applications</div>
                </div>
                <div class="stat">
                    <div class="stat-value"><?php echo ucfirst($trip['status']); ?></div>
                    <div class="stat-label">Status</div>
                </div>
            </div>
        </div>

        <h5 class="mb-4">Applicants</h5>

        <?php if ($applications->num_rows === 0): ?>
            <div class="empty">
                <p style="color: #999; margin: 0;">No applications yet</p>
            </div>
        <?php else: ?>
            <?php while ($app = $applications->fetch_assoc()): ?>
                <div class="app-item">
                    <div class="app-left">
                        <div class="app-name"><?php echo htmlspecialchars($app['name']); ?></div>
                        <div class="app-meta">
                            <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($app['email']); ?></span>
                            <span><i class="fas fa-birthday-cake"></i> <?php echo $app['age']; ?> years</span>
                            <span><i class="fas fa-venus-mars"></i> <?php echo ucfirst($app['gender']); ?></span>
                        </div>
                    </div>

                    <div class="app-right">
                        <div class="score">
                            <div class="score-num"><?php echo $app['compatibility_score']; ?>%</div>
                            <div class="score-label">Match</div>
                        </div>
                        <span class="status-badge status-<?php echo strtolower($app['status']); ?>">
                            <?php echo ucfirst($app['status']); ?>
                        </span>
                        <?php if ($app['status'] === 'pending'): ?>
                            <?php if ($is_host): ?>
                                <form method="post" style="display: flex; gap: 8px;">
                                    <input type="hidden" name="app_id" value="<?php echo $app['id']; ?>">
                                    <button type="submit" name="action" value="accept" class="btn-sm btn-accept">Accept</button>
                                    <button type="submit" name="action" value="reject" class="btn-sm btn-reject">Reject</button>
                                </form>
                            <?php else: ?>
                            <?php endif; ?>
                        <?php endif; ?>

                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</body>

</html>