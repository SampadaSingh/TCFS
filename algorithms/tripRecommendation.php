<?php

require_once __DIR__ . '/compatibility.php';

function scoreDestinationMatch($userDestination, $tripDestination)
{
    if (empty($userDestination) || empty($tripDestination)) return 0;

    $u = strtolower(trim($userDestination));
    $t = strtolower(trim($tripDestination));

    if ($u === $t) return 30;
    if (strpos($t, $u) !== false || strpos($u, $t) !== false) return 15;

    return 0;
}

function scoreDateOverlap($userStart, $userEnd, $tripStart, $tripEnd)
{
    if (empty($userStart) || empty($userEnd)) return 0;

    try {
        $uStart = new DateTime($userStart);
        $uEnd   = new DateTime($userEnd);
        $tStart = new DateTime($tripStart);
        $tEnd   = new DateTime($tripEnd);

        $overlapStart = max($uStart, $tStart);
        $overlapEnd   = min($uEnd, $tEnd);

        if ($overlapStart > $overlapEnd) return 0;

        $overlapDays = $overlapStart->diff($overlapEnd)->days + 1;
        $tripDays    = $tStart->diff($tEnd)->days + 1;

        if ($tripDays <= 0) return 0;

        return min(($overlapDays / $tripDays) * 25, 25);
    } catch (Exception $e) {
        return 0;
    }
}

function scoreSharedInterests($userInterests, $tripInterests)
{
    if (empty($userInterests) || empty($tripInterests)) return 0;

    $u = array_map('strtolower', $userInterests);
    $t = array_map('strtolower', $tripInterests);

    $common = count(array_intersect($u, $t));
    if ($common === 0) return 0;

    return min(($common / count($u)) * 20, 20);
}

function scoreTravelMode($userMode, $tripMode)
{
    if (empty($userMode) || empty($tripMode)) return 0;
    return (strtolower($userMode) === strtolower($tripMode)) ? 10 : 0;
}

function scoreBudgetCompatibility($uMin, $uMax, $tMin, $tMax)
{
    if ($tMin === null || $tMax === null) return 0;

    if ($tMin >= $uMin && $tMax <= $uMax) return 10;
    if ($tMin <= $uMax && $tMax >= $uMin) return 5;

    return 0;
}

function scoreCompanionPreference($hostAge, $ageMin, $ageMax, $hostStyle, $userStyle)
{
    $score = 0;

    if ($hostAge >= $ageMin && $hostAge <= $ageMax) {
        $score += 3;
    }

    if (
        !empty($hostStyle) && !empty($userStyle) &&
        strtolower($hostStyle) === strtolower($userStyle)
    ) {
        $score += 2;
    }

    return min($score, 5);
}

function calculateAge($dob)
{
    if (empty($dob)) return 0;
    return (new DateTime())->diff(new DateTime($dob))->y;
}

function parseTripInterests($raw)
{
    if (empty($raw)) return [];

    $json = json_decode($raw, true);
    if (is_array($json)) return array_map('strtolower', $json);

    return array_map('strtolower', array_map('trim', explode(',', $raw)));
}

function matchesDestinationFilter($filter, $destination)
{
    if (empty($filter)) return true;
    if (empty($destination)) return false;

    $f = strtolower(trim($filter));
    $d = strtolower(trim($destination));

    return $d === $f || strpos($d, $f) !== false;
}

