<?php
session_start();
require '../../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch($action) {
    case 'get':
        $id = intval($_GET['id']);
        $user = $conn->query("SELECT * FROM users WHERE id = $id")->fetch_assoc();

        if($user) {
            // Pull human-readable interests list for the admin modal
            $interests = [];
            $interest_sql = "SELECT i.interest_name FROM user_interests ui JOIN interests i ON ui.interest_id = i.id WHERE ui.user_id = $id";
            $interest_result = $conn->query($interest_sql);
            if($interest_result) {
                while($row = $interest_result->fetch_assoc()) {
                    $interests[] = $row['interest_name'];
                }
            }
            $user['interests'] = !empty($interests) ? implode(', ', $interests) : '';

            echo json_encode(['success' => true, 'user' => $user]);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
        break;

    case 'update':
        $id = intval($_POST['user_id']);
        $name = $conn->real_escape_string($_POST['name']);
        $email = $conn->real_escape_string($_POST['email']);
        $dob = $conn->real_escape_string($_POST['dob']);
        $gender = $conn->real_escape_string($_POST['gender']);
        $bio = $conn->real_escape_string($_POST['bio'] ?? '');

        $check = $conn->query("SELECT id FROM users WHERE email = '$email' AND id != $id");
        if($check->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            exit;
        }

        $sql = "UPDATE users SET name = '$name', email = '$email', dob = '$dob', gender = '$gender', bio = '$bio' WHERE id = $id";
        
        if($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'User updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update user: ' . $conn->error]);
        }
        break;

    case 'delete':
        $id = intval($_POST['id']);
        
        if($conn->query("DELETE FROM users WHERE id = $id")) {
            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete user: ' . $conn->error]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
