<?php
session_start();
require "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$trip_id = (int)($_GET['id'] ?? 0);

if ($trip_id <= 0) {
    header('Location: myTrips.php?error=Invalid trip ID');
    exit;
}

// Verify trip belongs to user before deleting
$stmt = $conn->prepare("SELECT id FROM trips WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $trip_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

if ($result->num_rows === 0) {
    header('Location: myTrips.php?error=Trip not found or access denied');
    exit;
}

// Begin transaction
$conn->begin_transaction();

try {
    // Delete trip interests first (foreign key constraint)
    $stmt = $conn->prepare("DELETE FROM trip_interests WHERE trip_id = ?");
    $stmt->bind_param("i", $trip_id);
    $stmt->execute();
    $stmt->close();
    
    // Delete the trip
    $stmt = $conn->prepare("DELETE FROM trips WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $trip_id, $user_id);
    $stmt->execute();
    $stmt->close();
    
    $conn->commit();
    header('Location: myTrips.php?success=Trip deleted successfully');
    exit;
    
} catch (Exception $e) {
    $conn->rollback();
    header('Location: myTrips.php?error=Failed to delete trip. Please try again.');
    exit;
}
?>
