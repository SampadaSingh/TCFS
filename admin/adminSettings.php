<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../auth/login.php");
    exit;
}

$admin_id = $_SESSION['user_id'];
$admin = $conn->query("SELECT * FROM users WHERE id = $admin_id")->fetch_assoc();

$site_stats = [];
$site_stats['total_users'] = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='User'")->fetch_assoc()['count'];
$site_stats['total_trips'] = $conn->query("SELECT COUNT(*) as count FROM trips")->fetch_assoc()['count'];
$site_stats['total_applications'] = $conn->query("SELECT COUNT(*) as count FROM trip_applications")->fetch_assoc()['count'];
$site_stats['pending_applications'] = $conn->query("SELECT COUNT(*) as count FROM trip_applications WHERE status='pending'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="content-header">
                <h1><i class="bi bi-gear-fill"></i> Settings</h1>
            </div>

            <div class="content-body">
                <div class="settings-grid">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="bi bi-person-circle"></i> Admin Profile</h3>
                        </div>
                        <div class="card-body">
                            <form id="adminProfileForm">
                                <input type="hidden" name="admin_id" value="<?php echo $admin_id; ?>">
                                <div class="form-group">
                                    <label>Name <span class="required">*</span></label>
                                    <input type="text" id="admin_name" name="name" value="<?php echo htmlspecialchars($admin['name']); ?>" required>
                                    <span class="error-message" id="error_admin_name"></span>
                                </div>
                                <div class="form-group">
                                    <label>Email <span class="required">*</span></label>
                                    <input type="email" id="admin_email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                                    <span class="error-message" id="error_admin_email"></span>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn-primary">
                                        <i class="bi bi-check-circle"></i> Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3><i class="bi bi-shield-lock"></i> Change Password</h3>
                        </div>
                        <div class="card-body">
                            <form id="changePasswordForm">
                                <input type="hidden" name="admin_id" value="<?php echo $admin_id; ?>">
                                <div class="form-group">
                                    <label>Current Password <span class="required">*</span></label>
                                    <input type="password" id="current_password" name="current_password" required>
                                    <span class="error-message" id="error_current_password"></span>
                                </div>
                                <div class="form-group">
                                    <label>New Password <span class="required">*</span></label>
                                    <input type="password" id="new_password" name="new_password" required>
                                    <span class="error-message" id="error_new_password"></span>
                                </div>
                                <div class="form-group">
                                    <label>Confirm New Password <span class="required">*</span></label>
                                    <input type="password" id="confirm_password" name="confirm_password" required>
                                    <span class="error-message" id="error_confirm_password"></span>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn-primary">
                                        <i class="bi bi-key"></i> Change Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3><i class="bi bi-bar-chart-fill"></i> System Statistics</h3>
                        </div>
                        <div class="card-body">
                            <div class="stats-list">
                                <div class="stat-item">
                                    <span class="stat-label">Total Users:</span>
                                    <span class="stat-value"><?php echo $site_stats['total_users']; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Total Trips:</span>
                                    <span class="stat-value"><?php echo $site_stats['total_trips']; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Total Applications:</span>
                                    <span class="stat-value"><?php echo $site_stats['total_applications']; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Pending Applications:</span>
                                    <span class="stat-value"><?php echo $site_stats['pending_applications']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3><i class="bi bi-database"></i> Database Management</h3>
                        </div>
                        <div class="card-body">
                            <div class="db-actions">
                                <button class="btn-danger" onclick="cleanupOldData()">
                                    <i class="bi bi-trash3"></i> Cleanup Old Data
                                </button>
                                <button class="btn-secondary" onclick="exportData()">
                                    <i class="bi bi-download"></i> Export Data
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/admin-settings.js"></script>
</body>

</html>