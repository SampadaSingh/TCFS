# Integration Guide - Trip Recommendation Engine

## Quick Integration Steps

### Step 1: Verify Database Tables Exist

Ensure these tables are present in your database:
- `users` ✓
- `trips` ✓
- `trip_applications` ✓
- `user_preferences` ✓
- `user_interests` ✓
- `interests` ✓

All tables should already exist based on your schema.

### Step 2: Include the Recommendation Engine

```php
require_once 'config/db.php';
require_once 'algorithms/tripRecommendation.php';
```

### Step 3: Use in Your Pages

#### In recommendations.php (Already Updated)
```php
<?php
$recommendations = getPersonalizedTripRecommendations($conn, $user_id);
?>
```

#### In userDashboard.php (Optional Enhancement)
```php
<?php
$topRecommendations = getPersonalizedTripRecommendations($conn, $user_id, '', 5, 70);
foreach ($topRecommendations as $trip) {
    echo "<div class='recommended-trip'>";
    echo "  <h3>" . $trip['trip_name'] . "</h3>";
    echo "  <p>Match Score: " . $trip['compatibility_score'] . "%</p>";
    echo "</div>";
}
?>
```

#### Via API (Already Created)
```javascript
// JavaScript
fetch('/user/api/recommendations.php?user_id=5')
    .then(r => r.json())
    .then(data => {
        console.log(data.recommendations);
    });
```

### Step 4: Display Recommendations

Basic card display:
```html
<?php foreach ($recommendations as $trip): ?>
    <div class="trip-recommendation-card">
        <h3><?php echo htmlspecialchars($trip['trip_name']); ?></h3>
        <p class="destination">
            <i class="icon-location"></i> 
            <?php echo htmlspecialchars($trip['destination']); ?>
        </p>
        <p class="dates">
            <?php echo date('M d', strtotime($trip['start_date'])); ?> - 
            <?php echo date('M d', strtotime($trip['end_date'])); ?>
        </p>
        <p class="score">
            <strong><?php echo $trip['compatibility_score']; ?>/100</strong> Match
        </p>
        <p class="host">Host: <?php echo htmlspecialchars($trip['host_name']); ?></p>
        <a href="viewTrip.php?id=<?php echo $trip['trip_id']; ?>" class="btn btn-primary">
            View Details
        </a>
    </div>
<?php endforeach; ?>
```

### Step 5: Add CSS Styling (Optional)

```css
.trip-recommendation-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-left: 4px solid #4CAF50;
    transition: transform 0.2s;
}

.trip-recommendation-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.trip-recommendation-card .score {
    font-size: 18px;
    color: #4CAF50;
    font-weight: bold;
}

.trip-recommendation-card .destination {
    color: #666;
    font-size: 16px;
}

.trip-recommendation-card .dates {
    color: #999;
    font-size: 14px;
}

.trip-recommendation-card .host {
    color: #666;
    font-size: 14px;
    margin-top: 10px;
}
```

## API Integration Examples

### Fetch All Recommendations
```javascript
async function getRecommendations(userId) {
    const response = await fetch(`/user/api/recommendations.php?user_id=${userId}`);
    return await response.json();
}

getRecommendations(5).then(data => {
    console.log(data.recommendations);
});
```

### Fetch with Filters
```javascript
async function getRecommendations(userId, destination, limit, minScore) {
    const params = new URLSearchParams({
        user_id: userId,
        destination: destination,
        limit: limit,
        min_score: minScore
    });
    const response = await fetch(`/user/api/recommendations.php?${params}`);
    return await response.json();
}

getRecommendations(5, 'Paris', 10, 70).then(data => {
    console.log(`Found ${data.recommendation_count} trips`);
});
```

## Function Reference (Quick)

### Main Function
```php
getPersonalizedTripRecommendations(
    $conn,              // Database connection
    $userId,            // User ID
    $userDestination = '', // Destination filter
    $limit = 10,        // Max results
    $minScore = 60      // Min score (0-100)
) → array
```

### Individual Scoring (Optional)
```php
scoreDestinationMatch($userDest, $tripDest, $userReg, $tripReg)     // 0-30
scoreDateOverlap($userStart, $userEnd, $tripStart, $tripEnd)        // 0-25
scoreSharedInterests($userInterests, $tripInterests)                // 0-20
scoreTravelStyleMatch($userStyle, $tripStyle)                       // 0-10
scoreBudgetCompatibility($userMin, $userMax, $tripMin, $tripMax)   // 0-10
scoreCompanionPreference($hostAge, $ageMin, $ageMax, $hostStyle, $userStyle) // 0-5
```

