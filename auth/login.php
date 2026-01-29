<?php
session_start();
require '../config/db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            header("Location: " . (strtolower($user['role']) === 'admin' ? '../admin/adminDashboard.php' : '../user/userDashboard.php'));
            exit;
        } else $error = "Invalid password.";
    } else $error = "User not found.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TCFS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #E8F4F8;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            padding: 40px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .login-card h2 {
            font-weight: 700;
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 12px 15px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: #57C785;
            box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.1);
        }

        .form-control::placeholder {
            color: #999;
        }

        .pwd-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #57C785;
        }

        .btn-login {
            background: linear-gradient(135deg, #2A7B9B 0%, #57C785 50%, #EDDD53 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .alert {
            border-radius: 8px;
            border: none;
        }

        .text-center a {
            color: #57C785;
            text-decoration: none;
            font-weight: 600;
        }

        .text-center a:hover {
            color: #57C785;
        }

        .signup-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <h2>Welcome Back</h2>
        <?php if ($error) echo "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> $error</div>"; ?>
        
        <form method="post" autocomplete="off">
            <div class="mb-3">
                <input type="email" name="email" class="form-control" placeholder="Email Address" required autocomplete="email">
            </div>
            
            <div class="mb-3 position-relative">
                <input type="password" id="password" name="password" class="form-control" placeholder="Password" required autocomplete="current-password">
                <span id="togglePassword" class="pwd-toggle">
                    <i class="fas fa-eye"></i>
                </span>
            </div>

            <button type="submit" class="btn btn-login w-100">Login</button>
        </form>

        <div class="signup-link">
            Don't have an account? <a href="register.php" style="color: #57C785; text-decoration:none; font-weight:bold;">Register Now</a>
        </div>
    </div>

    <script>
        document.getElementById("togglePassword").addEventListener("click", function() {
            const pwd = document.getElementById("password");
            const icon = this.querySelector("i");
            if (pwd.type === "password") {
                pwd.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                pwd.type = "password";
                icon.classList.add("fa-eye");
                icon.classList.remove("fa-eye-slash");
            }
        });
    </script>
</body>
</html>