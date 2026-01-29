<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
   
<style>
    .sidebar {
        height: 100vh;
        background: #ffffff;
        color: #333;
        position: fixed;
        left: 0;
        top: 0;
        width: 250px;
        overflow-y: auto;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border-right: 1px solid #f0f0f0;
    }
    
    .sidebar .brand {
        padding: 20px;
        font-size: 20px;
        font-weight: 700;
        border-bottom: 2px solid #f5f5f5;
        text-align: center;
        color: #57C785;
    }
    
    .sidebar .nav-item {
        list-style: none;
    }
    
    .sidebar .nav-link {
        color: #666;
        padding: 15px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
        margin: 5px 0;
    }
    
    .sidebar .nav-link:hover {
        background-color: #E8F4F8;
        border-left-color: #57C785;
        color: #57C785;
    }
    
    .sidebar .nav-link.active {
        background: linear-gradient(135deg, #2A7B9B 0%, #57C785 50%, #EDDD53 100%);
        color: white;
        border-left-color: #2A7B9B;
        font-weight: 600;
        border-radius: 15px;
    }
    
    .sidebar .nav-link.active:hover {
        background: linear-gradient(135deg, #2A7B9B 0%, #57C785 50%, #EDDD53 100%);
        color: white;
    }
    
    .sidebar .nav-link i {
        font-size: 18px;
    }
    
    .sidebar .logout-section {
        margin-top: auto;
        border-top: 2px solid #f5f5f5;
        padding-top: 10px;
    }
</style>

<aside class="sidebar">
    <div class="brand">
        <i class="bi bi-globe"></i> TCFS
    </div>
    <ul class="nav-menu" style="list-style: none; padding: 0; margin: 0;">
        <li class="nav-item">
            <a href="userDashboard.php" class="nav-link <?php echo ($current_page == 'userDashboard.php') ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="profile.php" class="nav-link <?php echo ($current_page == 'profile.php' || $current_page == 'editProfile.php') ? 'active' : ''; ?>">
                <i class="bi bi-person"></i> Profile
            </a>
        </li>
        <li class="nav-item">
            <a href="createTrip.php" class="nav-link <?php echo ($current_page == 'createTrip.php') ? 'active' : ''; ?>">
                <i class="bi bi-plus-circle"></i> Create Trip
            </a>
        </li>
        <li class="nav-item">
            <a href="myTrips.php" class="nav-link <?php echo ($current_page == 'myTrips.php') ? 'active' : ''; ?>">
                <i class="bi bi-calendar3"></i> My Trips
            </a>
        </li>
        <li class="nav-item">
            <a href="discoverTrips.php" class="nav-link <?php echo ($current_page == 'discoverTrips.php') ? 'active' : ''; ?>">
                <i class="bi bi-compass"></i> Discover Trips
            </a>
        </li>
        <li class="nav-item">
            <a href="recommendations.php" class="nav-link <?php echo ($current_page == 'recommendations.php') ? 'active' : ''; ?>">
                <i class="bi bi-lightbulb"></i> Recommendations
            </a>
        </li>
        <li class="nav-item">
            <a href="tripApplications.php" class="nav-link <?php echo ($current_page == 'tripApplications.php') ? 'active' : ''; ?>">
                <i class="bi bi-inbox"></i> My Applications
            </a>
        </li>
    </ul>
    <div class="logout-section">
        <ul class="nav-menu" style="list-style: none; padding: 0; margin: 0;">
            <li class="nav-item">
                <a href="../auth/logout.php" class="nav-link">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</aside>
