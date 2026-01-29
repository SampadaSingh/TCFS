# Trip Recommendation Engine - TCFS System

## Overview

The Trip Recommendation Engine is a deterministic weighted scoring model that generates personalized trip recommendations by comparing user preferences with trip data and host profiles.

## Architecture

### Location
- **Main Engine**: `algorithms/tripRecommendation.php`
- **API Endpoint**: `user/api/recommendations.php`
- **Display Page**: `user/recommendations.php`

### Files Structure

```
algorithms/
├── tripRecommendation.php      # Main recommendation engine
├── compatibility.php            # Legacy compatibility functions
├── weightedMatch.php           # Weighted matching utilities
├── placeRecommendation.php     # Place recommendations
└── recommendation.php          # General recommendations

user/
├── recommendations.php          # Recommendations display page
└── api/
    └── recommendations.php      # REST API endpoint
```

## Recommendation Pipeline

### Step 1: Fetch User Preferences
- Retrieves user preferences from `user_preferences` table
- Retrieves user interests from `user_interests` table
- Includes availability dates (available_from, available_to)
- Includes budget range, age preferences, and travel style

### Step 2: Fetch Eligible Trips

Hard constraints applied:
- `trip.host_id != current_user` - Cannot apply to own trips
- `trip.current_participants < trip.max_participants` - Must have available spots
- Date overlap exists between trip and user availability
- `trip.destination` matches user preference (if specified)
- User has not already applied to this trip
- Trip status is 'pending' or 'confirmed'
- Trip start date is >= today

### Step 3: Calculate Compatibility Score

For each eligible trip, calculate total score (0-100):

#### Scoring Dimensions

1. **Destination Match (max 30)**
   - Exact city match = 30 points
   - Same region/country = 20 points
   - Otherwise = 0 points

2. **Date Overlap (max 25)**
   - Formula: `(overlapping_days / trip_duration_days) * 25`
   - Example: 50% overlap = 12.5 points

3. **Shared Interests (max 20)**
   - Formula: `(shared_interests / total_user_interests) * 20`
   - Example: User has 5 interests, 2 match trip = (2/5) * 20 = 8 points

4. **Travel Style Match (max 10)**
   - Exact match = 10 points
   - No match = 0 points

5. **Budget Compatibility (max 10)**
   - Trip budget within user range = 10 points
   - Partial overlap = 5 points
   - No overlap = 0 points

6. **Companion Preference (max 5)**
   - Host age within user's preferred range = +3 points
   - Host travel style matches user's = +2 points
   - Maximum total = 5 points

**Total Score = Sum of all dimensions (max 100)**

### Step 4: Filter by Minimum Score

Only recommendations with score >= 60 are returned. This threshold can be adjusted via the `minScore` parameter.

### Step 5: Sort Results

Results are sorted by compatibility score in descending order (highest scores first).

### Step 6: Return Top N

By default, returns top 10 recommendations. Can be adjusted via the `limit` parameter.

## Usage

### 1. Using the Main Function

```php
<?php
require_once 'config/db.php';
require_once 'algorithms/tripRecommendation.php';

// Get top 10 recommendations for user with score >= 60
$recommendations = getPersonalizedTripRecommendations(
    $conn,
    $user_id,
    $userDestination = '',  // Optional: filter by destination
    $limit = 10,            // Optional: number of results
    $minScore = 60          // Optional: minimum score threshold
);

foreach ($recommendations as $trip) {
    echo "Trip: " . $trip['trip_name'] . " - Score: " . $trip['compatibility_score'];
}
?>
```

### 2. Using the REST API

```bash
# Get recommendations
GET /user/api/recommendations.php?user_id=5&limit=10&min_score=60

# With destination filter
GET /user/api/recommendations.php?user_id=5&destination=Paris&limit=10
```

Response:
```json
{
  "success": true,
  "user_id": 5,
  "recommendation_count": 8,
  "recommendations": [
    {
      "trip_id": 42,
      "trip_name": "Paris City Tour",
      "destination": "Paris",
      "region": "France",
      "start_date": "2026-03-01",
      "end_date": "2026-03-07",
      "budget_min": 1000,
      "budget_max": 2000,
      "travel_mode": "cultural",
      "compatibility_score": 87.5,
      "host_name": "John Doe",
      "current_participants": 3,
      "available_spots": 2
    }
  ]
}
```

