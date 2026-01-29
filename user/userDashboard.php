<?php
session_start();
require "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if preferences are filled
$pref_stmt = $conn->prepare("SELECT preferences_filled FROM user_preferences WHERE user_id = ?");
$pref_stmt->bind_param("i", $user_id);
$pref_stmt->execute();
$pref = $pref_stmt->get_result()->fetch_assoc();

// If preferences are not filled, redirect to preferences.php
if (!$pref || $pref['preferences_filled'] == 0) {
    header("Location: preferences.php");
    exit;
}

// Fetch user info
$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

// Stats
$trips_stmt = $conn->prepare("SELECT COUNT(*) as count FROM trips WHERE host_id = ?");
$trips_stmt->bind_param("i", $user_id);
$trips_stmt->execute();
$trips_count = $trips_stmt->get_result()->fetch_assoc()['count'];

$apps_stmt = $conn->prepare("SELECT COUNT(*) as count FROM trip_applications WHERE user_id = ?");
$apps_stmt->bind_param("i", $user_id);
$apps_stmt->execute();
$apps_count = $apps_stmt->get_result()->fetch_assoc()['count'];

$accepted_stmt = $conn->prepare("SELECT COUNT(*) as count FROM trip_applications WHERE user_id = ? AND status = 'accepted'");
$accepted_stmt->bind_param("i", $user_id);
$accepted_stmt->execute();
$accepted_count = $accepted_stmt->get_result()->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - TCFS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #E8F4F8;
        }

        .main-content {
            margin-left: 250px;
            padding: 40px 30px;
            min-height: 100vh;
        }

        .page-title {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin-bottom: 25px;
        }

        /* Welcome Card */
        .welcome-card {
            background: linear-gradient(135deg, #57C785 0%, #FFA65D 100%);
            color: white;
            border-radius: 16px;
            padding: 40px 30px;
            margin-bottom: 40px;
            box-shadow: 0 6px 18px rgba(234, 88, 12, 0.2);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .welcome-card h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .welcome-card p {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.9);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: #fff;
            padding: 25px 20px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 18px rgba(234, 88, 12, 0.15);
        }

        .stat-icon {
            font-size: 36px;
            color: #57C785;
            margin-bottom: 12px;
        }

        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 6px;
        }

        .stat-label {
            font-size: 14px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Action Cards */
        .action-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
        }

        .action-card {
            background: linear-gradient(135deg, #E8F4F8 0%, #F0F9EC 100%);
            padding: 30px 20px;
            border-radius: 16px;
            text-align: center;
            text-decoration: none;
            color: #333;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 18px rgba(234, 88, 12, 0.15);
        }

        .action-icon {
            font-size: 40px;
            color: #57C785;
            margin-bottom: 15px;
        }

        .action-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .action-desc {
            font-size: 13px;
            color: #999;
        }

        @media (max-width: 768px) {
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
        <h1 class="page-title">Dashboard</h1>

        <div class="welcome-card">
            <div>
                <h2>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h2>
                <p>Browse trips, apply, and connect with fellow travelers.</p>
            </div>
            <div style="font-size: 80px; font-weight: 700; color: rgba(255,255,255,0.2);">
                <i class="bi bi-compass"></i>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-map"></i></div>
                <div class="stat-number"><?php echo $trips_count; ?></div>
                <div class="stat-label">Trips Hosted</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-file-earmark-text"></i></div>
                <div class="stat-number"><?php echo $apps_count; ?></div>
                <div class="stat-label">Applications</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                <div class="stat-number"><?php echo $accepted_count; ?></div>
                <div class="stat-label">Accepted</div>
            </div>
        </div>

        <div class="action-cards">
            <a href="discoverTrips.php" class="action-card">
                <div class="action-icon"><i class="bi bi-compass"></i></div>
                <div class="action-title">Discover Trips</div>
                <div class="action-desc">Browse and apply to trips</div>
            </a>

            <a href="tripApplications.php" class="action-card">
                <div class="action-icon"><i class="bi bi-inbox"></i></div>
                <div class="action-title">My Applications</div>
                <div class="action-desc">Track your applications</div>
            </a>

            <a href="myTrips.php" class="action-card">
                <div class="action-icon"><i class="bi bi-list"></i></div>
                <div class="action-title">Hosted Trips</div>
                <div class="action-desc">Manage your trips</div>
            </a>

            <a href="editProfile.php" class="action-card">
                <div class="action-icon"><i class="bi bi-person-circle"></i></div>
                <div class="action-title">My Profile</div>
                <div class="action-desc">Update your info</div>
            </a>
        </div>
    </div>
</body>

</html>