<?php
session_start();
require "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    session_destroy();
    header('Location: ../auth/login.php');
    exit;
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $bio = trim($_POST['bio']);
    $submittedInterests = $_POST['interests'] ?? [];

    $submittedInterests = array_unique(array_map('intval', $submittedInterests));

    $currentInterests = [];
    $res = $conn->query("SELECT interest_id FROM user_interests WHERE user_id = $user_id");
    while ($row = $res->fetch_assoc()) {
        $currentInterests[] = (int)$row['interest_id'];
    }

    $toAdd = array_diff($submittedInterests, $currentInterests);
    $toRemove = array_diff($currentInterests, $submittedInterests);

    if (!empty($toRemove)) {
        $toRemoveList = implode(',', $toRemove);
        $conn->query("DELETE FROM user_interests WHERE user_id = $user_id AND interest_id IN ($toRemoveList)");
    }

    foreach ($toAdd as $interestId) {
        $interestId = (int)$interestId;
        $conn->query("INSERT INTO user_interests (user_id, interest_id) VALUES ($user_id, $interestId)");
    }

    if (!empty($name)) {
        $update_stmt = $conn->prepare("UPDATE users SET name = ?, bio = ? WHERE id = ?");
        $update_stmt->bind_param("ssi", $name, $bio, $user_id);

        if ($update_stmt->execute()) {
            $user['name'] = $name;
            $user['bio'] = $bio;

            header('Location: profile.php?success=1');
            exit;
        } else {
            $message = 'Error updating profile. Please try again.';
            $message_type = 'error';
        }
    } else {
        $message = 'Name cannot be empty.';
        $message_type = 'error';
    }
}

$allInterests = $conn->query("SELECT id, interest_name FROM interests ORDER BY interest_name ASC");

$selected = [];
$res = $conn->query("SELECT interest_id FROM user_interests WHERE user_id = $user_id");
while ($r = $res->fetch_assoc()) {
    $selected[] = $r['interest_id'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - TCFS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', sans-serif;
            padding: 30px 0;
        }

        .profile-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            padding: 40px;
            max-width: 700px;
            margin: 0 auto;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .form-control {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 14px;
        }

        .form-control:focus {
            border-color: #57C785;
            box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.1);
        }

        .form-info {
            font-size: 12px;
            color: #999;
            margin-top: 6px;
        }

        .btn-save {
            background: linear-gradient(135deg, #2A7B9B 0%, #57C785 50%, #EDDD53 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 40px;
            border-radius: 8px;
            margin-top: 20px;
            width: 100%;
        }

        .tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .tag {
            padding: 8px 16px;
            background: #E8F4F8;
            color: #57C785;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            border: 2px solid #D4EDE1;
        }

        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .form-check-input:checked {
            background-color: #57C785;
            border-color: #57C785;
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="profile-container">
            <h2>Edit Profile</h2>
            <?php if ($message): ?>
                <div class="<?= $message_type === 'success' ? 'success-message' : 'error-message' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($user['name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']); ?>" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Location</label>
                    <input type="text" class="form-control" name="location" value="<?= htmlspecialchars($user['location'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" class="form-control" name="dob" value="<?= htmlspecialchars($user['dob'] ?? ''); ?>" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Gender</label>
                    <input type="text" class="form-control" name="gender" value="<?= htmlspecialchars($user['gender'] ?? ''); ?>" disabled>
                </div>  
                <div class="mb-3">
                    <label class="form-label">Bio</label>
                    <textarea class="form-control" name="bio" placeholder="Tell others about yourself..."><?= htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Interests</label>
                    <div class="row">
                        <?php while ($row = $allInterests->fetch_assoc()): ?>
                            <div class="col-md-4 col-6 mb-2">
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="interests[]"
                                        value="<?= $row['id']; ?>"
                                        <?= in_array($row['id'], $selected) ? 'checked' : ''; ?>>
                                    <label class="form-check-label"><?= htmlspecialchars($row['interest_name']); ?></label>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <div class="form-info">Select your interests</div>
                </div>

                <button type="submit" class="btn-save">Save Changes</button>
            </form>
        </div>
    </div>
</body>

</html>