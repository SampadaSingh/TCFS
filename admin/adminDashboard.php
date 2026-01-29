<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: auth/login.php");
    exit;
}

$today = date('Y-m-d');

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='User'");
$total_users = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM trips");
$total_trips = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM trips WHERE start_date <= '$today' AND end_date >= '$today'");
$active_trips = $result->fetch_assoc()['count'];

$recent_users = $conn->query("SELECT id, name, email, created_at FROM users WHERE role='User' ORDER BY created_at DESC LIMIT 5");

$recent_trips = $conn->query("SELECT t.id, t.trip_name as trip_name, t.destination, t.start_date, u.name as user_name FROM trips t JOIN users u ON t.host_id = u.id ORDER BY t.created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: #ffffff;
            color: #333;
            padding: 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border-right: 1px solid #f0f0f0;
        }

        .logo {
            padding: 20px;
            font-size: 20px;
            font-weight: 700;
            border-bottom: 2px solid #f5f5f5;
            text-align: center;
            color: #57C785;
        }

        .logo h2 {
            color: #57C785;
            font-size: 20px;
            font-weight: 700;
            margin: 0;
        }

        .logo p {
            display: none;
        }

        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-item {
            margin: 5px 0;
        }

        .nav-link {
            color: #666;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 20px;
            border-radius: 0;
            transition: all 0.3s ease;
            font-weight: 500;
            border-left: 4px solid transparent;
        }

        .nav-link:hover {
            background-color: #E8F4F8;
            border-left-color: #57C785;
            color: #57C785;
            padding-left: 20px;
        }

        .nav-link.active {
            background: linear-gradient(135deg, #2A7B9B 0%, #57C785 50%, #EDDD53 100%);
            color: white;
            border-left-color: #2A7B9B;
            font-weight: 600;
            border-radius: 15px;
            padding: 15px 20px;
        }

        .nav-link i {
            font-size: 18px;
            width: auto;
        }

        .logout-btn {
            margin-top: auto;
            border-top: 2px solid #f5f5f5;
            padding-top: 10px;
        }

        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 30px;
        }

        .header {
            background: white;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .header h1 {
            font-size: 28px;
            color: #1a1a1a;
            font-weight: 700;
            margin: 0;
        }

        .logout-link {
            background: #57C785;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s ease;
        }

        .logout-link:hover {
            background: #2A7B9B;
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border-left: 5px solid #57C785;
        }

        .stat-label {
            color: #666;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #1a1a1a;
        }

        .table-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .table-section h3 {
            font-size: 18px;
            color: #1a1a1a;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-section h3 i {
            color: #57C785;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: #f9f9f9;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: #666;
            border-bottom: 2px solid #eee;
        }

        .data-table td {
            padding: 15px 12px;
            border-bottom: 1px solid #eee;
            color: #333;
        }

        .data-table tr:hover {
            background: #f9f9f9;
        }

        .user-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #57C785;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }

        .badge-new {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-active {
            background: #fff3e0;
            color: #e65100;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .action-link {
            color: #57C785;
            text-decoration: none;
            font-weight: 600;
            font-size: 12px;
        }

        .action-link:hover {
            color: #2A7B9B;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .empty-state i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
            display: block;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                left: -260px;
                transition: left 0.3s;
                z-index: 999;
                height: 100vh;
            }

            .sidebar.active {
                left: 0;
            }

            .main-content {
                margin-left: 0;
            }

            .header {
                flex-direction: column;
                gap: 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="content-header">
                <h1><i class="bi bi-speedometer2"></i> Dashboard</h1>
            </div>

            <div class="content-body">
                <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-label">Total Users</div>
                    <div class="stat-value"><?php echo $total_users; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Total Trips</div>
                    <div class="stat-value"><?php echo $total_trips; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Active Trips</div>
                    <div class="stat-value"><?php echo $active_trips; ?></div>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="table-section">
                <h3><i class="bi bi-person-plus"></i> Recent Users</h3>
                <?php if ($recent_users->num_rows > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $recent_users->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="user-cell">
                                            <div class="user-avatar">
                                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                            </div>
                                            <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><span class="badge-new"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <p>No users yet</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="bi bi-airplane-engines"></i> Recent Trips</h3>
                </div>
                <div class="card-body">
                    <?php if ($recent_trips->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Trip Name</th>
                                        <th>Destination</th>
                                        <th>Created By</th>
                                        <th>Start Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($trip = $recent_trips->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($trip['trip_name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($trip['destination']); ?></td>
                                            <td><?php echo htmlspecialchars($trip['user_name']); ?></td>
                                            <td><span class="badge-active"><?php echo date('M d, Y', strtotime($trip['start_date'])); ?></span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>No trips yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    </div>
</body>

</html>
