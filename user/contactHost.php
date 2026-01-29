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

$app_stmt = $conn->prepare("SELECT * FROM trip_applications WHERE user_id = ? AND trip_id = ? AND status = 'accepted'");
$app_stmt->bind_param("ii", $user_id, $trip_id);
$app_stmt->execute();
$application = $app_stmt->get_result()->fetch_assoc();

if (!$application) {
    header('Location: tripApplications.php');
    exit;
}

$trip_stmt = $conn->prepare("SELECT id, trip_name, host_id FROM trips WHERE id = ?");
$trip_stmt->bind_param("i", $trip_id);
$trip_stmt->execute();
$trip = $trip_stmt->get_result()->fetch_assoc();

$host_stmt = $conn->prepare("SELECT id, name, email FROM users WHERE id = ?");
$host_stmt->bind_param("i", $trip['host_id']);
$host_stmt->execute();
$host = $host_stmt->get_result()->fetch_assoc();

$message_sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $msg_stmt = $conn->prepare("INSERT INTO contact_requests (sender_id, recipient_id, trip_id, message) VALUES (?, ?, ?, ?)");
        $msg_stmt->bind_param("iiis", $user_id, $host['id'], $trip_id, $message);
        if ($msg_stmt->execute()) {
            $message_sent = true;
        }
    }
}
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

        .header {
            background: linear-gradient(135deg, #2A7B9B 0%, #57C785 50%, #EDDD53 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
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
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            padding: 40px;
            max-width: 600px;
            margin: 0 auto;
        }

        .title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .subtitle {
            font-size: 14px;
            color: #999;
            margin-bottom: 30px;
        }

        .host-info {
            background: #f5f7fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .host-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .host-name {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
        }

        .trip-reference {
            font-size: 13px;
            color: #666;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        textarea {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            font-family: 'Segoe UI', sans-serif;
            font-size: 14px;
        }

        textarea:focus {
            border-color: #57C785;
            box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.1);
        }

        .btn-send {
            background: linear-gradient(135deg, #2A7B9B 0%, #57C785 50%, #EDDD53 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 30px;
            border-radius: 8px;
            width: 100%;
            margin-top: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-send:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            color: white;
        }

        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .link-back {
            color: white;
            text-decoration: none;
            font-size: 14px;
        }

        .link-back:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
    <div class="header">
        <div class="container">
            <a href="tripApplications.php" class="link-back"><i class="fas fa-arrow-left"></i> Back to Applications</a>
            <h1 class="title">Contact <?php echo htmlspecialchars($host['name']); ?></h1>
            <p class="subtitle">About: <?php echo htmlspecialchars($trip['trip_name']); ?></p>
        </div>
    </div>

    <div class="container">
        <div class="contact-container">
            <?php if ($message_sent): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> Message sent successfully!
                </div>
            <?php endif; ?>

            <div class="host-info">
                <div class="host-label">Trip Host</div>
                <div class="host-name"><?php echo htmlspecialchars($host['name']); ?></div>
                <div class="trip-reference">
                    <i class="fas fa-map-pin"></i> Reference: <?php echo htmlspecialchars($trip['trip_name']); ?>
                </div>
            </div>

            <form method="POST">
                <label class="form-label">Your Message</label>
                <textarea name="message" rows="6" placeholder="Hi! I'm excited about this trip and would like to know more details..." required></textarea>

                <button type="submit" class="btn-send">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
            </form>

            <p style="font-size: 12px; color: #999; margin-top: 20px; text-align: center;">
                Your email is not visible to the host. Messages are relayed safely.
            </p>
        </div>
    </div>
    </div>
</body>

</html>
