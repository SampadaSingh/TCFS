<?php
session_start();
require "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['request_id'])) {
    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action'];

    if ($action === 'accept') {
        // Update request status
        $stmt = $conn->prepare("UPDATE collaborator_requests SET status = 'accepted', responded_at = NOW() WHERE id = ? AND collaborator_id = ?");
        $stmt->bind_param("ii", $request_id, $user_id);

        if ($stmt->execute()) {
            // Get trip_id
            $get_trip = $conn->prepare("SELECT trip_id FROM collaborator_requests WHERE id = ?");
            $get_trip->bind_param("i", $request_id);
            $get_trip->execute();
            $trip_data = $get_trip->get_result()->fetch_assoc();
            $get_trip->close();

            if ($trip_data) {
                // Optionally: if trips table needs a collaborator_id, update it
                // $update_trip = $conn->prepare("UPDATE trips SET collaborator_id = ? WHERE id = ?");
                // $update_trip->bind_param("ii", $user_id, $trip_data['trip_id']);
                // $update_trip->execute();
                // $update_trip->close();
            }

            $message = "Collaborator request accepted!";
        }
        $stmt->close();
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE collaborator_requests SET status = 'rejected', responded_at = NOW() WHERE id = ? AND collaborator_id = ?");
        $stmt->bind_param("ii", $request_id, $user_id);
        $stmt->execute();
        $stmt->close();
        $message = "Collaborator request rejected.";
    }
}

// Fetch all requests for this user
$requests_stmt = $conn->prepare("
    SELECT cr.id, cr.trip_id, cr.created_at, cr.status,
           t.trip_name, t.destination, t.start_date, t.end_date,
           u.name as host_name, u.email as host_email
    FROM collaborator_requests cr
    JOIN trips t ON cr.trip_id = t.id
    JOIN users u ON cr.host_id = u.id
    WHERE cr.collaborator_id = ?
    ORDER BY cr.created_at DESC
");
$requests_stmt->bind_param("i", $user_id);
$requests_stmt->execute();
$requests = $requests_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collaborator Requests - TCFS</title>
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

        .page-header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .request-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
        }

        .request-card:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
        }

        .request-header {
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

        .status-accepted {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .request-info {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 20px;
            padding: 15px 0;
            border-bottom: 1px solid #e5e5e5;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #666;
        }

        .info-item i {
            color: #57C785;
            font-size: 16px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-accept {
            background: #57C785;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-accept:hover {
            background: #45a368;
        }

        .btn-reject {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-reject:hover {
            background: #c82333;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 64px;
            color: #ccc;
            margin-bottom: 20px;
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
        <div class="page-header">
            <h1 class="page-title"><i class="bi bi-envelope-check"></i> Collaborator Requests</h1>
            <p class="text-muted">Manage collaboration invitations for trips</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($requests->num_rows > 0): ?>
            <?php while ($request = $requests->fetch_assoc()): ?>
                <div class="request-card">
                    <div class="request-header">
                        <div>
                            <h3 class="trip-title"><?= htmlspecialchars($request['trip_name']) ?></h3>
                            <small class="text-muted">From: <?= htmlspecialchars($request['host_name']) ?></small>
                        </div>
                        <span class="status-badge status-<?= $request['status'] ?>"><?= ucfirst($request['status']) ?></span>
                    </div>

                    <div class="request-info">
                        <div class="info-item">
                            <i class="bi bi-geo-alt-fill"></i>
                            <span><strong>Destination:</strong> <?= htmlspecialchars($request['destination']) ?></span>
                        </div>
                        <div class="info-item">
                            <i class="bi bi-calendar"></i>
                            <span><strong>Dates:</strong> <?= date('M d, Y', strtotime($request['start_date'])) ?> - <?= date('M d, Y', strtotime($request['end_date'])) ?></span>
                        </div>
                        <div class="info-item">
                            <i class="bi bi-clock"></i>
                            <span><strong>Requested:</strong> <?= date('M d, Y', strtotime($request['created_at'])) ?></span>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <a href="viewTrip.php?trip_id=<?= $request['trip_id'] ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-eye"></i> View Trip
                        </a>

                        <?php if ($request['status'] === 'pending'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                <input type="hidden" name="action" value="accept">
                                <button type="submit" class="btn-accept"><i class="bi bi-check-circle"></i> Accept</button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn-reject"><i class="bi bi-x-circle"></i> Reject</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h4>No Collaborator Requests</h4>
                <p>You don't have any collaborator requests at the moment.</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>