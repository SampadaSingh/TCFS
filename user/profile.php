<?php
session_start();
require "../config/db.php";

if(!isset($_SESSION['user_id'])){
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$user_query = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
$user = $user_result->fetch_assoc();

if(!$user) {
    session_destroy();
    header('Location: ../auth/login.php');
    exit;
}

$created_at = isset($user['created_at']) ? new DateTime($user['created_at']) : $today;
$member_since = $created_at->format('F Y');

$interests = [];
$interests_query = $conn->prepare("
    SELECT i.interest_name FROM user_interests ui
    JOIN interests i ON ui.interest_id = i.id
    WHERE ui.user_id = ?
    ORDER BY i.interest_name ASC
");
$interests_query->bind_param("i", $user_id);
$interests_query->execute();
$interests_result = $interests_query->get_result();
while($row = $interests_result->fetch_assoc()) {
    $interests[] = $row['interest_name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Travel Companion Finder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background-color: #E8F4F8;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 40px;
            min-height: 100vh;
        }
        
        .profile-container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 30px;
        }
        
        .profile-card {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 12px rgba(234, 88, 12, 0.06);
            margin-bottom: 25px;
        }
        
        .profile-header {
            display: flex;
            gap: 30px;
            align-items: flex-start;
            margin-bottom: 30px;
        }
        
        .profile-avatar-container {
            position: relative;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2A7B9B 0%, #57C785 50%, #EDDD53 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: bold;
            box-shadow: 0 4px 12px rgba(234, 88, 12, 0.2);
        }
        
        .avatar-badge {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 32px;
            height: 32px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }
        
        .avatar-badge i {
            color: #57C785;
            font-size: 16px;
        }
        
        .profile-info {
            flex: 1;
        }
        
        .profile-name {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        
        .profile-email {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .profile-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .detail-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .detail-value {
            font-size: 15px;
            color: #333;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .detail-value i {
            color: #57C785;
            font-size: 14px;
        }
        
        .edit-profile-btn {
            padding: 12px 30px;
            background: linear-gradient(135deg, #2A7B9B 0%, #57C785 50%, #EDDD53 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .edit-profile-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(234, 88, 12, 0.3);
            color: white;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
        }
        
        .about-text {
            color: #666;
            line-height: 1.6;
            font-size: 14px;
        }
        
        .tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .tag {
            padding: 8px 16px;
            background: #E8F4F8;
            color: #57C785;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            border: 2px solid #D4EDE1;
        }
        
        .achievements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
        }
        
        .achievement-card {
            background: #FFFBF7;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            border: 2px solid #D4EDE1;
            transition: all 0.3s ease;
        }
        
        .achievement-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(234, 88, 12, 0.15);
        }
        
        .achievement-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }
        
        .achievement-icon.gold {
            color: #FFB800;
        }
        
        .achievement-icon.blue {
            color: #3B82F6;
        }
        
        .achievement-icon.purple {
            color: #A855F7;
        }
        
        .achievement-title {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        .success-message {
            background: #D1FAE5;
            border: 1px solid #A7F3D0;
            color: #065F46;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
        }

        .success-message i {
            font-size: 18px;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            
            .profile-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .profile-details {
                grid-template-columns: 1fr;
            }
            
            .achievements-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="profile-container">
            <h1 class="page-title">My Profile</h1>

            <?php if(isset($_GET['success'])): ?>
            <div class="success-message">
                <i class="bi bi-check-circle-fill"></i>
                <span>Profile updated successfully!</span>
            </div>
            <?php endif; ?>
            
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar-container">
                        <div class="profile-avatar">
                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                        </div>
                        <div class="avatar-badge">
                            <i class="bi bi-camera-fill"></i>
                        </div>
                    </div>
                    
                    <div class="profile-info">
                        <h2 class="profile-name"><?php echo htmlspecialchars($user['name']); ?></h2>
                        <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                        
                        <div class="profile-details">
                            <div class="detail-item">
                                <span class="detail-label">Age</span>
                                <span class="detail-value"><?php echo htmlentities($user['age']); ?> years</span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Gender</span>
                                <span class="detail-value"><?php echo htmlspecialchars($user['gender']); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Location</span>
                                <span class="detail-value">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <?php echo isset($user['location']) ? htmlspecialchars($user['location']) : 'Not specified'; ?>
                                </span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Member Since</span>
                                <span class="detail-value">
                                    <i class="bi bi-calendar-check"></i>
                                    <?php echo $member_since; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 10px;">
                            <a href="editProfile.php" class="edit-profile-btn">
                                <i class="bi bi-pencil"></i> Edit Profile
                            </a>
                            <a href="preferences.php" class="edit-profile-btn">
                                <i class="bi bi-sliders"></i> Preferences
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="profile-card">
                <h3 class="section-title">About Me</h3>
                <p class="about-text">
                    <?php 
                    echo isset($user['bio']) && !empty($user['bio']) 
                        ? htmlspecialchars($user['bio']) 
                        : 'Adventure seeker and photography enthusiast. Love exploring new cultures and meeting fellow travelers.';
                    ?>
                </p>
            </div>
            
            <div class="profile-card">
                <h3 class="section-title">Interests</h3>
                <div class="tags-container">
                    <?php 
                    if(!empty($interests)){
                        foreach($interests as $interest){
                            echo '<span class="tag">' . htmlspecialchars($interest) . '</span>';
                        }
                    } else {
                        echo '<p class="about-text">No interests added yet. <a href="editProfile.php">Add interests</a></p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
