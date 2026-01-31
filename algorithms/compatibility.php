<?php

function calculateDateOverlap($start1, $end1, $start2, $end2) {
    $start1 = new DateTime($start1);
    $end1 = new DateTime($end1);
    $start2 = new DateTime($start2);
    $end2 = new DateTime($end2);
    
    $overlapStart = max($start1, $start2);
    $overlapEnd = min($end1, $end2);
    
    if ($overlapStart > $overlapEnd) {
        return 0;
    }
    
    $interval = $overlapStart->diff($overlapEnd);
    return $interval->days + 1;
}

function calculateInterestScore($interests1, $interests2) {
    if (empty($interests1) || empty($interests2)) {
        return 0;
    }
    
    $common = count(array_intersect($interests1, $interests2));
    $total = count(array_unique(array_merge($interests1, $interests2)));
    
    if ($total == 0) {
        return 0;
    }
    
    return ($common / $total) * 100;
}

function calculateDestinationScore($dest1, $dest2) {
    $dest1 = strtolower(trim($dest1));
    $dest2 = strtolower(trim($dest2));

    if (empty($dest1) || empty($dest2)) {
        return 0;
    }

    if ($dest1 === $dest2) {
        return 100;
    }

    similar_text($dest1, $dest2, $percent);

    if ($percent >= 70) {
        return 60; 
    }

    if ($percent >= 40) {
        return 30; 
    }

    return 0;
}

function calculateModeScore($mode1, $mode2) {
    $mode1 = strtolower(trim($mode1));
    $mode2 = strtolower(trim($mode2));
    
    if (empty($mode1) || empty($mode2)) {
        return 50;
    }
    
    if ($mode1 === $mode2) {
        return 100;
    }
    
    return 0;
}

function weightedMatchScore($trip1, $trip2, $interests1, $interests2) {
    $compatibilityThreshold = 60; // Minimum score for trip recommendations

    $dateWeight = 0.35;
    $destinationWeight = 0.30;
    $interestWeight = 0.25;
    $modeWeight = 0.10;
    
    $trip1Duration = (new DateTime($trip1['start_date']))->diff(new DateTime($trip1['end_date']))->days + 1;
    $trip2Duration = (new DateTime($trip2['start_date']))->diff(new DateTime($trip2['end_date']))->days + 1;
    $maxDuration = max($trip1Duration, $trip2Duration);
    
    $overlapDays = calculateDateOverlap(
        $trip1['start_date'], 
        $trip1['end_date'],
        $trip2['start_date'], 
        $trip2['end_date']
    );
    
    $dateScore = $maxDuration > 0 ? ($overlapDays / $maxDuration) * 100 : 0;
    
    $destinationScore = calculateDestinationScore(
        $trip1['destination'],
        $trip2['destination']
    );
    
    $interestScore = calculateInterestScore($interests1, $interests2);
    
    $modeScore = calculateModeScore(
        $trip1['travel_mode'] ?? '',
        $trip2['travel_mode'] ?? ''
    );
    
    $finalScore = (
        $dateScore * $dateWeight +
        $destinationScore * $destinationWeight +
        $interestScore * $interestWeight +
        $modeScore * $modeWeight
    );
    
    if ($finalScore > $compatibilityThreshold) {
        return round($finalScore, 2);
    }
    return 0; 

}

function getUserTrips($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT t.* FROM trips t
        WHERE t.host_id = ? 
        OR t.id IN (
            SELECT trip_id FROM trip_applications 
            WHERE user_id = ? AND status = 'accepted'
        )
        ORDER BY t.start_date DESC
    ");
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $trips = [];
    while ($row = $result->fetch_assoc()) {
        $trips[] = $row;
    }
    return $trips;
}

function getAllActiveTrips($conn, $excludeUserId = null) {
    $query = "
        SELECT t.*, u.name as host_name, u.email as host_email 
        FROM trips t
        JOIN users u ON t.host_id = u.id
        WHERE t.status IN ('pending', 'confirmed') 
        AND t.start_date >= CURDATE()
    ";
    
    if ($excludeUserId) {
        $query .= " AND t.host_id != $excludeUserId";
    }
    
    $query .= " ORDER BY t.start_date ASC";
    
    $result = $conn->query($query);
    $trips = [];
    while ($row = $result->fetch_assoc()) {
        $trips[] = $row;
    }
    return $trips;
}

function getUserInterests($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT i.interest_name 
        FROM user_interests ui
        JOIN interests i ON ui.interest_id = i.id
        WHERE ui.user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $interests = [];
    while ($row = $result->fetch_assoc()) {
        $interests[] = $row['interest_name'];
    }
    return $interests;
}

function getUserActiveTrip($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT t.* FROM trips t
        WHERE t.host_id = ? 
        AND t.status IN ('pending', 'confirmed')
        AND t.start_date >= CURDATE()
        ORDER BY t.start_date ASC
        LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

?>
