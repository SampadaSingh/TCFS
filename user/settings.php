<?php
session_start();
require "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$user_query = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user = $user_query->get_result()->fetch_assoc();

if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password=? WHERE id=?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("si", $hashed_password, $user_id);

            if ($stmt->execute()) {
                $success = "Password changed successfully!";
            } else {
                $error = "Error changing password.";
            }
        } else {
            $error = "New passwords do not match.";
        }
    } else {
        $error = "Current password is incorrect.";
    }
}

if (isset($_POST['delete_account'])) {
    $confirm_delete = $_POST['confirm_delete'];
    if ($confirm_delete === 'DELETE') {
        $delete_query = "DELETE FROM users WHERE id=?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            session_destroy();
            header("Location: ../index.php?deleted=1");
            exit;
        }
    } else {
        $delete_error = "Please type DELETE to confirm account deletion.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Travel Companion Finder</title>
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

        .settings-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 30px;
        }

        .settings-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 12px rgba(234, 88, 12, 0.06);
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #57C785;
        }

        .section-description {
            color: #999;
            font-size: 14px;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e5e5;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #57C785;
            box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.1);
        }

        .btn-primary {
            padding: 12px 30px;
            background: linear-gradient(135deg, #2A7B9B 0%, #57C785 50%, #EDDD53 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(234, 88, 12, 0.3);
        }

        .btn-danger {
            padding: 12px 30px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        .toggle-setting {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f5f5f5;
        }

        .toggle-setting:last-child {
            border-bottom: none;
        }

        .toggle-info h4 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .toggle-info p {
            font-size: 13px;
            color: #999;
            margin: 0;
        }

        .toggle-switch {
            position: relative;
            width: 50px;
            height: 26px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: #57C785;
        }

        input:checked+.slider:before {
            transform: translateX(24px);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .danger-zone {
            background: #fff5f5;
            border: 2px solid #ffd6d6;
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
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="settings-container">
            <h1 class="page-title">Settings</h1>

            <!-- Account Settings -->
            <div class="settings-card">
                <h3 class="section-title">
                    <i class="bi bi-person-circle"></i> Account Information
                </h3>
                <p class="section-description">Manage your account details and preferences</p>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Account Type</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['role']); ?>" readonly>
                </div>
            </div>

            <!-- Password Settings -->
            <div class="settings-card">
                <h3 class="section-title">
                    <i class="bi bi-shield-lock"></i> Security
                </h3>
                <p class="section-description">Keep your account secure by updating your password regularly</p>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required>
                        <small class="text-danger"></small>
                    </div>

                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        <small class="text-danger"></small>
                    </div>


                    <button type="submit" name="change_password" class="btn-primary">
                        <i class="bi bi-key"></i> Change Password
                    </button>
                </form>
            </div>

            <!-- Notification Settings -->
            <div class="settings-card">
                <h3 class="section-title">
                    <i class="bi bi-bell"></i> Notifications
                </h3>
                <p class="section-description">Choose what updates you want to receive</p>

                <div class="toggle-setting">
                    <div class="toggle-info">
                        <h4>Email Notifications</h4>
                        <p>Receive updates about your trips and buddy requests</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="slider"></span>
                    </label>
                </div>



                <div class="toggle-setting">
                    <div class="toggle-info">
                        <h4>Buddy Suggestions</h4>
                        <p>Receive recommendations for potential travel companions</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="slider"></span>
                    </label>
                </div>

            </div>

            <!-- Privacy Settings -->
            <div class="settings-card">
                <h3 class="section-title">
                    <i class="bi bi-eye"></i> Privacy
                </h3>
                <p class="section-description">Control who can see your profile and activity</p>

                <div class="toggle-setting">
                    <div class="toggle-info">
                        <h4>Profile Visibility</h4>
                        <p>Make your profile visible to other travelers</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="toggle-setting">
                    <div class="toggle-info">
                        <h4>Show Travel History</h4>
                        <p>Display your past trips on your profile</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="settings-card danger-zone">
                <h3 class="section-title">
                    <i class="bi bi-exclamation-triangle"></i> Danger Zone
                </h3>
                <p class="section-description">Irreversible actions - proceed with caution</p>

                <?php if (isset($delete_error)): ?>
                    <div class="alert alert-danger"><?php echo $delete_error; ?></div>
                <?php endif; ?>

                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
                    <div class="form-group">
                        <label>Type <strong>DELETE</strong> to confirm account deletion</label>
                        <input type="text" name="confirm_delete" class="form-control" placeholder="DELETE" required>
                    </div>

                    <button type="submit" name="delete_account" class="btn-danger">
                        <i class="bi bi-trash"></i> Delete Account
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<script>
    const newPassword = document.getElementById("new_password");
    const confirmPassword = document.getElementById("confirm_password");

    const passwordPattern = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/;

    function showError(input, message) {
        input.nextElementSibling.textContent = message;
    }

    function clearError(input) {
        input.nextElementSibling.textContent = "";
    }

    function validatePassword() {
        if (!passwordPattern.test(newPassword.value)) {
            showError(
                newPassword,
                "Minimum 8 characters with a letter, number and special character."
            );
            return false;
        }
        clearError(newPassword);
        return true;
    }

    function validateConfirmPassword() {
        if (confirmPassword.value !== newPassword.value) {
            showError(confirmPassword, "Passwords do not match.");
            return false;
        }
        clearError(confirmPassword);
        return true;
    }

    newPassword.addEventListener("blur", validatePassword);
    confirmPassword.addEventListener("blur", validateConfirmPassword);

    document.querySelector("form").addEventListener("submit", function(e) {
        if (!validatePassword() || !validateConfirmPassword()) {
            e.preventDefault();
        }
    });
</script>