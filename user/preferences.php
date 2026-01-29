<?php
session_start();
require "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

$preferences = [];

$pref_stmt = $conn->prepare("SELECT * FROM user_preferences WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$pref_stmt->bind_param("i", $user_id);
$pref_stmt->execute();
$result = $pref_stmt->get_result();

if ($result->num_rows > 0) {
    $preferences = $result->fetch_assoc();
}

$defaults = [
    'preferred_destination' => '',
    'trip_style' => '',
    'available_from' => '',
    'available_to' => '',
    'budget_min' => 0,
    'budget_max' => 0,
    'travel_mode' => '',
    'age_min' => 18,
    'age_max' => 65,
    'preferred_gender' => 'Any'
];

$preferences = array_merge($defaults, $preferences);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $preferred_destination = trim($_POST['preferred_destination'] ?? $preferences['preferred_destination']);

    $trip_style = !empty($_POST['trip_style'])
        ? implode(',', $_POST['trip_style'])
        : $preferences['trip_style'];

    $available_from = !empty($_POST['available_from']) ? $_POST['available_from'] : $preferences['available_from'];
    $available_to   = !empty($_POST['available_to']) ? $_POST['available_to'] : $preferences['available_to'];

    $budget_min = (!empty($_POST['budget_min']) || $_POST['budget_min'] === '0') ? (float)$_POST['budget_min'] : $preferences['budget_min'];
    $budget_max = (!empty($_POST['budget_max']) || $_POST['budget_max'] === '0') ? (float)$_POST['budget_max'] : $preferences['budget_max'];

    $travel_mode = !empty($_POST['travel_mode']) ? $_POST['travel_mode'] : $preferences['travel_mode'];

    $age_min = (!empty($_POST['age_min']) || $_POST['age_min'] === '0') ? (int)$_POST['age_min'] : $preferences['age_min'];
    $age_max = (!empty($_POST['age_max']) || $_POST['age_max'] === '0') ? (int)$_POST['age_max'] : $preferences['age_max'];

    $preferred_gender = $_POST['preferred_gender'] ?? $preferences['preferred_gender'];

    if ($budget_min < 0 || $budget_max < 0 || $budget_min > $budget_max) {
        $message = "Invalid budget range.";
        $message_type = "danger";
    } elseif ($age_min < 0 || $age_max < 0 || $age_min > $age_max) {
        $message = "Invalid age range.";
        $message_type = "danger";
    } elseif ($available_from && $available_to && strtotime($available_from) > strtotime($available_to)) {
        $message = "Start date must be before end date.";
        $message_type = "danger";
    }

    if (empty($message)) {

        if (!empty($preferences['id'])) {
            // UPDATE
            $stmt = $conn->prepare("
                UPDATE user_preferences 
                SET preferred_destination = ?, trip_style = ?, available_from = ?, available_to = ?, 
                    budget_min = ?, budget_max = ?, travel_mode = ?, age_min = ?, age_max = ?, preferred_gender = ?, preferences_filled = 1
                WHERE id = ? AND user_id = ?
            ");

            $stmt->bind_param(
                "ssssddsiisii",
                $preferred_destination,
                $trip_style,
                $available_from,
                $available_to,
                $budget_min,
                $budget_max,
                $travel_mode,
                $age_min,
                $age_max,
                $preferred_gender,
                $preferences['id'],
                $user_id
            );

            $stmt->execute();
            $message = "Preferences updated successfully!";
        } else {
            // INSERT
            $stmt = $conn->prepare("
                INSERT INTO user_preferences 
                (user_id, preferred_destination, trip_style, available_from, available_to, budget_min, budget_max, travel_mode, age_min, age_max, preferred_gender, preferences_filled)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");

            $stmt->bind_param(
                "isssddsiiss",
                $user_id,
                $preferred_destination,
                $trip_style,
                $available_from,
                $available_to,
                $budget_min,
                $budget_max,
                $travel_mode,
                $age_min,
                $age_max,
                $preferred_gender
            );

            $stmt->execute();
            $message = "Preferences saved successfully!";
            header("Location: userDashboard.php");
            exit;

        }

        $message_type = "success";

        $stmt = $conn->prepare("SELECT * FROM user_preferences WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $preferences = array_merge($defaults, $stmt->get_result()->fetch_assoc());
    }
}

$all_styles = ['Adventure', 'Cultural', 'Relaxation', 'Nature', 'Historical', 'Wildlife', 'Religious'];
$selected_styles = !empty($preferences['trip_style']) ? array_map('trim', explode(',', $preferences['trip_style'])) : [];

$travel_modes = ['Bike', 'Car', 'Bus', 'Train', 'Flight', 'Jeep', 'Walking', 'Mixed'];
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Preferences - TCFS</title>
    <?php require "sidebar.php"; ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #E8F4F8;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-content {
            margin-left: 250px;
            padding: 40px;
            min-height: 100vh;
        }

        .preferences-container {
            max-width: 800px;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(234, 88, 12, 0.06);
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .page-subtitle {
            color: #666;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
            display: block;
        }

        .form-control,
        .form-select {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 14px;
            width: 100%;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #57C785;
            box-shadow: 0 0 0 0.2rem rgba(87, 199, 133, 0.25);
            outline: none;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-row.full {
            grid-template-columns: 1fr;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }

        .btn-submit {
            background-color: #57C785;
            border: none;
            color: white;
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s;
            flex: 1;
        }

        .btn-submit:hover {
            background-color: #45a049;
            color: white;
            text-decoration: none;
        }

        .btn-cancel {
            background-color: #999;
            border: none;
            color: white;
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s;
            flex: 1;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-cancel:hover {
            background-color: #777;
            color: white;
            text-decoration: none;
        }

        .alert {
            margin-bottom: 20px;
            border-radius: 6px;
            border: none;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .preferences-container {
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <?php require "sidebar.php"; ?>

    <div class="main-content">
        <div class="preferences-container">
            <h1 class="page-title"><i class="bi bi-sliders"></i> Travel Preferences</h1>
            <p class="page-subtitle">
                <?php echo !empty($preferences) ? 'Update your travel preferences to get better trip recommendations' : 'Set your travel preferences to get better trip recommendations'; ?>
            </p>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                    <strong><?php echo ucfirst($message_type); ?>!</strong> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-section">
                    <div class="section-title"><i class="bi bi-map"></i> Destination Preferences</div>

                    <div class="form-group form-row full">
                        <div>
                            <label for="destination" class="form-label">Preferred Destination</label>
                            <input
                                type="text"
                                class="form-control"
                                id="destination"
                                name="preferred_destination"
                                placeholder="e.g., Pokhara, Nagarkot, Mustang"
                                value="<?php echo isset($preferences['preferred_destination']) ? htmlspecialchars($preferences['preferred_destination']) : ''; ?>">
                            <div class="help-text">The destination you'd prefer to travel to</div>
                        </div>
                    </div>
                    <div class="form-group form-row full">
                        <div>
                            <label class="form-label">Preferred Trip Styles</label>
                            <?php foreach ($all_styles as $style): ?>
                                <div>
                                    <input
                                        type="checkbox"
                                        id="style_<?php echo $style; ?>"
                                        name="trip_style[]"
                                        value="<?php echo $style; ?>"
                                        <?php echo in_array($style, $selected_styles) ? 'checked' : ''; ?>>
                                    <label for="style_<?php echo $style; ?>"><?php echo $style; ?></label>
                                </div>
                            <?php endforeach; ?>
                            <div class="help-text">Select one or more styles of trips you prefer</div>
                        </div>
                    </div>

                </div>

                <div class="form-section">
                    <div class="section-title"><i class="bi bi-calendar"></i> Availability</div>

                    <div class="form-group form-row">
                        <div>
                            <label for="available_from" class="form-label">Available From</label>
                            <input
                                type="date"
                                class="form-control"
                                id="available_from"
                                name="available_from"
                                value="<?php echo htmlspecialchars($preferences['available_from'] ?? ''); ?>">
                            <div class="help-text">When you're available to start traveling</div>
                        </div>

                        <div>
                            <label for="available_to" class="form-label">Available To</label>
                            <input
                                type="date"
                                class="form-control"
                                id="available_to"
                                name="available_to"
                                value="<?php echo htmlspecialchars($preferences['available_to'] ?? ''); ?>">
                            <div class="help-text">When you need to be back</div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-title"><i class="bi bi-cash-coin"></i> Budget</div>

                    <div class="form-group form-row">
                        <div>
                            <label for="budget_min" class="form-label">Minimum Budget</label>
                            <input
                                type="number"
                                class="form-control"
                                id="budget_min"
                                name="budget_min"
                                min="0"
                                step="100"
                                value="<?php echo isset($preferences['budget_min']) ? (float)$preferences['budget_min'] : 0; ?>">
                            <div class="help-text">Your minimum budget for a trip</div>
                        </div>

                        <div>
                            <label for="budget_max" class="form-label">Maximum Budget</label>
                            <input
                                type="number"
                                class="form-control"
                                id="budget_max"
                                name="budget_max"
                                min="0"
                                step="100"
                                value="<?php echo isset($preferences['budget_max']) ? (float)$preferences['budget_max'] : 100000; ?>">
                            <div class="help-text">Your maximum budget for a trip</div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-title"><i class="bi bi-backpack"></i> Travel Style</div>

                    <div class="form-group form-row full">
                        <div>
                            <label for="travel_mode" class="form-label">Preferred Travel Mode</label>
                            <select class="form-select" id="travel_mode" name="travel_mode">
                                <option value="">-- Select a travel mode --</option>
                                <?php foreach ($travel_modes as $mode): ?>
                                    <option value="<?php echo htmlspecialchars($mode); ?>"
                                        <?php echo (isset($preferences['travel_mode']) && $preferences['travel_mode'] === $mode) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($mode); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="help-text">The type of travel experience you prefer</div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-title"><i class="bi bi-people"></i> Companion Preferences</div>

                    <div class="form-group form-row">
                        <div>
                            <label for="age_min" class="form-label">Companion Minimum Age</label>
                            <input
                                type="number"
                                class="form-control"
                                id="age_min"
                                name="age_min"
                                min="18"
                                max="120"
                                value="<?php echo isset($preferences['age_min']) ? (int)$preferences['age_min'] : 18; ?>">
                            <div class="help-text">Minimum age of travel companions</div>
                        </div>

                        <div>
                            <label for="age_max" class="form-label">Companion Maximum Age</label>
                            <input
                                type="number"
                                class="form-control"
                                id="age_max"
                                name="age_max"
                                min="18"
                                max="120"
                                value="<?php echo isset($preferences['age_max']) ? (int)$preferences['age_max'] : 80; ?>">
                            <div class="help-text">Maximum age of travel companions</div>
                        </div>
                    </div>

                    <div class="form-group form-row full">
                        <div>
                            <label for="preferred_gender" class="form-label">Preferred Companion Gender</label>
                            <select class="form-select" id="preferred_gender" name="preferred_gender">
                                <option value="Any" <?php echo (isset($preferences['preferred_gender']) && $preferences['preferred_gender'] === 'Any') ? 'selected' : (empty($preferences) ? 'selected' : ''); ?>>
                                    Any
                                </option>
                                <option value="Male" <?php echo (isset($preferences['preferred_gender']) && $preferences['preferred_gender'] === 'Male') ? 'selected' : ''; ?>>
                                    Male
                                </option>
                                <option value="Female" <?php echo (isset($preferences['preferred_gender']) && $preferences['preferred_gender'] === 'Female') ? 'selected' : ''; ?>>
                                    Female
                                </option>
                            </select>
                            <div class="help-text">Preferred gender of travel companions</div>
                        </div>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn-submit"><i class="bi bi-check-circle"></i> <?php echo !empty($preferences) ? 'Update Preferences' : 'Save Preferences'; ?></button>
                    <a href="userDashboard.php" class="btn-cancel"><i class="bi bi-x-circle"></i> Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>