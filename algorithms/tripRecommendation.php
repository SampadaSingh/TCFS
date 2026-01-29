<?php

require_once 'compatibility.php';

function scoreDestinationMatch($userDestination, $tripDestination) {
    $userDest = strtolower(trim($userDestination ?? ''));
    $tripDest = strtolower(trim($tripDestination ?? ''));

    // Exact city match
    if (!empty($userDest) && $userDest === $tripDest) {
        return 30;
    }

    

    return 0;
}
function scoreDateOverlap($userAvailStart, $userAvailEnd, $tripStart, $tripEnd) {
    try {
        $userStart = new DateTime($userAvailStart);
        $userEnd = new DateTime($userAvailEnd);
        $tripStart = new DateTime($tripStart);
        $tripEnd = new DateTime($tripEnd);

        // Calculate overlap period
        $overlapStart = max($userStart, $tripStart);
        $overlapEnd = min($userEnd, $tripEnd);

        // No overlap
        if ($overlapStart > $overlapEnd) {
            return 0;
        }

        // Calculate overlapping days and trip duration
        $overlappingDays = $overlapStart->diff($overlapEnd)->days + 1;
        $tripDuration = $tripStart->diff($tripEnd)->days + 1;

        if ($tripDuration === 0) {
            return 0;
        }

        $score = ($overlappingDays / $tripDuration) * 25;
        return min($score, 25);
    } catch (Exception $e) {
        return 0;
    }
}


function scoreSharedInterests($userInterests, $tripInterests) {
    if (empty($userInterests) || empty($tripInterests)) {
        return 0;
    }

    $userInterests = array_map('strtolower', $userInterests);
    $tripInterests = array_map('strtolower', $tripInterests);

    $sharedCount = count(array_intersect($userInterests, $tripInterests));
    $totalUserInterests = count($userInterests);

    if ($totalUserInterests === 0) {
        return 0;
    }

    $score = ($sharedCount / $totalUserInterests) * 20;
    return min($score, 20);
}

function scoreTravelStyleMatch($userTravelStyle, $tripTravelStyle) {
    if (empty($userTravelStyle) || empty($tripTravelStyle)) {
        return 0;
    }

    $userStyle = strtolower(trim($userTravelStyle));
    $tripStyle = strtolower(trim($tripTravelStyle));

    return ($userStyle === $tripStyle) ? 10 : 0;
}

function scoreBudgetCompatibility($userMinBudget, $userMaxBudget, $tripBudgetMin, $tripBudgetMax) {
    if (empty($tripBudgetMin) || empty($tripBudgetMax)) {
        return 0;
    }

    // Full compatibility: trip budget falls within user's budget range
    if ($tripBudgetMin >= $userMinBudget && $tripBudgetMax <= $userMaxBudget) {
        return 10;
    }

    // Partial overlap: ranges overlap
    if ($tripBudgetMin <= $userMaxBudget && $tripBudgetMax >= $userMinBudget) {
        return 5;
    }

    return 0;
}

function scoreCompanionPreference($hostAge, $userPreferredAgeMin, $userPreferredAgeMax, $hostTravelStyle, $userTravelStyle) {
    $score = 0;

    // Age preference match (+3)
    if (!empty($hostAge) && !empty($userPreferredAgeMin) && !empty($userPreferredAgeMax)) {
        if ($hostAge >= $userPreferredAgeMin && $hostAge <= $userPreferredAgeMax) {
            $score += 3;
        }
    }

    // Travel style match (+2)
    if (!empty($hostTravelStyle) && !empty($userTravelStyle)) {
        if (strtolower(trim($hostTravelStyle)) === strtolower(trim($userTravelStyle))) {
            $score += 2;
        }
    }

    return min($score, 5);
}

function calculateAge($dob) {
    try {
        $birthDate = new DateTime($dob);
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;
        return $age;
    } catch (Exception $e) {
        return 0;
    }
}