function getUserPreferences($conn, $userId)
{
    $stmt = $conn->prepare("
        SELECT u.budget as budget_min, u.budget as budget_max, 
               u.available_from, u.available_to, u.travel_mode, u.dob,
               u.interests as preferred_destination
        FROM users u
        WHERE u.id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();

    if ($data) {
        return [
            'budget_min' => $data['budget_min'] ?? 0,
            'budget_max' => $data['budget_max'] ?? 100000,
            'age_min' => 18,
            'age_max' => 80,
            'travel_mode' => $data['travel_mode'] ?? '',
            'available_from' => $data['available_from'] ?? '',
            'available_to' => $data['available_to'] ?? '',
            'preferred_destination' => $data['preferred_destination'] ?? ''
        ];
    }

    return [
        'budget_min' => 0,
        'budget_max' => 100000,
        'age_min' => 18,
        'age_max' => 80,
        'travel_mode' => '',
        'available_from' => '',
        'available_to' => '',
        'preferred_destination' => ''
    ];
}

/*function getUserInterests($conn, $userId)
{
    $stmt = $conn->prepare("
        SELECT i.interest_name
        FROM user_interests ui
        JOIN interests i ON ui.interest_id = i.id
        WHERE ui.user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $interests = [];
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $interests[] = $row['interest_name'];
    }
    return $interests;
}*/

function getEligibleTrips($conn, $userId)
{
    $stmt = $conn->prepare("
        SELECT t.*, u.name AS host_name, u.email AS host_email, u.dob AS host_dob
        FROM trips t
        JOIN users u ON t.host_id = u.id
        WHERE t.host_id != ?
          AND t.status IN ('pending','confirmed')
          AND DATE(t.start_date) >= CURDATE()
          AND t.id NOT IN (
              SELECT trip_id FROM trip_applications WHERE user_id = ?
          )
    ");
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();

    $trips = [];
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $trips[] = $row;
    }
    return $trips;
}

function calculateTripCompatibilityScore($preferences, $userInterests, $trip)
{
    $score = 0;
    $maxScore = 0;

    $hasUserDestination = !empty($preferences['preferred_destination']) && !empty($trip['destination']);
    if ($hasUserDestination) {
        $maxScore += 30;
        $score += scoreDestinationMatch(
            $preferences['preferred_destination'] ?? '',
            $trip['destination']
        );
    }

    $hasDates = !empty($preferences['available_from']) && !empty($preferences['available_to'])
        && !empty($trip['start_date']) && !empty($trip['end_date']);
    if ($hasDates) {
        $maxScore += 25;
        $score += scoreDateOverlap(
            $preferences['available_from'],
            $preferences['available_to'],
            $trip['start_date'],
            $trip['end_date']
        );
    }

    $tripInterests = parseTripInterests($trip['trip_style'] ?? '');
    if (!empty($userInterests) && !empty($tripInterests)) {
        $maxScore += 20;
        $score += scoreSharedInterests($userInterests, $tripInterests);
    }

    if (!empty($preferences['travel_mode']) && !empty($trip['travel_mode'])) {
        $maxScore += 10;
        $score += scoreTravelMode($preferences['travel_mode'], $trip['travel_mode']);
    }

    if ($trip['budget_min'] !== null && $trip['budget_max'] !== null) {
        $maxScore += 10;
        $score += scoreBudgetCompatibility(
            $preferences['budget_min'],
            $preferences['budget_max'],
            $trip['budget_min'],
            $trip['budget_max']
        );
    }

    if (!empty($trip['host_dob'])) {
        $maxScore += 5;
        $score += scoreCompanionPreference(
            calculateAge($trip['host_dob']),
            $preferences['age_min'],
            $preferences['age_max'],
            $trip['travel_mode'],
            $preferences['travel_mode']
        );
    }

    if ($maxScore <= 0) return 0;

    return ($score / $maxScore) * 100;
}

function getTripRecommendations($conn, $userId, $minScore = 40, $limit = 10, $destinationFilter = '')
{
    $minScore = max(40, (int)$minScore);
    $preferences   = getUserPreferences($conn, $userId);
    $userInterests = getUserInterests($conn, $userId);
    $trips         = getEligibleTrips($conn, $userId);

    $results = [];

    foreach ($trips as $trip) {

        if (!matchesDestinationFilter($destinationFilter, $trip['destination'] ?? '')) {
            continue;
        }

        $score = calculateTripCompatibilityScore($preferences, $userInterests, $trip);

        if ($score >= $minScore) {
            $trip['compatibility_score'] = round($score, 2);
            $results[] = $trip;
        }
    }

    usort(
        $results,
        fn($a, $b) =>
        $b['compatibility_score'] <=> $a['compatibility_score']
    );

    return array_slice($results, 0, $limit);
}

function getPersonalizedTripRecommendations($conn, $userId, $userDestination = '', $limit = 10, $minScore = 40)
{
    return getTripRecommendations($conn, $userId, $minScore, $limit, $userDestination);
}
