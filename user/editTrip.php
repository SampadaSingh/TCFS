<?php
session_start();
require "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$trip_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

$stmt = $conn->prepare("SELECT * FROM trips WHERE id = ? AND host_id = ?");
$stmt->bind_param("ii", $trip_id, $user_id);
$stmt->execute();
$trip = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$trip) {
    die("Trip not found or you don't have permission to edit this trip.");
}

$applicants = [];
$app_stmt = $conn->prepare("
    SELECT u.id, u.name 
    FROM users u
    JOIN trip_applications ta ON u.id = ta.user_id
    WHERE ta.trip_id = ?
    ORDER BY u.name ASC
");
$app_stmt->bind_param("i", $trip_id);
$app_stmt->execute();
$app_result = $app_stmt->get_result();
while ($row = $app_result->fetch_assoc()) {
    $applicants[] = $row;
}
$app_stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trip_name = trim($_POST['trip_name'] ?? '');
    $destination = trim($_POST['destination'] ?? '');
    $start_place = trim($_POST['start_place'] ?? '');
    $end_place = trim($_POST['end_place'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $travel_mode = $_POST['travel_mode'] ?? 'Mixed';
    $trip_style = $_POST['trip_style'] ?? 'Adventure';
    $description = trim($_POST['description'] ?? '');
    $preferred_age = $_POST['preferred_age'] ?? 'Any';
    $preferred_gender = $_POST['preferred_gender'] ?? 'Any';
    $budget_label = $_POST['budget_range'] ?? '';
    $group_size_min = isset($_POST['group_size_min']) ? (int)$_POST['group_size_min'] : 5;
    $group_size_max = isset($_POST['group_size_max']) ? (int)$_POST['group_size_max'] : null;
    $collaborator_id = !empty($_POST['collaborator_id']) ? intval($_POST['collaborator_id']) : NULL;

    $startdate = strtotime($start_date);
    $enddate = strtotime($end_date);

    if (strlen($trip_name) < 3) {
        $error = "Trip name must be at least 3 characters.";
    } elseif (empty($start_place) || empty($end_place)) {
        $error = "Start and End places are required.";
    } elseif (!$startdate || !$enddate) {
        $error = "Please provide valid dates.";
    } elseif ($enddate < $startdate) {
        $error = "End date cannot be before start date.";
    } elseif ($group_size_min < 5) {
        $error = "Minimum group size must be at least 5 people.";
    } elseif ($group_size_max !== null && $group_size_max < $group_size_min) {
        $error = "Maximum group size must be greater than or equal to minimum group size.";
    }

    if (!$error) {
        $duration_days = (int)(($enddate - $startdate) / 86400) + 1;
        $budget_min = 0;
        $budget_max = 0;
        if (!empty($budget_label)) {
            preg_match_all('/\d+/', str_replace(',', '', $budget_label), $budget_matches);
            $budget_min = isset($budget_matches[0][0]) ? intval($budget_matches[0][0]) : 0;
            $budget_max = isset($budget_matches[0][1]) ? intval($budget_matches[0][1]) : $budget_min;
        }

        $group_size_label = $group_size_min;
        if ($group_size_max !== null) {
            $group_size_label .= '-' . $group_size_max;
        } else {
            $group_size_label .= '+';
        }

        $update_stmt = $conn->prepare("
            UPDATE trips SET
                trip_name = ?,
                destination = ?,
                start_place = ?,
                end_place = ?,
                start_date = ?,
                end_date = ?,
                duration_days = ?,
                travel_mode = ?,
                budget_label = ?,
                budget_min = ?,
                budget_max = ?,
                group_size_label = ?,
                group_size_min = ?,
                group_size_max = ?,
                preferred_gender = ?,
                preferred_age = ?,
                trip_style = ?,
                description = ?,
                collaborator_id = ?,
                updated_at = NOW()
            WHERE id = ? AND host_id = ?
        ");

        $update_stmt->bind_param(
            "ssssssissiisiiisssiii",
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

        if ($update_stmt->execute()) {
            $update_stmt->close();
            header("Location: viewTrip.php?id=$trip_id&success=1");
            exit;
        } else {
            $error = "Failed to update trip: " . $update_stmt->error;
            $update_stmt->close();
        }
    }

    if ($error) {
        $trip['trip_name'] = $trip_name;
        $trip['destination'] = $destination;
        $trip['start_place'] = $start_place;
        $trip['end_place'] = $end_place;
        $trip['start_date'] = $start_date;
        $trip['end_date'] = $end_date;
        $trip['travel_mode'] = $travel_mode;
        $trip['trip_style'] = $trip_style;
        $trip['description'] = $description;
        $trip['preferred_age'] = $preferred_age;
        $trip['preferred_gender'] = $preferred_gender;
        $trip['budget_label'] = $budget_label;
        $trip['group_size_min'] = $group_size_min;
        $trip['group_size_max'] = $group_size_max;
        $trip['collaborator_id'] = $collaborator_id;
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Trip - TCFS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #E8F4F8;
            margin: 0;
            padding: 0;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
            min-height: 100vh;
        }

        .form-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 35px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #2A7B9B;
            margin: 0;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #57C785;
            margin-top: 30px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #E8F4F8;
        }

        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
        }

        .required::after {
            content: " *";
            color: #dc3545;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 10px 15px;
            transition: all 0.3s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #57C785;
            box-shadow: 0 0 0 3px rgba(87, 199, 133, 0.1);
        }

        .btn-container {
            display: flex;
            gap: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e5e5;
        }

        .btn-save {
            background: #57C785;
            color: white;
            font-weight: 600;
            flex: 1;
            border-radius: 8px;
            padding: 12px 20px;
            border: none;
            transition: all 0.3s;
        }

        .btn-save:hover {
            background: #45b76a;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(87, 199, 133, 0.3);
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
            font-weight: 600;
            flex: 1;
            border-radius: 8px;
            padding: 12px 20px;
            border: none;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s;
        }

        .btn-cancel:hover {
            background: #5a6268;
            color: white;
            transform: translateY(-1px);
        }

        .alert {
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
        }

        .help-text {
            font-size: 13px;
            color: #6c757d;
            margin-top: 4px;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .btn-container {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="form-container">
            <div class="page-header">
                <h1 class="page-title"><i class="fas fa-edit"></i> Edit Trip</h1>
                <a href="viewTrip.php?id=<?= $trip_id ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="editTripForm">
                <div class="mb-3">
                    <label class="form-label required">Trip Name</label>
                    <input type="text" name="trip_name" class="form-control"
                        value="<?= htmlspecialchars($trip['trip_name']) ?>"
                        required minlength="3" placeholder="E.g., Annapurna Base Camp Trek">
                </div>

                <div class="mb-3">
                    <label class="form-label required">Destination</label>
                    <input type="text" name="destination" class="form-control"
                        value="<?= htmlspecialchars($trip['destination']) ?>"
                        required placeholder="E.g., Pokhara, Nepal">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Start Place</label>
                        <input type="text" name="start_place" class="form-control"
                            value="<?= htmlspecialchars($trip['start_place']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">End Place</label>
                        <input type="text" name="end_place" class="form-control"
                            value="<?= htmlspecialchars($trip['end_place']) ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Start Date</label>
                        <input type="date" name="start_date" class="form-control"
                            value="<?= htmlspecialchars($trip['start_date']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">End Date</label>
                        <input type="date" name="end_date" class="form-control"
                            value="<?= htmlspecialchars($trip['end_date']) ?>" required>
                    </div>
                </div>

                <h5 class="section-title"><i class="fas fa-sliders-h"></i> Trip Preferences</h5>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Travel Mode</label>
                        <select name="travel_mode" class="form-select">
                            <?php
                            $modes = ["Bike", "Car", "Bus", "Train", "Flight", "Jeep", "Walking", "Mixed"];
                            foreach ($modes as $mode):
                            ?>
                                <option value="<?= $mode ?>" <?= $trip['travel_mode'] == $mode ? 'selected' : '' ?>>
                                    <?= $mode ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Trip Style</label>
                        <select name="trip_style" class="form-select">
                            <?php
                            $styles = ["Adventure", "Cultural", "Historical", "Nature", "Pilgrimage", "Relaxation", "Volunteer & Community", "Wildlife"];
                            foreach ($styles as $style):
                            ?>
                                <option value="<?= $style ?>" <?= $trip['trip_style'] == $style ? 'selected' : '' ?>>
                                    <?= $style ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Min Group Size</label>
                        <input
                            type="number"
                            name="group_size_min"
                            class="form-control"
                            min="5"
                            required
                            value="<?= htmlspecialchars($_POST['group_size_min'] ?? $trip['group_size_min'] ?? '5') ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Max Group Size</label>
                        <input
                            type="number"
                            name="group_size_max"
                            class="form-control"
                            min="5"
                            required
                            value="<?= htmlspecialchars($_POST['group_size_max'] ?? $trip['group_size_max'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Budget Range</label>
                        <select name="budget_range" class="form-select">
                            <?php
                            $budgets = [
                                "NRS 1,000–5,000",
                                "NRS 5,000–10,000",
                                "NRS 10,000–15,000",
                                "NRS 15,000–25,000",
                                "NRS 25,000–40,000",
                                "NRS 40,000–60,000",
                                "NRS 60,000–100,000",
                                "NRS 100,000+"
                            ];
                            foreach ($budgets as $budget):
                            ?>
                                <option value="<?= $budget ?>" <?= $trip['budget_label'] == $budget ? 'selected' : '' ?>>
                                    <?= $budget ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Preferred Age Group</label>
                        <select name="preferred_age" class="form-select">
                            <?php
                            $ages = ["Any", "18-25", "25-35", "35-50", "50+"];
                            foreach ($ages as $age):
                            ?>
                                <option value="<?= $age ?>" <?= $trip['preferred_age'] == $age ? 'selected' : '' ?>>
                                    <?= $age ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Preferred Gender</label>
                        <select name="preferred_gender" class="form-select">
                            <?php
                            $genders = ["Any", "Male", "Female", "Other"];
                            foreach ($genders as $gender):
                            ?>
                                <option value="<?= $gender ?>" <?= $trip['preferred_gender'] == $gender ? 'selected' : '' ?>>
                                    <?= $gender ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="5"
                        placeholder="Describe your trip, activities, and what makes it special..."><?= htmlspecialchars($trip['description']) ?></textarea>
                    <div class="help-text">Provide details about the trip, activities, and expectations.</div>
                </div>

                <?php if (!empty($applicants)): ?>
                    <h5 class="section-title"><i class="fas fa-user-friends"></i> Collaborator / Volunteer</h5>
                    <div class="mb-3">
                        <label class="form-label">Select Collaborator</label>
                        <select name="collaborator_id" class="form-select">
                            <option value="">-- None --</option>
                            <?php foreach ($applicants as $applicant): ?>
                                <option value="<?= $applicant['id'] ?>"
                                    <?= $trip['collaborator_id'] == $applicant['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($applicant['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="help-text">Choose a volunteer from your trip applicants to help organize.</div>
                    </div>
                <?php endif; ?>

                <div class="btn-container">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="viewTrip.php?id=<?= $trip_id ?>" class="btn-cancel">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

</body>

</html>