function parseTripInterests($tripStyle) {
    if (empty($tripStyle)) {
        return [];
    }

    // Try to parse as JSON first
    $decoded = json_decode($tripStyle, true);
    if (is_array($decoded)) {
        return array_map('strtolower', $decoded);
    }

    // Parse as comma-separated
    $interests = array_map('trim', explode(',', $tripStyle));
    return array_filter(array_map('strtolower', $interests));
}

function getUserPreferencesFromDB($conn, $userId) {
    $query = "
        SELECT up.*, u.available_from, u.available_to, u.travel_mode, u.dob
        FROM user_preferences up
        LEFT JOIN users u ON up.user_id = u.id
        WHERE up.user_id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $preferences = $result->fetch_assoc();
    
    // If no preferences set, create default ones
    if (!$preferences) {
        $preferences = [
            'user_id' => $userId,
            'age_min' => 18,
            'age_max' => 80,
            'preferred_gender' => 'Any',
            'budget_min' => 0,
            'budget_max' => 100000,
            'travel_mode' => '',
            'available_from' => '',
            'available_to' => ''
        ];
    }

    return $preferences;
}

function getUserInterestsFromDB($conn, $userId) {
    $query = "
        SELECT i.interest_name
        FROM user_interests ui
        JOIN interests i ON ui.interest_id = i.id
        WHERE ui.user_id = ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $interests = [];
    while ($row = $result->fetch_assoc()) {
        $interests[] = $row['interest_name'];
    }

    return $interests;
}

function getEligibleTripsForRecommendation($conn, $userId, $userDestination = '') {
    $query = "
        SELECT t.*, 
               (SELECT COUNT(*) FROM trip_applications WHERE trip_id = t.id AND status = 'accepted') as current_participants,
               u.dob as host_dob,
               u.travel_mode as host_travel_style,
               u.name as host_name,
               u.email as host_email
        FROM trips t
        JOIN users u ON t.host_id = u.id
        WHERE t.host_id != ?
        AND t.status IN ('pending', 'confirmed')
        AND t.start_date >= CURDATE()
        AND t.group_size_max IS NOT NULL
        AND (
            SELECT COUNT(*) FROM trip_applications 
            WHERE trip_id = t.id AND status = 'accepted'
        ) < t.group_size_max
        AND t.id NOT IN (
            SELECT trip_id FROM trip_applications 
            WHERE user_id = ?
        )
    ";

    $params = [$userId, $userId];
    $types = "ii";

    // Filter by destination if provided
    if (!empty($userDestination)) {
        $query .= " AND (t.destination LIKE ?)";
        $search = "%$userDestination%";
        $params[] = $search;
        $params[] = $search;
        $types .= "ss";
    }

    $query .= " ORDER BY t.start_date ASC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->execute();

    $trips = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $trips[] = $row;
    }

    return $trips;
}


function calculateTripCompatibilityScore($user, $trip, $userInterests) {
    $preferences = $user['preferences'];
    $score = 0;

    // 1. Destination match (max 30)
    $destScore = scoreDestinationMatch(
        $user['preferred_destination'] ?? '',
        $trip['destination']
    );
    $score += $destScore;

    // 2. Date overlap (max 25)
    $availFrom = $preferences['available_from'] ?? '';
    $availTo = $preferences['available_to'] ?? '';
    
    if (!empty($availFrom) && !empty($availTo)) {
        $dateScore = scoreDateOverlap(
            $availFrom,
            $availTo,
            $trip['start_date'],
            $trip['end_date']
        );
        $score += $dateScore;
    }

    // 3. Shared interests (max 20)
    $tripInterests = parseTripInterests($trip['trip_style'] ?? $trip['preferences'] ?? '');
    $interestScore = scoreSharedInterests($userInterests, $tripInterests);
    $score += $interestScore;

    // 4. Travel style match (max 10)
    $styleScore = scoreTravelStyleMatch(
        $preferences['travel_mode'] ?? '',
        $trip['travel_mode'] ?? ''
    );
    $score += $styleScore;

    // 5. Budget compatibility (max 10)
    $budgetScore = scoreBudgetCompatibility(
        $preferences['budget_min'] ?? 0,
        $preferences['budget_max'] ?? 100000,
        $trip['budget_min'] ?? 0,
        $trip['budget_max'] ?? 0
    );
    $score += $budgetScore;

    // 6. Companion preference (max 5)
    $hostAge = calculateAge($trip['host_dob']);
    $companionScore = scoreCompanionPreference(
        $hostAge,
        $preferences['age_min'] ?? 18,
        $preferences['age_max'] ?? 80,
        $trip['host_travel_style'] ?? '',
        $preferences['travel_mode'] ?? ''
    );
    $score += $companionScore;

    return min($score, 100);
}