### 3. Using in Templates

```php
<?php
require_once 'algorithms/tripRecommendation.php';

$recommendations = getPersonalizedTripRecommendations($conn, $user_id);

foreach ($recommendations as $trip):
?>
  <div class="trip-card">
    <h3><?php echo htmlspecialchars($trip['trip_name']); ?></h3>
    <p><?php echo htmlspecialchars($trip['destination']); ?></p>
    <p>Score: <?php echo $trip['compatibility_score']; ?>/100</p>
    <p>Host: <?php echo htmlspecialchars($trip['host_name']); ?></p>
    <p>Available Spots: <?php echo $trip['available_spots']; ?></p>
  </div>
<?php endforeach; ?>
```

## Scoring Examples

### Example 1: High Compatibility Trip

User:
- Interests: [Adventure, Hiking, Mountains]
- Budget: $1000-$3000
- Available: 2026-04-01 to 2026-04-15
- Preferred Age: 25-45
- Travel Style: Adventure

Trip:
- Destination: Swiss Alps
- User preferred: Swiss Alps
- Budget: $1500-$2500
- Date: 2026-04-05 to 2026-04-12
- Style: Adventure
- Host Age: 35

Scoring:
- Destination: 30 (exact match)
- Date Overlap: 20 (7 of 8 days overlap)
- Interests: 13 (2 of 3 interests match)
- Travel Style: 10 (exact match)
- Budget: 10 (within range)
- Companion: 5 (age 35 in 25-45 range + style match)
- **Total: 88/100** ✓ Recommended

### Example 2: Low Compatibility Trip

User:
- Interests: [Beach, Relaxation, Food]
- Budget: $500-$1500
- Available: 2026-04-01 to 2026-04-15
- Travel Style: Relaxation

Trip:
- Destination: Mountain Climbing
- Budget: $3000-$5000
- Date: 2026-04-20 to 2026-04-27
- Style: Adventure
- Host Age: 22

Scoring:
- Destination: 0 (no match)
- Date Overlap: 0 (no overlap)
- Interests: 0 (no match)
- Travel Style: 0 (no match)
- Budget: 0 (out of range)
- Companion: 0 (age outside range, style mismatch)
- **Total: 0/100** ✗ Not recommended

## Database Schema

### Required Tables

