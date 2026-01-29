<?php
session_start();
require "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$status_filter = $_GET['status'] ?? 'all';

$query = "SELECT a.*, t.trip_name, t.destination, t.start_date, t.end_date, u.name as host_name 
          FROM trip_applications a 
          JOIN trips t ON a.trip_id = t.id 
          JOIN users u ON t.host_id = u.id 
          WHERE a.user_id = ?";

if ($status_filter !== 'all') {
    $query .= " AND a.status = ?";
}

$query .= " ORDER BY a.applied_date DESC";

$stmt = $conn->prepare($query);
if ($status_filter !== 'all') {
    $stmt->bind_param("is", $user_id, $status_filter);
} else {
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$applications = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Applications - TCFS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body {
    background: #f5f7fa;
    font-family: 'Segoe UI', sans-serif;
    margin: 0;
    padding: 0;
}
.main-content {
    margin-left: 250px;
    padding: 40px 30px;
    min-height: 100vh;
}
.header {
    background: white;
    padding: 30px 0;
    border-bottom: 1px solid #e5e5e5;
    margin-bottom: 30px;
}
.tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    background: white;
    padding: 15px;
    border-radius: 12px;
}
.tab-btn {
    padding: 10px 20px;
    background: transparent;
    border: none;
    cursor: pointer;
    font-weight: 600;
    color: #999;
    border-bottom: 3px solid transparent;
    transition: all 0.3s;
}
.tab-btn.active {
    color: #57C785;
    border-bottom-color: #57C785;
}
.app-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
}
.app-info {
    flex: 1;
}
.app-trip-name {
    font-size: 18px;
    font-weight: 700;
    color: #333;
    margin-bottom: 8px;
}
.app-details {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin: 10px 0;
    font-size: 13px;
    color: #666;
}
.detail {
    display: flex;
    align-items: center;
    gap: 8px;
}
.app-score {
    background: linear-gradient(135deg, #2A7B9B 0%, #57C785 50%, #EDDD53 100%);
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    text-align: center;
    min-width: 100px;
}
.score-num {
    font-size: 24px;
    font-weight: 700;
}
.score-label {
    font-size: 11px;
    opacity: 0.9;
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
.app-actions {
    display: flex;
    gap: 10px;
}
.btn-action {
    padding: 8px 15px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-size: 13px;
    text-decoration: none;
    transition: all 0.3s;
}
.btn-contact {
    background: #57C785;
    color: white;
}
.btn-contact:hover {
    background: #2A7B9B;
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
@media (max-width: 768px) {
    .app-card {
        flex-direction: column;
        align-items: flex-start;
    }
    .app-details {
        grid-template-columns: repeat(2, 1fr);
    }
    .app-score {
        width: 100%;
        margin-top: 15px;
    }
}
</style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="header">
        <div class="container">
            <h2 class="fw-bold mb-0">My Trip Applications</h2>
        </div>
    </div>

    <div class="container">
        <div class="tabs">
            <button class="tab-btn <?php echo $status_filter === 'all' ? 'active' : ''; ?>" onclick="window.location.href='tripApplications.php?status=all'">
                All Applications
            </button>
            <button class="tab-btn <?php echo $status_filter === 'pending' ? 'active' : ''; ?>" onclick="window.location.href='tripApplications.php?status=pending'">
                <i class="fas fa-hourglass-half"></i> Pending
            </button>
            <button class="tab-btn <?php echo $status_filter === 'accepted' ? 'active' : ''; ?>" onclick="window.location.href='tripApplications.php?status=accepted'">
                <i class="fas fa-check-circle"></i> Accepted
            </button>
            <button class="tab-btn <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>" onclick="window.location.href='tripApplications.php?status=rejected'">
                <i class="fas fa-times-circle"></i> Rejected
            </button>
        </div>

        <?php if ($applications->num_rows === 0): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-inbox"></i></div>
                <h4>No applications yet</h4>
                <p class="text-muted mb-4">Explore trips and apply to find your next travel buddy</p>
                <a href="discoverTrips.php" class="btn" style="background: linear-gradient(135deg, #2A7B9B 0%, #57C785 50%, #EDDD53 100%); color: white; padding: 12px 30px; border-radius: 8px; text-decoration: none;">
                    <i class="fas fa-search"></i> Discover Trips
                </a>
            </div>
        <?php else: ?>
            <?php while ($app = $applications->fetch_assoc()): ?>
                <div class="app-card">
                    <div class="app-info">
                        <h5 class="app-trip-name"><?php echo htmlspecialchars($app['trip_name']); ?></h5>
                        <div class="app-details">
                            <div class="detail">
                                <i class="fas fa-map-marker-alt" style="color: #57C785;"></i>
                                <?php echo htmlspecialchars($app['destination']); ?>
                            </div>
                            <div class="detail">
                                <i class="fas fa-calendar-alt" style="color: #57C785;"></i>
                                <?php echo date('M d, Y', strtotime($app['start_date'])); ?>
                            </div>
                            <div class="detail">
                                <i class="fas fa-user" style="color: #57C785;"></i>
                                <?php echo htmlspecialchars($app['host_name']); ?>
                            </div>
                            <div class="detail">
                                <span class="status-badge status-<?php echo strtolower($app['status']); ?>">
                                    <?php echo ucfirst($app['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="app-score">
                        <div class="score-num"><?php echo $app['compatibility_score']; ?>%</div>
                        <div class="score-label">Match Score</div>
                    </div>

                    <div class="app-actions">
                        <?php if ($app['status'] === 'accepted'): ?>
                            <a href="contactHost.php?trip_id=<?php echo $app['trip_id']; ?>" class="btn-action btn-contact">Contact Host</a>
                        <?php endif; ?>
                        <a href="viewTrip.php?trip_id=<?php echo $app['trip_id']; ?>" class="btn-action" style="background: #f5f7fa; color: #57C785; border: 1px solid #57C785;">View Trip</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
