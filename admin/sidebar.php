<div class="sidebar">
    <div class="logo">
        <h2>TCFS Admin</h2>
    </div>
    <ul class="nav-menu">
        <li class="nav-item">
            <a href="adminDashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'adminDashboard.php' ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="manageUsers.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manageUsers.php' ? 'active' : ''; ?>">
                <i class="bi bi-people-fill"></i>
                <span>Manage Users</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="manageTrips.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manageTrips.php' ? 'active' : ''; ?>">
                <i class="bi bi-airplane"></i>
                <span>Manage Trips</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="adminSettings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'adminSettings.php' ? 'active' : ''; ?>">
                <i class="bi bi-gear-fill"></i>
                <span>Settings</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="../auth/logout.php" class="nav-link">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</div>