#### user_preferences
```sql
CREATE TABLE user_preferences (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  age_min INT,
  age_max INT,
  preferred_gender VARCHAR(50),
  budget_min INT,
  budget_max INT,
  travel_mode VARCHAR(100),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### user_interests
```sql
CREATE TABLE user_interests (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  interest_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (interest_id) REFERENCES interests(id) ON DELETE CASCADE,
  UNIQUE KEY unique_user_interest (user_id, interest_id)
);
```

#### interests
```sql
CREATE TABLE interests (
  id INT PRIMARY KEY AUTO_INCREMENT,
  interest_name VARCHAR(100) UNIQUE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### users (required fields)
- `dob` - Date of birth (for age calculation)
- `travel_mode` - User's travel style
- `available_from` - Availability start date
- `available_to` - Availability end date

#### trips (required fields)
- `host_id` - Trip creator
- `destination` - Trip destination
- `region` - Trip region/country
- `start_date` - Trip start date
- `end_date` - Trip end date
- `budget_min` - Minimum budget
- `budget_max` - Maximum budget
- `travel_mode` - Trip style
- `trip_style` - Additional trip preferences
- `group_size_max` - Maximum participants
- `status` - Trip status (pending, confirmed, etc.)

## API Reference

### getPersonalizedTripRecommendations()

Main function to get recommendations.

**Parameters:**
- `mysqli $conn` - Database connection
- `int $userId` - User ID
- `string $userDestination = ''` - Optional destination filter
- `int $limit = 10` - Number of recommendations
- `int $minScore = 60` - Minimum score threshold

**Returns:** Array of recommendation objects

**Fields in each recommendation:**
- `trip_id` - Trip ID
- `trip_name` - Trip name
- `destination` - Destination
- `region` - Region
- `start_date` - Start date
- `end_date` - End date
- `budget_min` - Minimum budget
- `budget_max` - Maximum budget
- `travel_mode` - Travel style
- `trip_style` - Trip preferences
- `group_size_max` - Max participants
- `current_participants` - Current participants
- `available_spots` - Available spots
- `host_id` - Host user ID
- `host_name` - Host name
- `host_email` - Host email
- `compatibility_score` - Score (0-100)
- `description` - Trip description

### Scoring Functions

Each scoring function is available for detailed breakdown:

```php
// Individual scoring dimensions
$destScore = scoreDestinationMatch($userDest, $tripDest, $userReg, $tripReg);
$dateScore = scoreDateOverlap($userStart, $userEnd, $tripStart, $tripEnd);
$interestScore = scoreSharedInterests($userInterests, $tripInterests);
$styleScore = scoreTravelStyleMatch($userStyle, $tripStyle);
$budgetScore = scoreBudgetCompatibility($userMin, $userMax, $tripMin, $tripMax);
$companionScore = scoreCompanionPreference($hostAge, $userAgeMin, $userAgeMax, $hostStyle, $userStyle);
```

### Score Breakdown

```php
$breakdown = getTripScoreBreakdown($user, $trip, $userInterests);

// Returns:
// [
//   'destination_match' => ['score' => 30, 'max' => 30],
//   'date_overlap' => ['score' => 20, 'max' => 25],
//   'shared_interests' => ['score' => 15, 'max' => 20],
//   'travel_style_match' => ['score' => 10, 'max' => 10],
//   'budget_compatibility' => ['score' => 10, 'max' => 10],
//   'companion_preference' => ['score' => 4, 'max' => 5],
//   'total_score' => ['score' => 89, 'max' => 100]
// ]
```

## Legacy Functions

For backward compatibility, the following legacy functions are still available:

- `recommendTripsForUser($conn, $userId, $limit = 10)` - Weighted match recommendations
- `recommendCompanionsForUser($conn, $userId, $limit = 10)` - Companion match recommendations

## Configuration

### Minimum Score Threshold

Default is 60. Adjust based on desired recommendation quality:
- **50-60**: More permissive, more results
- **60-70**: Balanced (default)
- **70+**: Strict, higher quality recommendations

### Result Limit

Default is 10. Adjust based on UI requirements:
- `limit = 5`: Show top 5
- `limit = 10`: Show top 10
- `limit = 20`: Show top 20

### Scoring Weights

Current weights (can be modified in scoring functions):
- Destination: 30 points (30%)
- Date Overlap: 25 points (25%)
- Shared Interests: 20 points (20%)
- Travel Style: 10 points (10%)
- Budget: 10 points (10%)
- Companion Preference: 5 points (5%)

## Deterministic Behavior

The recommendation engine is fully deterministic:
- Same input always produces same output
- No randomization
- Reproducible results
- Suitable for testing and verification

## Performance Considerations

### Optimization Strategies

1. **Database Indexes**
   - Index `trips(host_id)`
   - Index `trips(status, start_date)`
   - Index `trip_applications(user_id, status)`
   - Index `user_preferences(user_id)`
   - Index `user_interests(user_id)`

2. **Query Caching**
   - Cache user preferences (TTL: 1 hour)
   - Cache user interests (TTL: 1 hour)
   - Cache eligible trips list (TTL: 30 minutes)

3. **Pagination**
   - Implement offset/limit for large result sets
   - Load top 50, then slice to top 10

## Troubleshooting

### No Recommendations Returned

**Possible causes:**
1. No eligible trips available
2. User has not set preferences (no budget/age range)
3. User has not selected interests
4. All trips have scores < 60

**Solutions:**
1. Lower `minScore` parameter to 50 or 40
2. Create test trips with matching criteria
3. Ensure user has preferences set
4. Check database for available trips

### Unexpected Low Scores

**Possible causes:**
1. User has no interests selected
2. User availability dates too narrow
3. Trips have no style/preferences set
4. Budget ranges don't overlap

**Solutions:**
1. Ensure user has interests selected
2. Adjust user availability dates
3. Ensure trips have travel_mode and trip_style set
4. Check budget compatibility

## Future Enhancements

Potential improvements:
1. Machine learning scoring weights optimization
2. Collaborative filtering (similar users' preferences)
3. Trend analysis (popular destinations/styles)
4. Real-time scoring updates
5. A/B testing framework for weight tuning
6. User feedback loop for score refinement