## Testing the Implementation

### Test 1: Basic Function Call
```php
<?php
require 'config/db.php';
require 'algorithms/tripRecommendation.php';

$recommendations = getPersonalizedTripRecommendations($conn, 5);
echo "Found " . count($recommendations) . " recommendations\n";
foreach ($recommendations as $trip) {
    echo "- " . $trip['trip_name'] . ": " . $trip['compatibility_score'] . "\n";
}
?>
```

### Test 2: API Endpoint
```bash
# In browser or curl
curl "http://localhost/user/api/recommendations.php?user_id=5"

# Should return:
# {"success":true,"user_id":5,"recommendation_count":8,"recommendations":[...]}
```

### Test 3: Verify Scoring
```php
<?php
require 'algorithms/tripRecommendation.php';

// Test destination match
$score = scoreDestinationMatch('Paris', 'Paris');
echo "Destination match (Paris=Paris): $score (expected: 30)\n"; // 30

// Test date overlap
$score = scoreDateOverlap('2026-04-01', '2026-04-30', '2026-04-10', '2026-04-20');
echo "Date overlap (10 of 30 days): $score (expected: ~8.33)\n"; // ~8.33

// Test interests
$score = scoreSharedInterests(['Adventure', 'Hiking'], ['Hiking', 'Mountains']);
echo "Shared interests (1 of 2): $score (expected: 10)\n"; // 10
?>
```

## Common Issues & Solutions

### Issue: No recommendations returned
**Solution:**
- Check if user has preferences set (budget, age range)
- Check if user has selected interests
- Lower `minScore` to 50 or 40
- Verify trips exist with correct status (pending/confirmed)

### Issue: Scores seem too low
**Solution:**
- Check if user has interests selected
- Verify trip data has travel_mode and trip_style
- Ensure dates overlap with user availability
- Check budget ranges overlap

### Issue: API returns 400 error
**Solution:**
- Verify user_id is provided
- Verify user_id exists in database
- Check database connection

### Issue: Recommendations appear random
**Solution:**
- Engine is deterministic - check database values
- Verify user preferences are set correctly
- Run example 4 to see score breakdown

## Performance Tips

### Optimize Queries
```php
// Add these indexes to your database
ALTER TABLE trips ADD INDEX idx_host_status_date (host_id, status, start_date);
ALTER TABLE trip_applications ADD INDEX idx_user_status (user_id, status);
ALTER TABLE user_preferences ADD INDEX idx_user (user_id);
ALTER TABLE user_interests ADD INDEX idx_user (user_id);
```

### Cache Results
```php
// Cache recommendations for 30 minutes
$cacheKey = "recommendations_" . $userId;
$cached = apcu_fetch($cacheKey);

if ($cached !== false) {
    return $cached;
}

$recommendations = getPersonalizedTripRecommendations($conn, $userId);
apcu_store($cacheKey, $recommendations, 1800); // 30 minutes

return $recommendations;
```

### Batch Processing
```php
// For multiple users
$users = $conn->query("SELECT id FROM users");
$allRecommendations = [];

while ($user = $users->fetch_assoc()) {
    $allRecommendations[$user['id']] = getPersonalizedTripRecommendations($conn, $user['id']);
}
```

## Documentation Files

- **[RECOMMENDATION_ENGINE.md](RECOMMENDATION_ENGINE.md)** - Comprehensive reference
- **[recommendations_examples.php](recommendations_examples.php)** - Code examples
- **[RECOMMENDATION_ENGINE_IMPLEMENTATION.md](../RECOMMENDATION_ENGINE_IMPLEMENTATION.md)** - Implementation summary

## Next Steps

1. ✓ **Implementation Complete** - Engine is ready to use
2. **Test** - Run test examples to verify
3. **Integrate** - Add to your pages
4. **Display** - Style recommendations for users
5. **Monitor** - Track average scores and user engagement
6. **Refine** - Adjust thresholds based on user feedback

## Support

For issues or questions:
- Check [RECOMMENDATION_ENGINE.md](RECOMMENDATION_ENGINE.md) for detailed documentation
- Review [recommendations_examples.php](recommendations_examples.php) for code examples
- Check database structure against schema.php

---

**Status:** Ready for Integration
**Version:** 1.0
**Last Updated:** January 28, 2026
