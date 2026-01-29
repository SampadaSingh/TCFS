<?php
session_start();
require "../config/db.php";

// Check if user is logged in
if(!isset($_SESSION['user_id'])){
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch current user data
$user_query = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$current_user = $user_query->get_result()->fetch_assoc();

// Fetch suggested buddies (users excluding current user)
$buddies_query = "SELECT u.*, 
                  TIMESTAMPDIFF(YEAR, u.dob, CURDATE()) as age 
                  FROM users u 
                  WHERE u.id != ? AND u.role = 'User'
                  LIMIT 12";
$stmt = $conn->prepare($buddies_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$buddies_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Travel Buddies - Travel Companion Finder</title>
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
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            color: #999;
            font-size: 14px;
        }
        
        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 12px rgba(234, 88, 12, 0.06);
            margin-bottom: 30px;
        }
        
        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .filter-group label {
            font-size: 13px;
            font-weight: 600;
            color: #666;
        }
        
        .filter-group select {
            padding: 10px;
            border: 2px solid #e5e5e5;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .filter-group select:focus {
            outline: none;
            border-color: #57C785;
        }
        
        .buddies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }
        
        .buddy-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 2px 12px rgba(234, 88, 12, 0.06);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-bottom: 3px solid #57C785;
        }
        
        .buddy-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 32px rgba(234, 88, 12, 0.15);
        }
        
        .buddy-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2A7B9B 0%, #57C785 50%, #EDDD53 100%);
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: bold;
            color: white;
        }
        
        .buddy-name {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        
        .buddy-info {
            color: #999;
            font-size: 13px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .buddy-info span {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .buddy-interests {
            color: #666;
            font-size: 13px;
            margin-bottom: 15px;
            min-height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .match-percentage {
            font-size: 16px;
            font-weight: 700;
            color: #57C785;
            margin-bottom: 15px;
        }
        
        .connect-btn {
            background: linear-gradient(135deg, #2A7B9B 0%, #57C785 50%, #EDDD53 100%);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .connect-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(234, 88, 12, 0.4);
        }
        
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .no-results i {
            font-size: 64px;
            color: #57C785;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            
            .filter-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Find Travel Buddies</h1>
            <p class="page-subtitle">Connect with like-minded travelers for your next adventure</p>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label><i class="bi bi-geo-alt"></i> Destination</label>
                        <select name="destination">
                            <option value="">All Destinations</option>
                            <option value="Asia">Asia</option>
                            <option value="Europe">Europe</option>
                            <option value="Americas">Americas</option>
                            <option value="Africa">Africa</option>
                            <option value="Oceania">Oceania</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="bi bi-gender-ambiguous"></i> Gender</label>
                        <select name="gender">
                            <option value="">Any Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="bi bi-calendar"></i> Age Range</label>
                        <select name="age">
                            <option value="">Any Age</option>
                            <option value="18-25">18-25 years</option>
                            <option value="26-35">26-35 years</option>
                            <option value="36-45">36-45 years</option>
                            <option value="46-60">46-60 years</option>
                            <option value="60+">60+ years</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="bi bi-star"></i> Interests</label>
                        <select name="interest">
                            <option value="">All Interests</option>
                            <option value="Adventure">Adventure</option>
                            <option value="Beach">Beach</option>
                            <option value="Culture">Culture</option>
                            <option value="Food">Food</option>
                            <option value="Hiking">Hiking</option>
                            <option value="Photography">Photography</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Buddies Grid -->
        <?php if($buddies_result->num_rows > 0): ?>
            <div class="buddies-grid">
                <?php 
                $match_scores = [94, 89, 85, 82, 78, 75, 72, 68, 65, 62, 58, 55];
                $interests_list = ['Hiking, Photography', 'Adventure, Culture', 'Beach, Food', 'Culture, Art', 
                                  'Wildlife, Nature', 'Food, Photography', 'Beach, Adventure', 'Hiking, Culture',
                                  'Photography, Art', 'Food, Culture', 'Adventure, Hiking', 'Beach, Wildlife'];
                $counter = 0;
                while($buddy = $buddies_result->fetch_assoc()): 
                ?>
                    <div class="buddy-card">
                        <div class="buddy-avatar">
                            <?php echo strtoupper(substr($buddy['name'], 0, 1)); ?>
                        </div>
                        <div class="buddy-name"><?php echo htmlspecialchars($buddy['name']); ?></div>
                        <div class="buddy-info">
                            <span><i class="bi bi-calendar"></i> <?php echo $buddy['age']; ?> years</span>
                            <span><i class="bi bi-gender-ambiguous"></i> <?php echo htmlspecialchars($buddy['gender']); ?></span>
                        </div>
                        <div class="buddy-interests"><?php echo $interests_list[$counter % count($interests_list)]; ?></div>
                        <div class="match-percentage"><?php echo $match_scores[$counter % count($match_scores)]; ?>% Match</div>
                        <button class="connect-btn" onclick="connectBuddy(<?php echo $buddy['id']; ?>)">
                            <i class="bi bi-person-plus"></i> Connect
                        </button>
                    </div>
                <?php 
                    $counter++;
                endwhile; 
                ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <i class="bi bi-search"></i>
                <h3>No Travel Buddies Found</h3>
                <p>Try adjusting your filters or check back later for new members</p>
            </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function connectBuddy(buddyId) {
            if(confirm('Send connection request to this travel buddy?')) {
                // Here you would implement the connection logic
                alert('Connection request sent! They will be notified.');
            }
        }
    </script>
</body>
</html>