function getPersonalizedTripRecommendations($conn, $userId, $userDestination = '', $limit = 10, $minScore = 60) {
    // Get user data
    $userQuery = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        return [];
    }

    // Get user preferences and interests
    $preferences = getUserPreferencesFromDB($conn, $userId);
    $userInterests = getUserInterestsFromDB($conn, $userId);

    // Get eligible trips
    $eligibleTrips = getEligibleTripsForRecommendation($conn, $userId, $userDestination);

    // Calculate compatibility scores for each trip
    $recommendations = [];
    foreach ($eligibleTrips as $trip) {
        $userData = array_merge($user, [
            'preferences' => $preferences,
            'preferred_destination' => $userDestination
        ]);

        $compatibilityScore = calculateTripCompatibilityScore($userData, $trip, $userInterests);

        // Only include trips with score >= minScore
        if ($compatibilityScore >= $minScore) {
            $recommendations[] = [
                'trip_id' => $trip['id'],
                'trip_name' => $trip['name'],
                'destination' => $trip['destination'],
                'start_date' => $trip['start_date'],
                'end_date' => $trip['end_date'],
                'budget_min' => $trip['budget_min'],
                'budget_max' => $trip['budget_max'],
                'travel_mode' => $trip['travel_mode'],
                'trip_style' => $trip['trip_style'],
                'group_size_max' => $trip['group_size_max'],
                'current_participants' => $trip['current_participants'],
                'available_spots' => $trip['group_size_max'] - $trip['current_participants'],
                'host_id' => $trip['host_id'],
                'host_name' => $trip['host_name'],
                'host_email' => $trip['host_email'],
                'compatibility_score' => round($compatibilityScore, 2),
                'description' => $trip['description']
            ];
        }
    }

    // Sort by compatibility score descending
    usort($recommendations, function ($a, $b) {
        return $b['compatibility_score'] <=> $a['compatibility_score'];
    });

    // Return top N recommendations
    return array_slice($recommendations, 0, $limit);
}


function getTripScoreBreakdown($user, $trip, $userInterests) {
    $preferences = $user['preferences'];
    $breakdown = [];

    // Destination match
    $destScore = scoreDestinationMatch(
        $user['preferred_destination'] ?? '',
        $trip['destination']
    );
    $breakdown['destination_match'] = [
        'score' => $destScore,
        'max' => 30,
        'label' => 'Destination Match'
    ];

    // Date overlap
    $availFrom = $preferences['available_from'] ?? '';
    $availTo = $preferences['available_to'] ?? '';
    $dateScore = 0;
    if (!empty($availFrom) && !empty($availTo)) {
        $dateScore = scoreDateOverlap(
            $availFrom,
            $availTo,
            $trip['start_date'],
            $trip['end_date']
        );
    }
    $breakdown['date_overlap'] = [
        'score' => $dateScore,
        'max' => 25,
        'label' => 'Date Overlap'
    ];

    // Shared interests
    $tripInterests = parseTripInterests($trip['trip_style'] ?? $trip['preferences'] ?? '');
    $interestScore = scoreSharedInterests($userInterests, $tripInterests);
    $breakdown['shared_interests'] = [
        'score' => $interestScore,
        'max' => 20,
        'label' => 'Shared Interests'
    ];

    // Travel style
    $styleScore = scoreTravelStyleMatch(
        $preferences['travel_mode'] ?? '',
        $trip['travel_mode'] ?? ''
    );
    $breakdown['travel_style_match'] = [
        'score' => $styleScore,
        'max' => 10,
        'label' => 'Travel Style Match'
    ];

    // Budget compatibility
    $budgetScore = scoreBudgetCompatibility(
        $preferences['budget_min'] ?? 0,
        $preferences['budget_max'] ?? 100000,
        $trip['budget_min'] ?? 0,
        $trip['budget_max'] ?? 0
    );
    $breakdown['budget_compatibility'] = [
        'score' => $budgetScore,
        'max' => 10,
        'label' => 'Budget Compatibility'
    ];

    // Companion preference
    $hostAge = calculateAge($trip['host_dob']);
    $companionScore = scoreCompanionPreference(
        $hostAge,
        $preferences['age_min'] ?? 18,
        $preferences['age_max'] ?? 80,
        $trip['host_travel_style'] ?? '',
        $preferences['travel_mode'] ?? ''
    );
    $breakdown['companion_preference'] = [
        'score' => $companionScore,
        'max' => 5,
        'label' => 'Companion Preference'
    ];

    // Total score
    $totalScore = $destScore + $dateScore + $interestScore + $styleScore + $budgetScore + $companionScore;
    $breakdown['total_score'] = [
        'score' => round($totalScore, 2),
        'max' => 100,
        'label' => 'Total Score'
    ];

    return $breakdown;
}


