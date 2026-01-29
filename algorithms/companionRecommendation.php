<?php

require_once 'compatibility.php';

function calculateCompanionScore($userTrip, $companionTrip, $userInterests, $companionInterests) {
    $score = 0;
    
    // 1. Destination compatibility (0-25 points) - More lenient
    $userDest = strtolower(trim($userTrip['destination'] ?? ''));
    $companionDest = strtolower(trim($companionTrip['destination'] ?? ''));
    
    if (!empty($userDest) && !empty($companionDest)) {
        if ($userDest === $companionDest) {
            $score += 25;
        } elseif (strpos($userDest, $companionDest) !== false || strpos($companionDest, $userDest) !== false) {
            $score += 15; // Partial match
        } else {
            $score += 5; // Different but still give some points
        }
    } else {
        $score += 10; // No preference = neutral
    }
    
    // 2. Date overlap compatibility (0-25 points) - More flexible
    $dateScore = calculateDateOverlapScore(
        $userTrip['start_date'] ?? null,
        $userTrip['end_date'] ?? null,
        $companionTrip['start_date'] ?? null,
        $companionTrip['end_date'] ?? null
    );
    $score += $dateScore;
    
    // 3. Shared interests (0-30 points) - Increased weight
    $interestScore = calculateSharedInterestsScore($userInterests, $companionInterests);
    $score += $interestScore;
    
    // 4. Travel mode compatibility (0-20 points) - More generous
    $modeScore = calculateTravelModeScore(
        $userTrip['travel_mode'] ?? '',
        $companionTrip['travel_mode'] ?? ''
    );
    $score += $modeScore;
    
    return min(round($score, 1), 100);
}

/**
 * Calculate date overlap score with more lenient matching
 */
function calculateDateOverlapScore($userStart, $userEnd, $companionStart, $companionEnd) {
    if (empty($userStart) || empty($userEnd) || empty($companionStart) || empty($companionEnd)) {
        return 15; // Give neutral score if dates not available
    }
    
    try {
        $userStartDate = new DateTime($userStart);
        $userEndDate = new DateTime($userEnd);
        $companionStartDate = new DateTime($companionStart);
        $companionEndDate = new DateTime($companionEnd);
        
        // Calculate overlap
        $overlapStart = max($userStartDate, $companionStartDate);
        $overlapEnd = min($userEndDate, $companionEndDate);
        
        if ($overlapStart > $overlapEnd) {
            // No overlap - check if dates are within 30 days of each other
            $daysDiff = min(
                abs($userStartDate->diff($companionStartDate)->days),
                abs($userEndDate->diff($companionEndDate)->days)
            );
            
            if ($daysDiff <= 7) {
                return 20; // Very close dates
            } elseif ($daysDiff <= 14) {
                return 15;
            } elseif ($daysDiff <= 30) {
                return 10;
            } else {
                return 5; // Still give some points
            }
        }
        
        // Calculate percentage of overlap
        $overlapDays = $overlapStart->diff($overlapEnd)->days + 1;
        $userDuration = $userStartDate->diff($userEndDate)->days + 1;
        $companionDuration = $companionStartDate->diff($companionEndDate)->days + 1;
        $maxDuration = max($userDuration, $companionDuration);
        
        $overlapPercent = ($overlapDays / $maxDuration) * 100;
        
        // More generous scoring
        if ($overlapPercent >= 50) {
            return 25;
        } elseif ($overlapPercent >= 25) {
            return 20;
        } elseif ($overlapPercent >= 10) {
            return 15;
        } else {
            return 10;
        }
    } catch (Exception $e) {
        return 15; // Neutral score on error
    }
}

/**
 * Calculate shared interests score with more lenient matching
 */
function calculateSharedInterestsScore($userInterests, $companionInterests) {
    if (empty($userInterests) && empty($companionInterests)) {
        return 15; // Both have no interests - neutral match
    }
    
    if (empty($userInterests) || empty($companionInterests)) {
        return 10; // One has no interests - give some points
    }
    
    $commonInterests = array_intersect($userInterests, $companionInterests);
    $commonCount = count($commonInterests);
    $totalUnique = count(array_unique(array_merge($userInterests, $companionInterests)));
    
    if ($totalUnique == 0) {
        return 15;
    }
    
    // More generous interest matching
    $matchPercent = ($commonCount / $totalUnique) * 100;
    
    if ($commonCount >= 5) {
        return 30;
    } elseif ($commonCount >= 3) {
        return 25;
    } elseif ($commonCount >= 2) {
        return 20;
    } elseif ($commonCount >= 1) {
        return 15;
    } else {
        return 8; // No common interests but still give points
    }
}

/**
 * Calculate travel mode compatibility score
 */
function calculateTravelModeScore($userMode, $companionMode) {
    $userMode = strtolower(trim($userMode));
    $companionMode = strtolower(trim($companionMode));
    
    if (empty($userMode) || empty($companionMode)) {
        return 12; // Neutral if no preference
    }
    
    if ($userMode === $companionMode) {
        return 20;
    }
    
    // Check for compatible modes
    $compatibleModes = [
        'mixed' => ['bike', 'car', 'bus', 'train', 'flight', 'jeep', 'walking'],
        'bike' => ['walking', 'mixed'],
        'car' => ['jeep', 'mixed'],
        'bus' => ['train', 'mixed'],
        'train' => ['bus', 'mixed'],
        'flight' => ['mixed'],
        'walking' => ['bike', 'mixed'],
        'jeep' => ['car', 'mixed']
    ];
    
    if (isset($compatibleModes[$userMode]) && in_array($companionMode, $compatibleModes[$userMode])) {
        return 15;
    }
    
    return 8; // Different modes but still compatible
}

