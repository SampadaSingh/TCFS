<?php
require_once __DIR__ . '/compatibility.php';

function getRecommendedTrips($conn, $userId, $minScore = 60, $limit = 10, $destinationFilter = '') {
    $user = $conn->query("SELECT * FROM users WHERE id = $userId")->fetch_assoc();
    $userInterests = getUserInterests($conn, $userId);

    $tripsResult = $conn->query("
        SELECT t.*, u.name AS host_name
        FROM trips t
        JOIN users u ON t.host_id = u.id
        WHERE t.status IN ('pending','confirmed')
        AND t.start_date >= CURDATE()
        AND t.host_id != $userId
        ORDER BY t.start_date ASC
    ");

    $recommended = [];
    while ($trip = $tripsResult->fetch_assoc()) {
        if (!empty($destinationFilter) && stripos($trip['destination'], $destinationFilter) === false) continue;

        if (!isTripEligible($user, $trip, $conn)) continue;

        $hostInterests = getUserInterests($conn, $trip['host_id']);
        $score = calculateUserTripCompatibility($user, $trip, $userInterests, $hostInterests);

        if ($score >= $minScore) {
            $trip['compatibility_score'] = $score;
            $recommended[] = $trip;
        }

        if (count($recommended) >= $limit) break;
    }

    usort($recommended, fn($a, $b) => $b['compatibility_score'] <=> $a['compatibility_score']);
    return $recommended;
}

function getPersonalizedTripRecommendations($conn, $userId, $userDestination = '', $limit = 10, $minScore = 60) {
    return getRecommendedTrips($conn, $userId, $minScore, $limit, $userDestination);
}
?>