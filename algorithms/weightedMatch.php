<?php

function calculateDateOverlapScore($user_from, $user_to, $trip_start, $trip_end) {
    if (empty($user_from) || empty($user_to)) {
        return 50;
    }

    $uStart = strtotime($user_from);
    $uEnd = strtotime($user_to);
    $tStart = strtotime($trip_start);
    $tEnd = strtotime($trip_end);

    $overlapStart = max($uStart, $tStart);
    $overlapEnd = min($uEnd, $tEnd);

    if ($overlapStart > $overlapEnd) {
        return 0;
    }

    $tripDays = ($tEnd - $tStart) / (60 * 60 * 24);
    $overlapDays = ($overlapEnd - $overlapStart) / (60 * 60 * 24);

    if ($tripDays <= 0) {
        return 0;
    }

    $score = ($overlapDays / $tripDays) * 100;
    return min(100, max(0, $score));
}

function calculateBudgetOverlapScore($userBudget, $tripMin, $tripMax) {
    if (!$userBudget || $tripMin == 0) {
        return 50;
    }

    if ($userBudget >= $tripMin && $userBudget <= $tripMax) {
        return 100;
    }

    if ($userBudget < $tripMin) {
        $diff = $tripMin - $userBudget;
    } else {
        $diff = $userBudget - $tripMax;
    }

    $range = $tripMax - $tripMin + 1;
    $penalty = ($diff / $range) * 100;
    $score = max(0, 100 - $penalty);

    return min(100, $score);
}

function calculateTripStyleScore($userInterests, $tripStyle) {
    if (empty($userInterests) || empty($tripStyle)) {
        return 50;
    }

    $interests = array_map('strtolower', array_map('trim', explode(',', $userInterests)));
    $styles = array_map('strtolower', array_map('trim', explode(',', $tripStyle)));

    $common = count(array_intersect($interests, $styles));

    if (empty($styles)) {
        return 50;
    }

    $score = ($common / count($styles)) * 100;
    return min(100, $score);
}

function calculateTravelModeScore($userMode, $tripMode) {
    if (empty($userMode) || empty($tripMode)) {
        return 50;
    }

    return (strtolower($userMode) === strtolower($tripMode)) ? 100 : 40;
}

function calculateDestinationScore($userInterests, $destination, $region) {
    if (empty($userInterests)) {
        return 50;
    }

    $interests = array_map('strtolower', array_map('trim', explode(',', $userInterests)));
    $destLower = strtolower($destination);
    $regionLower = strtolower($region);

    foreach ($interests as $interest) {
        if (strpos($interest, $destLower) !== false || strpos($destLower, $interest) !== false) {
            return 100;
        }
        if (strpos($interest, $regionLower) !== false || strpos($regionLower, $interest) !== false) {
            return 75;
        }
    }

    return 30;
}

function calculateAgeGenderScore($age, $gender, $prefAge, $prefGender) {
    $score = 50;

    if ($prefGender !== 'Any' && !empty($gender)) {
        if (strtolower($prefGender) === strtolower($gender)) {
            $score += 25;
        } else {
            $score -= 15;
        }
    } else {
        $score += 25;
    }

    if ($prefAge !== 'Any' && !empty($age)) {
        if (isAgeInRange($age, $prefAge)) {
            $score += 25;
        } else {
            $score -= 15;
        }
    } else {
        $score += 25;
    }

    return min(100, max(0, $score));
}

function isAgeInRange($age, $ageRange) {
    if (strpos($ageRange, '-') !== false) {
        list($min, $max) = explode('-', $ageRange);
        return $age >= intval($min) && $age <= intval($max);
    } elseif (strpos($ageRange, '+') !== false) {
        $min = intval(str_replace('+', '', $ageRange));
        return $age >= $min;
    }
    return true;
}

function calculateTripCompatibility($user, $trip) {
    $dateScore = calculateDateOverlapScore(
        $user['available_from'] ?? null,
        $user['available_to'] ?? null,
        $trip['start_date'],
        $trip['end_date']
    );

    if ($dateScore === 0) {
        return 0;
    }

    $scores = [
        'date' => $dateScore,
        'trip_style' => calculateTripStyleScore($user['interests'] ?? '', $trip['trip_style'] ?? ''),
        'budget' => calculateBudgetOverlapScore($user['budget'] ?? 0, $trip['budget_min'] ?? 0, $trip['budget_max'] ?? 0),
        'travel_mode' => calculateTravelModeScore($user['travel_mode'] ?? '', $trip['travel_mode'] ?? ''),
        'destination' => calculateDestinationScore($user['interests'] ?? '', $trip['destination'] ?? '', $trip['region'] ?? ''),
        'age_gender' => calculateAgeGenderScore($user['age'] ?? 0, $user['gender'] ?? 'Any', $trip['preferred_age'] ?? 'Any', $trip['preferred_gender'] ?? 'Any')
    ];

    $weights = ['date' => 0.25, 'trip_style' => 0.20, 'budget' => 0.20, 'travel_mode' => 0.15, 'destination' => 0.10, 'age_gender' => 0.10];

    $finalScore = 0;
    foreach ($scores as $factor => $score) {
        $finalScore += $score * $weights[$factor];
    }

    return (int) round($finalScore);
}

?>
