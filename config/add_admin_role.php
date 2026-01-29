<?php
require 'db.php';

try {
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
    
    if($result->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN role ENUM('User', 'Admin') DEFAULT 'User' AFTER email");
        echo "Role column added successfully!<br>";
    } else {
        echo "Role column already exists!<br>";
    }
    
    $check_admin = $conn->query("SELECT id FROM users WHERE email = 'admin@tcfs.com'");
    
    if($check_admin->num_rows == 0) {
        $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
        $conn->query("INSERT INTO users (name, email, password, dob, gender, role) 
                      VALUES ('Admin User', 'admin@tcfs.com', '$hashed_password', '1990-01-01', 'Other', 'Admin')");
        echo "Default admin user created!<br>";
        echo "Email: admin@tcfs.com<br>";
        echo "Password: admin123<br>";
    } else {
        $conn->query("UPDATE users SET role = 'Admin' WHERE email = 'admin@tcfs.com'");
        echo "Admin user already exists and updated!<br>";
    }
    
    echo "<br>Migration completed successfully!";
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