function recommendTripsForUser($conn, $currentUserId, $limit = 10, $minScore = 60) {

    $currentUserTrip = getUserActiveTrip($conn, $currentUserId);

    if (!$currentUserTrip) {
        return [];
    }

    $allTrips = getAllActiveTrips($conn, $currentUserId);
    $currentUserInterests = getUserInterests($conn, $currentUserId);

    $recommendations = [];

    foreach ($allTrips as $trip) {

        // Skip own trips
        if ($trip['host_id'] == $currentUserId) continue;

        $tripHostInterests = getUserInterests($conn, $trip['host_id']);

        $score = weightedMatchScore(
            $currentUserTrip,
            $trip,
            $currentUserInterests,
            $tripHostInterests
        );

        // Only keep meaningful matches
        if ($score >= $minScore) {
            $trip['compatibility_score'] = round($score, 1);
            $recommendations[] = $trip;
        }
    }

    usort($recommendations, fn($a, $b) => $b['compatibility_score'] <=> $a['compatibility_score']);

    return array_slice($recommendations, 0, $limit);
}


function recommendCompanionsForUser($conn, $currentUserId, $limit = 10, $minScore = 50) {

    $currentUserTrip = getUserActiveTrip($conn, $currentUserId);

    if (!$currentUserTrip) return [];

    $currentUserInterests = getUserInterests($conn, $currentUserId);

    $query = "
        SELECT DISTINCT u.id, u.name, u.email, u.bio,
               t.id as trip_id, t.trip_name, t.destination, 
               t.start_date, t.end_date, t.travel_mode
        FROM users u
        JOIN trips t ON u.id = t.host_id
        WHERE u.id != ?
          AND t.status IN ('pending', 'confirmed')
          AND t.start_date >= CURDATE()
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $currentUserId);
    $stmt->execute();
    $result = $stmt->get_result();

    $companions = [];

    while ($row = $result->fetch_assoc()) {

        $companionTrip = [
            'id' => $row['trip_id'],
            'destination' => $row['destination'],
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date'],
            'travel_mode' => $row['travel_mode']
        ];

        $companionInterests = getUserInterests($conn, $row['id']);

        $score = weightedMatchScore(
            $currentUserTrip,
            $companionTrip,
            $currentUserInterests,
            $companionInterests
        );

        // Filter weak users
        if ($score >= $minScore) {
            $row['compatibility_score'] = round($score, 1);
            $row['common_interests'] = array_values(
                array_intersect($currentUserInterests, $companionInterests)
            );
            $companions[] = $row;
        }
    }

    usort($companions, fn($a, $b) => $b['compatibility_score'] <=> $a['compatibility_score']);

    return array_slice($companions, 0, $limit);
}


?>
