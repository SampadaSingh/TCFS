<?php
session_start();
require "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = null;

if (isset($_GET['user_id']) && ctype_digit($_GET['user_id'])) {
    $user_id = (int) $_GET['user_id'];
}

if (!$user_id || $user_id <= 0) {
    http_response_code(400);
    die("Invalid or missing user ID.");
}


$stmt = $conn->prepare("
    SELECT id, name, email, age, gender, bio, location, created_at 
    FROM users 
    WHERE id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User not found.");
}


$interests = [];
$stmt = $conn->prepare("
    SELECT i.interest_name 
    FROM user_interests ui
    JOIN interests i ON ui.interest_id = i.id
    WHERE ui.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $interests[] = $row['interest_name'];
}
$stmt->close();

$logged_in_user_id = $_SESSION['user_id'];
$loggedInUserInterests = [];
$stmt = $conn->prepare("
    SELECT i.interest_name 
    FROM user_interests ui
    JOIN interests i ON ui.interest_id = i.id
    WHERE ui.user_id = ?
");
$stmt->bind_param("i", $logged_in_user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $loggedInUserInterests[] = $row['interest_name'];
}
$stmt->close();

$commonInterests = array_intersect($interests, $loggedInUserInterests);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['name']); ?> - Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #E8F4F8;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        .profile-card {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }
        .profile-header {
            text-align: center;
            margin-bottom: 25px;
        }
        .profile-header i {
            font-size: 50px;
            color: #57C785;
            margin-bottom: 10px;
        }
        .profile-header h2 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .profile-header p {
            color: #999;
            font-size: 14px;
        }
        .profile-details {
            margin-top: 20px;
        }
        .profile-details .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f0f0f0;
        }
        .profile-details .detail-item .detail-value {
            max-width: 70%;
            word-wrap: break-word;
        }
        .detail-label {
            color: #555;
            font-weight: 600;
        }
        .detail-value {
            color: #333;
        }
        .interests-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }
        .interest-tag {
            background: #E8F4F8;
            color: #2A7B9B;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .common-interest-tag {
            background: linear-gradient(135deg, #57C785, #2A7B9B);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .common-interests-section {
            background: #f0f8f5;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #57C785;
        }
        .common-interests-title {
            font-size: 12px;
            font-weight: 700;
            color: #57C785;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="profile-card">
    <div class="profile-header">
        <i class="bi bi-person-circle"></i>
        <h2><?php echo htmlspecialchars($user['name']); ?></h2>
    </div>

    <div class="profile-details">
        <div class="detail-item">
            <div class="detail-label">Name:</div>
            <div class="detail-value"><?php echo htmlspecialchars($user['name']); ?></div>
        </div>

        <!--<div class="detail-item">
            <div class="detail-label">Email:</div>
            <div class="detail-value"><?php echo htmlspecialchars($user['email']); ?></div>
        </div>-->

        <div class="detail-item">
            <div class="detail-label">Gender:</div>
            <div class="detail-value"><?php echo htmlspecialchars($user['gender'] ?? 'N/A'); ?></div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Location:</div>
            <div class="detail-value"><?php echo htmlspecialchars($user['location'] ?? 'N/A'); ?></div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Bio:</div>
            <div class="detail-value"><?php echo htmlspecialchars($user['bio'] ?? 'N/A'); ?></div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Age:</div>
            <div class="detail-value"><?php echo htmlspecialchars($user['age'] ?? 'N/A'); ?></div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Member Since:</div>
            <div class="detail-value"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></div>
        </div>

          <?php if (!empty($commonInterests)): ?>
        <div class="common-interests-section">
            <div class="common-interests-title">
                <i class="bi bi-star-fill"></i> Common Interests (<?php echo count($commonInterests); ?>)
            </div>
            <div class="interests-container">
                <?php foreach ($commonInterests as $interest): ?>
                    <span class="common-interest-tag">
                        ✓ <?php echo htmlspecialchars($interest); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
