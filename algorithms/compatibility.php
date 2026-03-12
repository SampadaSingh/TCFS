<?php
function calculateDateOverlap($start1, $end1, $start2, $end2) {
    // ensure all are DateTime objects
    if (!($start1 instanceof DateTime)) $start1 = new DateTime($start1);
    if (!($end1 instanceof DateTime)) $end1 = new DateTime($end1);
    if (!($start2 instanceof DateTime)) $start2 = new DateTime($start2);
    if (!($end2 instanceof DateTime)) $end2 = new DateTime($end2);

    $overlapStart = $start1 > $start2 ? $start1 : $start2;
    $overlapEnd   = $end1 < $end2 ? $end1 : $end2;

    if ($overlapStart > $overlapEnd) return 0;

    return $overlapStart->diff($overlapEnd)->days + 1;
}

function calculateInterestScore($userInterests, $hostInterests) {
    if (empty($userInterests) || empty($hostInterests)) return 50;
    $u = array_map('strtolower', $userInterests);
    $h = array_map('strtolower', $hostInterests);
    $common = count(array_intersect($u, $h));
    $total  = count(array_unique(array_merge($u, $h)));
    return $total > 0 ? ($common / $total) * 100 : 50;
}

function calculateDestinationScore($userDest, $tripDest) {
    $u = strtolower(trim($userDest));
    $t = strtolower(trim($tripDest));
    if (empty($u) || empty($t)) return 50;
    if ($u === $t) return 100;
    similar_text($u, $t, $percent);
    if ($percent >= 70) return 60;
    if ($percent >= 40) return 30;
    return 0;
}

function calculateModeScore($userMode, $tripMode) {
    $u = strtolower(trim($userMode));
    $t = strtolower(trim($tripMode));
    if (empty($u) || $t === 'mixed') return 100;
    return $u === $t ? 100 : 70;
}

function calculateAge($dob) {
    if (empty($dob)) return 0;
    return (new DateTime())->diff(new DateTime($dob))->y;
}

function calculateAgeScore($userAge, $trip) {
    $ageMin = $trip['age_min'] ?? 0;
    $ageMax = $trip['age_max'] ?? 99;
    if (($trip['preferred_age'] ?? 'Any') === 'Any') return 100;
    return ($userAge >= $ageMin && $userAge <= $ageMax) ? 100 : 70;
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
    while ($row = $result->fetch_assoc()) $interests[] = $row['interest_name'];
    return $interests;
}

function isTripEligible($user, $trip, $conn) {
    if (!empty($trip['preferred_gender']) && strtolower($trip['preferred_gender']) !== 'any' &&
        strtolower($trip['preferred_gender']) !== strtolower($user['gender'])) return false;

    if (!empty($trip['group_size_max']) && ($trip['accepted_count'] ?? 0) >= $trip['group_size_max']) return false;

    $stmt = $conn->prepare("
        SELECT 1 FROM trip_applications 
        WHERE user_id = ? AND trip_id = ? AND status IN ('accepted','pending')
        LIMIT 1
    ");
    $stmt->bind_param("ii", $user['id'], $trip['id']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) return false;

    if (strtotime($trip['end_date']) < strtotime(date('Y-m-d'))) return false;

    return true;
}

function calculateUserTripCompatibility($user, $trip, $userInterests, $hostInterests) {
    $weights = [
        'date' => 0.40,
        'destination' => 0.25,
        'mode' => 0.10,
        'host_interest' => 0.15,
        'age' => 0.10
    ];

    $userStart = !empty($user['available_from']) ? new DateTime($user['available_from']) : new DateTime($trip['start_date']);
    $userEnd   = !empty($user['available_to']) ? new DateTime($user['available_to']) : new DateTime($trip['end_date']);
    $tripStart = new DateTime($trip['start_date']);
    $tripEnd   = new DateTime($trip['end_date']);

    $tripDuration = $tripStart->diff($tripEnd)->days + 1;
    $overlapDays  = calculateDateOverlap($userStart, $userEnd, $tripStart, $tripEnd);
    $dateScore    = $tripDuration > 0 ? ($overlapDays / $tripDuration) * 100 : 0;

    $destinationScore = calculateDestinationScore($user['preferred_destination'] ?? '', $trip['destination'] ?? '');
    $modeScore        = calculateModeScore($user['travel_mode'] ?? '', $trip['travel_mode'] ?? '');
    $interestScore    = calculateInterestScore($userInterests, $hostInterests);
    $ageScore         = calculateAgeScore(calculateAge($user['dob']), $trip);

    $finalScore = ($dateScore * $weights['date']) +
                  ($destinationScore * $weights['destination']) +
                  ($modeScore * $weights['mode']) +
                  ($interestScore * $weights['host_interest']) +
                  ($ageScore * $weights['age']);

    return round($finalScore, 2);
}
?>