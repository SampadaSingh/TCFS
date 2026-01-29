<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../algorithms/tripRecommendation.php';

try {
    // Validate input
    if (!isset($_GET['user_id'])) {
        throw new Exception('user_id parameter is required');
    }

    $userId = (int)$_GET['user_id'];
    $destination = $_GET['destination'] ?? '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $minScore = isset($_GET['min_score']) ? (int)$_GET['min_score'] : 60;

    // Validate user exists
    $userCheck = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $userCheck->bind_param("i", $userId);
    $userCheck->execute();
    if (!$userCheck->get_result()->fetch_assoc()) {
        throw new Exception('User not found');
    }

    // Get recommendations
    $recommendations = getPersonalizedTripRecommendations(
        $conn,
        $userId,
        $destination,
        $limit,
        $minScore
    );

    // Return response
    echo json_encode([
        'success' => true,
        'user_id' => $userId,
        'recommendation_count' => count($recommendations),
        'recommendations' => $recommendations
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