/**
 * Build a trip array from user preferences
 */
function buildTripFromPreferences($preferences, $fallbackTrip = null) {
    $start = !empty($preferences['available_from']) ? $preferences['available_from'] : null;
    $end = !empty($preferences['available_to']) ? $preferences['available_to'] : null;
    
    if ((!$start || !$end) && $fallbackTrip) {
        $start = $fallbackTrip['start_date'] ?? $start;
        $end = $fallbackTrip['end_date'] ?? $end;
    }
    
    return [
        'destination' => $preferences['preferred_destination'] ?? '',
        'start_date' => $start ?? date('Y-m-d'),
        'end_date' => $end ?? date('Y-m-d', strtotime('+7 days')),
        'travel_mode' => $preferences['travel_mode'] ?? ''
    ];
}

/**
 * Get user preferences from database
 */
function getCompanionUserPreferences($conn, $userId) {
    $query = "
        SELECT up.*, u.available_from, u.available_to, u.travel_mode
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
    
    if (!$preferences) {
        // Return default preferences
        $preferences = [
            'user_id' => $userId,
            'preferred_destination' => '',
            'available_from' => '',
            'available_to' => '',
            'travel_mode' => ''
        ];
    }
    
    return $preferences;
}

/**
 * Get user's active trip
 */
function getCompanionUserActiveTrip($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT t.* 
        FROM trips t
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

/**
 * Get user interests
 */
function getCompanionUserInterests($conn, $userId) {
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

/**
 * Main function to get companion recommendations
 * @param mysqli $conn Database connection
 * @param int $userId Current user ID
 * @param int $limit Maximum number of recommendations
 * @param float $minScore Minimum compatibility score (default: 30 for more results)
 * @return array Array of recommended companions with scores
 */
function getCompanionRecommendations($conn, $userId, $limit = 10, $minScore = 30) {
    // Get current user's trip or preferences
    $userTrip = getCompanionUserActiveTrip($conn, $userId);
    $preferences = getCompanionUserPreferences($conn, $userId);
    $userInterests = getCompanionUserInterests($conn, $userId);
    
    // Build reference trip from active trip or preferences
    $referenceTrip = null;
    if ($userTrip) {
        $referenceTrip = [
            'destination' => $userTrip['destination'],
            'start_date' => $userTrip['start_date'],
            'end_date' => $userTrip['end_date'],
            'travel_mode' => $userTrip['travel_mode']
        ];
    } else {
        $referenceTrip = buildTripFromPreferences($preferences);
    }
    
    // Get potential companions (users with trips)
    $query = "
        SELECT DISTINCT 
            u.id, 
            u.name, 
            u.email, 
            u.bio,
            u.gender,
            u.dob,
            t.id as trip_id, 
            t.trip_name, 
            t.destination, 
            t.start_date, 
            t.end_date, 
            t.travel_mode,
            t.trip_style
        FROM users u
        JOIN trips t ON u.id = t.host_id
        WHERE u.id != ?
          AND t.status IN ('pending', 'confirmed')
          AND t.start_date >= CURDATE()
        ORDER BY t.start_date ASC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $companions = [];
    $processedUsers = []; // Track users to avoid duplicates
    
    while ($row = $result->fetch_assoc()) {
        // Skip if we already processed this user
        if (isset($processedUsers[$row['id']])) {
            continue;
        }
        
        $companionTrip = [
            'destination' => $row['destination'],
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date'],
            'travel_mode' => $row['travel_mode']
        ];
        
        $companionInterests = getCompanionUserInterests($conn, $row['id']);
        
        // Calculate compatibility score with lenient algorithm
        $score = calculateCompanionScore(
            $referenceTrip,
            $companionTrip,
            $userInterests,
            $companionInterests
        );
        
        // Only include companions meeting minimum score
        if ($score >= $minScore) {
            $processedUsers[$row['id']] = true;
            
            $companions[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'email' => $row['email'],
                'bio' => $row['bio'],
                'gender' => $row['gender'],
                'dob' => $row['dob'],
                'trip_id' => $row['trip_id'],
                'trip_name' => $row['trip_name'],
                'destination' => $row['destination'],
                'start_date' => $row['start_date'],
                'end_date' => $row['end_date'],
                'travel_mode' => $row['travel_mode'],
                'trip_style' => $row['trip_style'],
                'compatibility_score' => $score,
                'common_interests' => array_values(array_intersect($userInterests, $companionInterests))
            ];
        }
    }
    
    // Sort by compatibility score (highest first)
    usort($companions, function($a, $b) {
        return $b['compatibility_score'] <=> $a['compatibility_score'];
    });
    
    // Return top N companions
    return array_slice($companions, 0, $limit);
}

/**
 * Get companion recommendations with detailed breakdown
 * For debugging and analysis purposes
 */
function getCompanionRecommendationsWithBreakdown($conn, $userId, $limit = 10, $minScore = 30) {
    $recommendations = getCompanionRecommendations($conn, $userId, $limit, $minScore);
    
    // Add breakdown details
    foreach ($recommendations as &$companion) {
        $companion['score_breakdown'] = [
            'destination' => 'Calculated based on location match',
            'date_overlap' => 'Calculated based on date compatibility',
            'shared_interests' => count($companion['common_interests']) . ' common interests',
            'travel_mode' => 'Travel mode compatibility'
        ];
    }
    
    return $recommendations;
}

?>
