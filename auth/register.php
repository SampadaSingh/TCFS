<?php
require '../config/db.php';
$error = '';
$name = $email = $dob = $gender = $location = '';
$interests = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $location = $_POST['location'] ?? null;
    $interests = isset($_POST['interests']) ? array_map('intval', $_POST['interests']) : [];

    // Validation
    if ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (!preg_match("/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/", $password)) {
        $error = "Password must contain letter, number, special char and be 8+ chars.";
    } elseif (count($interests) < 3) {
        $error = "Select at least 3 interests.";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email=?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = "Email already exists.";
        } else {
            $birthDate = new DateTime($dob);
            $today = new DateTime();
            $age = $today->diff($birthDate)->y;

            if ($age < 18) {
                $error = "You must be 18+ to register.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $role = 'User';
                $stmt = $conn->prepare("INSERT INTO users (name,email,password,dob,gender,role,age,location) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->bind_param("ssssssis", $name, $email, $hashed, $dob, $gender, $role, $age, $location);

                if ($stmt->execute()) {
                    $user_id = $conn->insert_id;

                    if (!empty($interests)) {
                        $interestStmt = $conn->prepare("INSERT INTO user_interests (user_id, interest_id) VALUES (?, ?)");
                        foreach ($interests as $interest_id) {
                            $interestStmt->bind_param("ii", $user_id, $interest_id);
                            $interestStmt->execute();
                        }
                    }

                    header("Location: login.php?success=1");
                    exit;
                } else {
                    $error = "Registration failed, please try again.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #E8F4F8;
            font-family: 'Segoe UI', sans-serif;
        }

        .card {
            width: 520px;
            margin: 50px auto;
            background: #fff;
            padding: 35px;
            border-radius: 14px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
        }

        .group {
            margin-bottom: 14px;
            position: relative;
        }

        input,
        select {
            width: 100%;
            padding: 11px;
            border-radius: 8px;
            border: 1px solid #ddd;
            box-sizing: border-box;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #57C785;
        }

        button {
            padding: 11px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
        }

        .primary {
            background: #57C785;
            color: #fff;
            width: 100%;
        }

        .secondary {
            background: #eee;
            width: 100%;
        }

        .server-error {
            background: #ffe3e3;
            color: #900;
            padding: 8px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .checkbox-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px 20px;
            margin-bottom: 20px;
        }

        .checkbox-grid .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-grid .form-check input {
            width: auto;
            padding: 0;
            margin: 0;
            cursor: pointer;
        }

        .checkbox-grid .form-check label {
            margin: 0;
            cursor: pointer;
            font-size: 14px;
        }

        .password-wrap {
            position: relative;
        }

        .password-wrap input {
            padding-right: 40px;
        }

        .password-wrap .toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #57C785;
            font-size: 18px;
        }
    </style>
</head>

<body>

    <div class="card">
        <h2>Create Account</h2>
        <?php if ($error) echo "<div class='server-error'>$error</div>"; ?>

        <form method="post" id="form" novalidate>
            <div class="group">
                <input name="name" placeholder="Full Name" value="<?= htmlspecialchars($name); ?>" required>
            </div>
            <div class="group">
                <input name="email" type="email" placeholder="Email" value="<?= htmlspecialchars($email); ?>" required>
            </div>
            <div class="group password-wrap">
                <input name="password" type="password" placeholder="Password" id="password" value="<?= htmlspecialchars($password); ?>" required>
                <i class="fa-solid fa-eye toggle" onclick="togglePassword('password',this)"></i>
            </div>
            <div class="group password-wrap">
                <input name="confirm_password" type="password" placeholder="Confirm Password" id="confirm" value="<?= htmlspecialchars($confirm); ?>" required>
                <i class="fa-solid fa-eye toggle" onclick="togglePassword('confirm',this)"></i>
            </div>
            <div class="group">
                <input name="location" placeholder="Location" value="<?= htmlspecialchars($location); ?>" required>
            </div>
            <div class="group">
                <input name="dob" type="date" value="<?= htmlspecialchars($dob); ?>" required>
            </div>
            <div class="group">
                <select name="gender" required>
                    <option value="">Select Gender</option>
                    <option value="Male" <?= $gender === 'Male' ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?= $gender === 'Female' ? 'selected' : ''; ?>>Female</option>
                    <option value="Other" <?= $gender === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>

            <h4>Select Interests (min 3)</h4>
            <div class="checkbox-grid">
                <?php
                $allInterests = $conn->query("SELECT id, interest_name FROM interests ORDER BY interest_name ASC");
                while ($row = $allInterests->fetch_assoc()):
                ?>
                    <div class="form-check">
                        <input type="checkbox" name="interests[]" value="<?= $row['id']; ?>" <?= in_array($row['id'], $interests) ? 'checked' : ''; ?>>
                        <label><?= htmlspecialchars($row['interest_name']); ?></label>
                    </div>
                <?php endwhile; ?>
            </div>

            <div style="display:flex; gap:10px;">
                <button type="submit" class="primary">Register</button>
            </div>
            <div style="margin-top:10px; text-align:center;">
                Already have an account? <a href="login.php" style="text-decoration:none; color:#57C785;">Login</a>
            </div>
        </form>
    </div>

    <script>
        function togglePassword(id, icon) {
            const input = document.getElementById(id);
            if (input.type === "password") {
                input.type = "text";
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = "password";
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>

</body>

</html>