<?php
session_start();
require "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $destination = trim($_POST['destination'] ?? $_POST['start_place']);
    $start_place = trim($_POST['start_place']);
    $end_place = trim($_POST['end_place']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $trip_style = trim($_POST['trip_style']);
    $budget_range = $_POST['budget_range'];
    $group_size_min = (int) ($_POST['group_size_min'] ?? 0);
    $group_size_max = (int) ($_POST['group_size_max'] ?? 0);
    $description = trim($_POST['description']);
    $travel_mode = $_POST['travel_mode'];
    //$preferred_age = $_POST['preferred_age'] ?? 'Any';
    $age_min = (int) ($_POST['age_min'] ?? 0);
    $age_max = (int) ($_POST['age_max'] ?? 0);
    $preferred_gender = $_POST['preferred_gender'] ?? 'Any';
    $collaborator_id = !empty($_POST['collaborator_id']) ? intval($_POST['collaborator_id']) : NULL;

    $startdate = strtotime($start_date);
    $enddate   = strtotime($end_date);

    if (strlen($name) < 3) {
        $error = "Trip name must be at least 3 characters.";
    } elseif (empty($start_place) || empty($end_place)) {
        $error = "Start and End places are required.";
    } elseif ($enddate < $startdate) {
        $error = "End date cannot be before start date.";
    } elseif (!$startdate || !$enddate) {
        $error = "Please provide valid dates.";
    } elseif ($group_size_min < 5) {
        $error = "Minimum group size must be at least 5 people.";
    } elseif ($group_size_max < $group_size_min) {
        $error = "Maximum group size must be greater than minimum group size.";
    } elseif ($group_size_min <= 0 || $group_size_max <= 0) {
        $error = "Group size must be greater than zero.";
    } elseif (empty($budget_range) || strpos($budget_range, '-') === false) {
        $error = "Please select a valid budget range.";
    } else {
        list($budget_min, $budget_max) = array_map('intval', explode('-', $budget_range));
        //$group_size_label = $group_size_min . '-' . $group_size_max;

        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $interval = $start->diff($end);
        $duration_days = $interval->days + 1;

        $today = strtotime(date('Y-m-d'));
        if ($startdate < $today) {
            $error = "Trip cannot start in the past.";
        }

        $stmt = $conn->prepare("
            INSERT INTO trips(
                host_id, trip_name, destination, start_place, end_place, start_date, end_date,
                duration_days, travel_mode, budget_label, budget_min, budget_max, group_size_min, group_size_max,
                preferred_gender, age_min, age_max, trip_style, description, collaborator_id, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");

        $stmt->bind_param(
            "issssssississsisissii",
            $user_id,
            $name,
            $destination,
            $start_place,
            $end_place,
            $start_date,
            $end_date,
            $duration_days,
            $travel_mode,
            $budget_range,
            $budget_min,
            $budget_max,
            $group_size_min,
            $group_size_max,
            $preferred_gender,
            $age_min,
            $age_max,
            $trip_style,
            $description,
            $collaborator_id
        );

        if ($stmt->execute()) {
            header("Location: myTrips.php?success=1");
            exit;
        } else {
            $error = "Error creating trip: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Trip - TCFS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #E8F4F8;
            margin: 0;
            padding: 0;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
        }

        .form-section {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(234, 88, 12, 0.15);
            margin-bottom: 25px;
        }

        .form-section h5 {
            font-weight: 700;
            color: #57C785;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 10px 15px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #57C785;
            box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.15);
        }

        .btn-create {
            background: linear-gradient(135deg, #2A7B9B 0%, #57C785 50%, #EDDD53 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 40px;
            border-radius: 8px;
        }

        .btn-create:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(234, 88, 12, 0.4);
        }

        .alert {
            border-radius: 8px;
            border: none;
        }

        .grid-cols-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        @media (max-width:768px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .grid-cols-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <h2 class="fw-bold">Create New Trip</h2>
            <a href="myTrips.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back</a>
        </div>

        <?php if ($error) echo "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> $error</div>"; ?>

        <form method="post">
            <!-- Trip Details -->
            <div class="form-section">
                <h5><i class="fas fa-info-circle"></i> Trip Details</h5>
                <div class="mb-3">
                    <label class="form-label">Trip Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g., Trek to ABC" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>
                <div class="grid-cols-2">
                    <div>
                        <label class="form-label">Start Place</label>
                        <input type="text" name="start_place" class="form-control" placeholder="e.g., Kathmandu" required value="<?= htmlspecialchars($_POST['start_place'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="form-label">End Place</label>
                        <input type="text" name="end_place" class="form-control" placeholder="e.g., Pokhara" required value="<?= htmlspecialchars($_POST['end_place'] ?? '') ?>">
                    </div>
                </div>
                <div class="mb-3 mt-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="4" placeholder="Add trip highlights, stops, or instructions..." required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Collaborator / Volunteer (Optional)</label>
                    <select name="collaborator_id" class="form-control">
                        <option value="">-- Select from applicants --</option>
                        <?php
                        if (isset($_GET['trip_id'])) {
                            $trip_id = intval($_GET['trip_id']);
                            $stmt = $conn->prepare("SELECT u.id, u.name FROM users u JOIN trip_applications ta ON u.id=ta.user_id WHERE ta.trip_id=?");
                            $stmt->bind_param("i", $trip_id);
                            $stmt->execute();
                            $res = $stmt->get_result();
                            while ($u = $res->fetch_assoc()) {
                                $selected = (($_POST['collaborator_id'] ?? '') == $u['id']) ? 'selected' : '';
                                echo "<option value='{$u['id']}' $selected>" . htmlspecialchars($u['name']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>

            <!-- Travel Dates -->
            <div class="form-section">
                <h5><i class="fas fa-calendar"></i> Travel Dates</h5>
                <div class="grid-cols-2">
                    <div>
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" required value="<?= $_POST['start_date'] ?? '' ?>">
                    </div>
                    <div>
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" required value="<?= $_POST['end_date'] ?? '' ?>">
                    </div>
                </div>
            </div>

            <!-- Budget & Travel Mode -->
            <div class="form-section">
                <h5><i class="fas fa-dollar-sign"></i> Budget & Travel Mode</h5>
                <div class="grid-cols-2">
                    <div>
                        <label class="form-label">Budget Range</label>
                        <select name="budget_range" class="form-select" required>
                            <option value="">Select budget</option>
                            <?php
                            $budgets = ["1000-5000", "5000-10000", "10000-15000", "15000-25000", "25000-40000", "40000-60000", "60000-100000", "100000+"];
                            foreach ($budgets as $b) {
                                $selected = (($_POST['budget_range'] ?? '') == $b) ? 'selected' : '';
                                echo "<option value='$b' $selected>Rs.$b</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Travel Mode</label>
                        <select name="travel_mode" class="form-select" required>
                            <option value="">Select mode</option>
                            <?php
                            $modes = ["Bike", "Car", "Bus", "Train", "Flight", "Jeep", "Walking", "Mixed"];
                            foreach ($modes as $m) {
                                $selected = (($_POST['travel_mode'] ?? '') == $m) ? 'selected' : '';
                                echo "<option value='$m' $selected>$m</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Group & Age Settings -->
            <div class="form-section">
                <h5><i class="fas fa-users"></i> Group & Age Settings</h5>
                <div class="grid-cols-2">
                    <div>
                        <label class="form-label">Min Group Size</label>
                        <input type="number" name="group_size_min" class="form-control" min="5" required value="<?= htmlspecialchars($_POST['group_size_min'] ?? '5') ?>">
                    </div>
                    <div>
                        <label class="form-label">Max Group Size</label>
                        <input type="number" name="group_size_max" class="form-control" min="5" required value="<?= htmlspecialchars($_POST['group_size_max'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="form-label">Minimum Age</label>
                        <input type="number" name="age_min" class="form-control" min="18" max="80" required value="<?= htmlspecialchars($_POST['age_min'] ?? 18) ?>">
                    </div>
                    <div>
                        <label class="form-label">Maximum Age</label>
                        <input type="number" name="age_max" class="form-control" min="18" max="80" required value="<?= htmlspecialchars($_POST['age_max'] ?? 80) ?>">
                    </div>
                </div>
            </div>

            <!-- Preferences -->
            <div class="form-section">
                <h5><i class="fas fa-filter"></i> Preferences</h5>
                <div class="grid-cols-2">
                    <div>
                        <label class="form-label">Preferred Gender</label>
                        <select name="preferred_gender" class="form-select" required>
                            <?php
                            $genders = ["Any", "Male", "Female", "Other"];
                            foreach ($genders as $g) {
                                $selected = (($_POST['preferred_gender'] ?? '') == $g) ? 'selected' : '';
                                echo "<option value='$g' $selected>$g</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Trip Style</label>
                        <select name="trip_style" class="form-select" required>
                            <option value="">Select style</option>
                            <?php
                            $styles = ["Adventure", "Cultural", "Historical", "Nature", "Pilgrimage", "Relaxation", "Volunteer & Community", "Wildlife"];
                            foreach ($styles as $s) {
                                $selected = (($_POST['trip_style'] ?? '') == $s) ? 'selected' : '';
                                echo "<option value='$s' $selected>$s</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end mb-5">
                <a href="myTrips.php" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-create"><i class="fas fa-check"></i> Create Trip</button>
            </div>
        </form>
    </div>
</body>

</html>