<?php
require_once 'compatibility.php';

function getUserVisitedDestinations($conn, $userId) {
    $query = "
        SELECT DISTINCT t.destination
        FROM trips t
        WHERE (t.host_id = ? OR t.id IN (
            SELECT trip_id FROM trip_applications 
            WHERE user_id = ? AND status = 'accepted'
        ))
        AND t.destination IS NOT NULL
        AND TRIM(t.destination) <> ''
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

function getUserPreferredDestination($conn, $userId) {
    $stmt = $conn->prepare("SELECT interests FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['interests'] : '';
}

function findCompatibleUsers($conn, $currentUserId, $minCompatibility = 40) {
    $currentUserInterests = getUserInterests($conn, $currentUserId);

    $query = "
        SELECT DISTINCT u.id, u.name
        FROM users u
        WHERE u.id != ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $currentUserId);
    $stmt->execute();
    $result = $stmt->get_result();

    $compatibleUsers = [];

    while ($row = $result->fetch_assoc()) {
        $userId = $row['id'];
        $otherUserInterests = getUserInterests($conn, $userId);
        
        if (empty($currentUserInterests) || empty($otherUserInterests)) {
            $interestScore = 50;
        } else {
            $commonInterests = array_intersect($currentUserInterests, $otherUserInterests);
            $totalInterests = count(array_unique(array_merge($currentUserInterests, $otherUserInterests)));
            $interestScore = $totalInterests > 0 ? (count($commonInterests) / $totalInterests) * 100 : 50;
        }
        
        if ($interestScore >= $minCompatibility) {
            $compatibleUsers[] = [
                'id' => $userId,
                'name' => $row['name'],
                'compatibility_score' => $interestScore
            ];
        }
    }

    return $compatibleUsers;
}

function getCompatibleUsersDestinations($conn, $compatibleUserIds) {
    if (empty($compatibleUserIds)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($compatibleUserIds), '?'));
    $query = "
        SELECT DISTINCT t.destination, u.id, u.name
        FROM trips t
        JOIN users u ON t.host_id = u.id
        WHERE u.id IN ($placeholders)
          AND t.destination IS NOT NULL
          AND TRIM(t.destination) <> ''
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param(str_repeat('i', count($compatibleUserIds)), ...$compatibleUserIds);
    $stmt->execute();
    $result = $stmt->get_result();

    $destinations = [];
    while ($row = $result->fetch_assoc()) {
        $dest = strtolower(trim($row['destination']));
        if (!isset($destinations[$dest])) {
            $destinations[$dest] = [
                'destination' => $row['destination'],
                'visited_by' => []
            ];
        }
        $destinations[$dest]['visited_by'][] = $row['name'];
    }

    return $destinations;
}

function recommendPlacesForUser($conn, $currentUserId, $limit = 10) {
    $currentUserVisited = getUserVisitedDestinations($conn, $currentUserId);
    $currentUserPreference = getUserPreferredDestination($conn, $currentUserId);
    $compatibleUsers = findCompatibleUsers($conn, $currentUserId, 40);
    
    $destinationScores = [];
    
    if (!empty($compatibleUsers)) {
        $compatibleUserIds = array_column($compatibleUsers, 'id');
        $compatibleDestinations = getCompatibleUsersDestinations($conn, $compatibleUserIds);
        
        foreach ($compatibleDestinations as $dest => $data) {
            if (!in_array($dest, $currentUserVisited)) {
                if (!isset($destinationScores[$dest])) {
                    $destinationScores[$dest] = [
                        'destination' => $data['destination'],
                        'score' => 0,
                        'visited_by' => $data['visited_by'],
                        'reason' => ''
                    ];
                }
                
                $visitCount = count($data['visited_by']);
                $destinationScores[$dest]['score'] += ($visitCount * 10);
                $destinationScores[$dest]['reason'] = sprintf(
                    'Visited by %d compatible traveler%s',
                    $visitCount,
                    $visitCount > 1 ? 's' : ''
                );
            }
        }
    }
    
    if (empty($destinationScores)) {
        $query = "
            SELECT destination, COUNT(*) as popularity
            FROM trips
            WHERE status IN ('pending', 'confirmed', 'completed')
              AND destination IS NOT NULL
              AND TRIM(destination) <> ''
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
            if ($dest !== '' && !in_array($dest, $currentUserVisited)) {
                $recommendations[] = [
                    'destination' => $row['destination'],
                    'score' => 50,
                    'visited_by' => [],
                    'reason' => 'Popular destination'
                ];
            }
        }
        return array_slice($recommendations, 0, $limit);
    }
    
    $recommendations = [];
    foreach ($destinationScores as $data) {
        $recommendations[] = [
            'destination' => $data['destination'],
            'score' => round($data['score'], 2),
            'visited_by' => array_slice($data['visited_by'], 0, 3),
            'reason' => $data['reason']
        ];
    }
    
    usort($recommendations, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });
    
    return array_slice($recommendations, 0, $limit);
}

?>
