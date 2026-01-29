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
        $trip = $conn->query("SELECT t.*, u.name as host_name, u.email as host_email FROM trips t JOIN users u ON t.host_id = u.id WHERE t.id = $id")->fetch_assoc();
        if($trip) {
            echo json_encode(['success' => true, 'trip' => $trip]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Trip not found']);
        }
        break;

    case 'update':
        $id = intval($_POST['trip_id']);
        $name = $conn->real_escape_string($_POST['name']);
        $destination = $conn->real_escape_string($_POST['destination']);
        $description = $conn->real_escape_string($_POST['description']);
        $start_date = $conn->real_escape_string($_POST['start_date']);
        $end_date = $conn->real_escape_string($_POST['end_date']);
        $budget_min = intval($_POST['budget_min']);
        $budget_max = intval($_POST['budget_max']);
        $status = $conn->real_escape_string($_POST['status']);

        $sql = "UPDATE trips SET trip_name = '$name', destination = '$destination', description = '$description', 
                start_date = '$start_date', end_date = '$end_date', budget_min = $budget_min, 
                budget_max = $budget_max, status = '$status' WHERE id = $id";
        
        if($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Trip updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update trip: ' . $conn->error]);
        }
        break;

    case 'delete':
        $id = intval($_POST['id']);
        
        if($conn->query("DELETE FROM trips WHERE id = $id")) {
            echo json_encode(['success' => true, 'message' => 'Trip deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete trip: ' . $conn->error]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
