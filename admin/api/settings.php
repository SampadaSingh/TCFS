<?php
session_start();
require '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';

switch($action) {
    case 'update_profile':
        $admin_id = $_POST['admin_id'];
        $name = $conn->real_escape_string($_POST['name']);
        $email = $conn->real_escape_string($_POST['email']);
        $dob = $_POST['dob'];

        $check = $conn->query("SELECT id FROM users WHERE email = '$email' AND id != $admin_id");
        if($check->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            exit;
        }

        $sql = "UPDATE users SET name = '$name', email = '$email', dob = '$dob' WHERE id = $admin_id";
        
        if($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
        }
        break;

    case 'change_password':
        $admin_id = $_POST['admin_id'];
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];

        $admin = $conn->query("SELECT password FROM users WHERE id = $admin_id")->fetch_assoc();
        
        if(!password_verify($current_password, $admin['password'])) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            exit;
        }

        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        if($conn->query("UPDATE users SET password = '$hashed_password' WHERE id = $admin_id")) {
            echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to change password']);
        }
        break;

    case 'cleanup':
        $date_threshold = date('Y-m-d', strtotime('-1 year'));
        $conn->query("DELETE FROM trips WHERE status = 'completed' AND end_date < '$date_threshold'");
        $conn->query("DELETE FROM trip_applications WHERE status = 'rejected' AND applied_date < '$date_threshold'");
        
        echo json_encode(['success' => true, 'message' => 'Old data cleaned up successfully']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
