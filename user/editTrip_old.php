<?php
session_start();
require "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$trip_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare("SELECT * FROM trips WHERE id=? AND host_id=?");
$stmt->bind_param("ii", $trip_id, $user_id);
$stmt->execute();
$trip = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$trip) die("Trip not found.");

$applicants = [];
$app_res = $conn->prepare("
    SELECT u.id, u.name 
    FROM users u
    JOIN trip_applications ta ON u.id = ta.user_id
    WHERE ta.trip_id = ?
");
$app_res->bind_param("i", $trip_id);
$app_res->execute();
$app_result = $app_res->get_result();
while ($row = $app_result->fetch_assoc()) {
    $applicants[] = $row;
}
$app_res->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = '';
    $trip_name = trim($_POST['trip_name']);
    $destination = trim($_POST['destination']);
    $start_place = trim($_POST['start_place']);
    $end_place = trim($_POST['end_place']);
    $travel_mode = $_POST['travel_mode'] ?? 'Mixed';
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $trip_style = $_POST['trip_style'] ?? 'Adventure';
    $description = trim($_POST['description']);
    $preferred_age = $_POST['preferred_age'] ?? 'Any';
    $preferred_gender = $_POST['preferred_gender'] ?? 'Any';
    $collaborator_id = !empty($_POST['collaborator_id']) ? intval($_POST['collaborator_id']) : 0;

    if (strtotime($start_date) >= strtotime($end_date)) {
        $error = "End date must be after start date.";
    } else {
        $duration_days = (int)((strtotime($end_date) - strtotime($start_date)) / 86400) + 1;
    }

    $budget_label = $_POST['budget_range'];
    preg_match_all('/\d+/', str_replace(',', '', $budget_label), $matches);
    $budget_min = isset($matches[0][0]) ? intval($matches[0][0]) : 0;
    $budget_max = isset($matches[0][1]) ? intval($matches[0][1]) : $budget_min;

    $group_size_label = $_POST['group_size'];
    preg_match_all('/\d+/', $group_size_label, $gs_matches);
    $group_size_min = isset($gs_matches[0][0]) ? intval($gs_matches[0][0]) : 1;
    $group_size_max = isset($gs_matches[0][1]) ? intval($gs_matches[0][1]) : 0;
    if (strpos($group_size_label, '+') !== false) $group_size_max = 0;

    $stmt = $conn->prepare("
        UPDATE trips SET
            trip_name=?, destination=?, start_place=?, end_place=?,
            start_date=?, end_date=?, duration_days=?,
            travel_mode=?, budget_label=?, budget_min=?, budget_max=?,
            group_size_label=?, group_size_min=?, group_size_max=?,
            preferred_gender=?, preferred_age=?,
            trip_style=?, description=?, collaborator_id=?, updated_at=NOW()
        WHERE id=? AND host_id=?
    ");

    $stmt->bind_param(
        "ssssssissiisiiissssiii",
        $trip_name,
        $destination,
        $start_place,
        $end_place,
        $start_date,
        $end_date,
        $duration_days,
        $travel_mode,
        $budget_label,
        $budget_min,
        $budget_max,
        $group_size_label,
        $group_size_min,
        $group_size_max,
        $preferred_gender,
        $preferred_age,
        $trip_style,
        $description,
        $collaborator_id,
        $trip_id,
        $user_id
    );

    if ($stmt->execute()) {
        header("Location: viewTrip.php?id=$trip_id&success=Trip updated");
        exit;
    } else {
        $error = "Update failed: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Trip</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #E8F4F8;
            padding: 20px;
        }

        .form-container {
            max-width: 950px;
            margin: auto;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
        }

        h2 {
            color: #2A7B9B;
            font-weight: 700;
            margin-bottom: 25px;
        }

        .section-title {
            font-weight: 600;
            margin-top: 25px;
            margin-bottom: 15px;
            color: #57C785;
        }

        .form-label {
            font-weight: 500;
        }

        .btn-container {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-save {
            background: #57C785;
            color: white;
            font-weight: 600;
            width: 50%;
            border-radius: 8px;
            padding: 12px;
            border: none;
            transition: background 0.3s;
        }

        .btn-save:hover {
            background: #45b76a;
        }

        .btn-secondary {
            width: 50%;
            border-radius: 8px;
            padding: 12px;
            border: none;
            background: #6c757d;
            color: white;
            transition: background 0.3s;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .form-select,
        .form-control {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 10px;
        }
    </style>
</head>

<body>
    <div class="form-container">
        <h2>Edit Trip</h2>
        <?php if (isset($error) && $error) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Trip Name *</label>
                <input type="text" name="trip_name" class="form-control" value="<?= htmlspecialchars($trip['trip_name']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Destination *</label>
                <input type="text" name="destination" class="form-control" value="<?= htmlspecialchars($trip['destination']); ?>" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Start Date *</label>
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($trip['start_date']); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">End Date *</label>
                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($trip['end_date']); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Start Place *</label>
                    <input type="text" name="start_place" class="form-control" value="<?= htmlspecialchars($trip['start_place']); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">End Place *</label>
                    <input type="text" name="end_place" class="form-control" value="<?= htmlspecialchars($trip['end_place']); ?>" required>
                </div>
            </div>

            <h5 class="section-title">Preferences</h5>
            <div class="mb-3">
                <label class="form-label">Travel Mode</label>
                <select name="travel_mode" class="form-select">
                    <?php foreach (["Flight", "Train", "Car", "Bus", "Bike/Scooter", "Mixed"] as $m): ?>
                        <option value="<?= $m ?>" <?= $trip['travel_mode'] == $m ? 'selected' : '' ?>><?= $m ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Budget Range</label>
                    <select name="budget_range" class="form-select">
                        <?php
                        $budgets = ["Starter (NRS 5,000–15,000)", "Economy (NRS 15,000–40,000)", "Standard (NRS 40,000–80,000)", "Comfort (NRS 80,000–150,000)", "Premium (NRS 150,000+)"];
                        foreach ($budgets as $b) echo "<option value='$b' " . ($trip['budget_label'] == $b ? 'selected' : '') . ">$b</option>";
                        ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Group Size</label>
                    <select name="group_size" class="form-select">
                        <?php foreach (["5-8", "8-12", "10-15", "15-20", "20+"] as $s): ?>
                            <option value="<?= $s ?>" <?= $trip['group_size_label'] == $s ? 'selected' : '' ?>><?= $s ?> people</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Trip Style</label>
                <select name="trip_style" class="form-select">
                    <?php
                    $styles = ["Refreshment", "Adventure", "Cultural", "Pilgrimage", "Party & Nightlife", "Nature & Wildlife", "Food & Culinary", "Photography", "Sports & Fitness", "Volunteer & Community"];
                    foreach ($styles as $s) echo "<option value='$s' " . ($trip['trip_style'] == $s ? 'selected' : '') . ">$s</option>";
                    ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($trip['description']); ?></textarea>
            </div>

            <div class="mb-3 mt-3">
                <label class="form-label">Volunteer</label>
                <select name="collaborator_id" class="form-select">
                    <option value="">-- Select from applicants --</option>
                    <?php foreach ($applicants as $app):
                        $sel = $trip['collaborator_id'] == $app['id'] ? 'selected' : '';
                        echo "<option value='{$app['id']}' $sel>" . htmlspecialchars($app['name']) . "</option>";
                    endforeach; ?>
                </select>
            </div>

            <div class="btn-container">
                <button type="submit" class="btn-save">Save Changes</button>
                <a href="viewTrip.php?id=<?= $trip_id ?>" class="btn btn-secondary">Cancel</a>
            </div>

        </form>
    </div>
</body>

</html>