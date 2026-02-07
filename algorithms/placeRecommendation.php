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

function getCompatibleUsersPreferredDestinations($conn, $compatibleUserIds) {
    if (empty($compatibleUserIds)) return [];

    $placeholders = implode(',', array_fill(0, count($compatibleUserIds), '?'));
    $types = str_repeat('i', count($compatibleUserIds));

    $query = "
        SELECT up.user_id, u.name, up.preferred_destination
        FROM user_preferences up
        JOIN users u ON up.user_id = u.id
        WHERE up.user_id IN ($placeholders)
          AND up.preferred_destination IS NOT NULL
          AND TRIM(up.preferred_destination) <> ''
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$compatibleUserIds);
    $stmt->execute();
    $result = $stmt->get_result();

    $destinations = [];

    while ($row = $result->fetch_assoc()) {
        $prefs = explode(',', $row['preferred_destination']);
        foreach ($prefs as $pref) {
            $dest = strtolower(trim($pref));
            if ($dest === '') continue;

            if (!isset($destinations[$dest])) {
                $destinations[$dest] = [
                    'destination' => $pref,
                    'visited_by' => []
                ];
            }
            $destinations[$dest]['visited_by'][] = $row['name'];
        }
    }

    return $destinations;
}


function recommendPlacesForUser($conn, $currentUserId, $limit = 10, $minCompatibility = 40) {
    $compatibleUsers = findCompatibleUsers($conn, $currentUserId, $minCompatibility);

    if (empty($compatibleUsers)) {
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
            $recommendations[] = [
                'destination' => $row['destination'],
                'score' => 50,
                'preferred_by' => [],
                'reason' => 'Popular destination'
            ];
        }
        return $recommendations;
    }

    $destinationScores = [];
    foreach ($compatibleUsers as $user) {
        $userId = $user['id'];
        $userRow = $conn->query("SELECT preferred_destination FROM user_preferences WHERE user_id = $userId")->fetch_assoc();
        if (!$userRow || empty($userRow['preferred_destination'])) continue;

        $preferredDestinations = array_map('trim', explode(',', $userRow['preferred_destination']));
        foreach ($preferredDestinations as $dest) {
            $destLower = strtolower($dest);
            if (!isset($destinationScores[$destLower])) {
                $destinationScores[$destLower] = [
                    'destination' => $dest,
                    'score' => 0,
                    'preferred_by' => []
                ];
            }
            $destinationScores[$destLower]['score'] += 10; 
            $destinationScores[$destLower]['preferred_by'][] = $user['name'];
        }
    }

    $currentUserVisited = getUserVisitedDestinations($conn, $currentUserId);
    foreach ($currentUserVisited as $visited) {
        $visitedLower = strtolower($visited);
        if (isset($destinationScores[$visitedLower])) {
            unset($destinationScores[$visitedLower]);
        }
    }

    $recommendations = [];
    foreach ($destinationScores as $data) {
        $count = count($data['preferred_by']);
        $recommendations[] = [
            'destination' => $data['destination'],
            'score' => $data['score'],
            'preferred_by' => array_slice($data['preferred_by'], 0, 3), 
            'reason' => sprintf(
                'Preferred by %d compatible traveler%s',
                $count,
                $count > 1 ? 's' : ''
            )
        ];
    }

    usort($recommendations, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });

    return array_slice($recommendations, 0, $limit);
}


?>
