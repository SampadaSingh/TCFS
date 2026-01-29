<?php
require_once 'compatibility.php';

function getUserVisitedDestinations($conn, $userId) {
    $query = "
        SELECT DISTINCT t.destination
        FROM trips t
        WHERE t.host_id = ? OR t.id IN (
            SELECT trip_id FROM trip_applications 
            WHERE user_id = ? AND status = 'accepted'
        )
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $destinations = [];
    while ($row = $result->fetch_assoc()) {
        $destinations[] = strtolower(trim($row['destination']));
    }
    return $destinations;
}

function findCompatibleUsers($conn, $currentUserId, $minCompatibility = 70) {
    $currentUserTrips = getUserTrips($conn, $currentUserId);
    
    if (empty($currentUserTrips)) {
        return [];
    }
    
    $currentUserInterests = getUserInterests($conn, $currentUserId);
    $currentUserTrip = $currentUserTrips[0];
    
    $query = "
        SELECT DISTINCT u.id, u.name, t.id as trip_id, t.destination, t.start_date, t.end_date, t.travel_mode
        FROM users u
        JOIN trips t ON u.id = t.host_id
        WHERE u.id != ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $currentUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $compatibleUsers = [];
    
    while ($row = $result->fetch_assoc()) {
        $otherUserTrip = [
            'destination' => $row['destination'],
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date'],
            'travel_mode' => $row['travel_mode']
        ];
        
        $otherUserInterests = getUserInterests($conn, $row['id']);
        
        $score = weightedMatchScore(
            $currentUserTrip,
            $otherUserTrip,
            $currentUserInterests,
            $otherUserInterests
        );
        
        if ($score >= $minCompatibility) {
            if (!isset($compatibleUsers[$row['id']])) {
                $compatibleUsers[$row['id']] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'compatibility_score' => $score,
                    'destinations' => []
                ];
            }
            
            $compatibleUsers[$row['id']]['destinations'][] = strtolower(trim($row['destination']));
            $compatibleUsers[$row['id']]['compatibility_score'] = max(
                $compatibleUsers[$row['id']]['compatibility_score'],
                $score
            );
        }
    }
    
    return array_values($compatibleUsers);
}

function recommendPlacesForUser($conn, $currentUserId, $limit = 10) {
    $visitedDestinations = getUserVisitedDestinations($conn, $currentUserId);
    $compatibleUsers = findCompatibleUsers($conn, $currentUserId, 70);
    
    if (empty($compatibleUsers)) {
        $query = "
            SELECT destination, COUNT(*) as popularity
            FROM trips
            WHERE status IN ('confirmed', 'completed')
            GROUP BY destination
            ORDER BY popularity DESC
            LIMIT ?
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $recommendations = [];
        while ($row = $result->fetch_assoc()) {
            $dest = strtolower(trim($row['destination']));
            if (!in_array($dest, $visitedDestinations)) {
                $recommendations[] = [
                    'destination' => $row['destination'],
                    'score' => 50,
                    'reason' => 'Popular destination'
                ];
            }
        }
        return array_slice($recommendations, 0, $limit);
    }
    
    $destinationScores = [];
    
    foreach ($compatibleUsers as $user) {
        $compatibilityBoost = $user['compatibility_score'] / 100;
        
        foreach ($user['destinations'] as $destination) {
            if (!in_array($destination, $visitedDestinations)) {
                if (!isset($destinationScores[$destination])) {
                    $destinationScores[$destination] = [
                        'score' => 0,
                        'count' => 0,
                        'compatible_users' => []
                    ];
                }
                
                $destinationScores[$destination]['score'] += (10 * $compatibilityBoost);
                $destinationScores[$destination]['count']++;
                $destinationScores[$destination]['compatible_users'][] = $user['name'];
            }
        }
    }
    
    $recommendations = [];
    foreach ($destinationScores as $destination => $data) {
        
        $finalScore = $data['score'] + ($data['count'] * 5);
        
        $recommendations[] = [
            'destination' => ucfirst($destination),
            'score' => round($finalScore, 2),
            'visited_by_count' => $data['count'],
            'compatible_users' => array_slice($data['compatible_users'], 0, 3),
            'reason' => sprintf(
                'Visited by %d compatible traveler%s',
                $data['count'],
                $data['count'] > 1 ? 's' : ''
            )
        ];
    }
    
    usort($recommendations, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });
    
    return array_slice($recommendations, 0, $limit);
}

?>
