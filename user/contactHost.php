<?php
session_start();
require "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if (!isset($_GET['trip_id'])) {
    header('Location: discoverTrips.php');
    exit;
}

$trip_id = (int)$_GET['trip_id'];
$user_id = $_SESSION['user_id'];

$app_stmt = $conn->prepare("
    SELECT * 
    FROM trip_applications 
    WHERE user_id = ? AND trip_id = ? AND status = 'accepted'
");
$app_stmt->bind_param("ii", $user_id, $trip_id);
$app_stmt->execute();
$application = $app_stmt->get_result()->fetch_assoc();

if (!$application) {
    header('Location: tripApplications.php');
    exit;
}

$trip_stmt = $conn->prepare("SELECT id, trip_name, host_id FROM trips WHERE id=?");
$trip_stmt->bind_param("i", $trip_id);
$trip_stmt->execute();
$trip = $trip_stmt->get_result()->fetch_assoc();

$host_stmt = $conn->prepare("SELECT id, name, email FROM users WHERE id=?");
$host_stmt->bind_param("i", $trip['host_id']);
$host_stmt->execute();
$host = $host_stmt->get_result()->fetch_assoc();

$message_sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $msg_stmt = $conn->prepare("
            INSERT INTO contact_requests (sender_id, recipient_id, trip_id, message, status, created_at)
            VALUES (?, ?, ?, ?, 'pending', NOW())
        ");
        $msg_stmt->bind_param("iiis", $user_id, $host['id'], $trip_id, $message);
        if ($msg_stmt->execute()) {
            $message_sent = true;
        }
    }
}

$conv_stmt = $conn->prepare("
    SELECT * 
    FROM contact_requests
    WHERE trip_id=? AND ((sender_id=? AND recipient_id=?) OR (sender_id=? AND recipient_id=?))
    ORDER BY created_at ASC
");
$conv_stmt->bind_param("iiiii", $trip_id, $user_id, $host['id'], $host['id'], $user_id);
$conv_stmt->execute();
$conversation = $conv_stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Host - TCFS</title>
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

        .contact-container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .subtitle {
            font-size: 14px;
            color: #999;
            margin-bottom: 20px;
        }

        .message {
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 8px;
            font-size: 14px;
            max-width: 80%;
        }

        .message-user {
            background: #fff3cd;
            color: #856404;
            margin-left: auto;
        }

        .message-host {
            background: #d4edda;
            color: #155724;
            margin-right: auto;
        }

        textarea {
            width: 100%;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            resize: none;
        }

        .btn-send {
            background: #2A7B9B;
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 10px;
            cursor: pointer;
        }

        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <a href="tripApplications.php" class="btn btn-outline-secondary mb-4"><i class="fas fa-arrow-left"></i> Back to Applications</a>

        <div class="contact-container">
            <h1 class="title">Contact <?php echo htmlspecialchars($host['name']); ?></h1>
            <p class="subtitle">Trip: <?php echo htmlspecialchars($trip['trip_name']); ?></p>

            <?php if ($message_sent): ?>
                <div class="success-message"><i class="fas fa-check-circle"></i> Message sent successfully!</div>
            <?php endif; ?>

            <div style="margin-bottom:20px;">
                <?php while ($msg = $conversation->fetch_assoc()): ?>
                    <div class="message <?php echo $msg['sender_id'] == $user_id ? 'message-user' : 'message-host'; ?>">
                        <?php echo htmlspecialchars($msg['message']); ?>
                    </div>
                <?php endwhile; ?>
            </div>

            <form method="post">
                <textarea name="message" rows="3" placeholder="Write a message to the host..." required></textarea>
                <button type="submit" class="btn-send"><i class="fas fa-paper-plane"></i> Send</button>
            </form>
        </div>
    </div>
</body>

</html>