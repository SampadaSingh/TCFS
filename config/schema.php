<?php
require 'db.php';

function tableExists($conn, $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    return $result && $result->num_rows > 0;
}

try {
    if (!tableExists($conn, 'users')) {
        $conn->query("
            CREATE TABLE users (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                dob DATE NOT NULL,
                gender ENUM('Male', 'Female', 'Other') NOT NULL,
                bio TEXT NULL,
                interests TEXT NULL,
                budget INT NULL,
                travel_mode VARCHAR(50) NULL,
                available_from DATE NULL,
                available_to DATE NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    if (!tableExists($conn, 'trips')) {
        $conn->query("
            CREATE TABLE trips (
                id INT PRIMARY KEY AUTO_INCREMENT,
                host_id INT NOT NULL,
                name VARCHAR(200) NOT NULL,
                description TEXT NOT NULL,
                destination VARCHAR(100) NOT NULL,
                region VARCHAR(100) NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                budget_min INT NOT NULL,
                budget_max INT NULL,
                trip_style VARCHAR(100) NULL,
                travel_mode VARCHAR(50) NULL,
                group_size_min INT DEFAULT 5,
                group_size_max INT DEFAULT 20,
                preferences TEXT NULL,
                accepted_count INT DEFAULT 0,
                status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
                collaborator_id INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (host_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (collaborator_id) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_host (host_id),
                INDEX idx_destination (destination),
                INDEX idx_start_date (start_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    if (!tableExists($conn, 'trip_applications')) {
        $conn->query("
            CREATE TABLE trip_applications (
                id INT PRIMARY KEY AUTO_INCREMENT,
                trip_id INT NOT NULL,
                user_id INT NOT NULL,
                compatibility_score INT DEFAULT 0,
                status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
                applied_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                reviewed_date TIMESTAMP NULL,
                FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY unique_application (trip_id, user_id),
                INDEX idx_trip (trip_id),
                INDEX idx_user (user_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    if (!tableExists($conn, 'contact_requests')) {
        $conn->query("
            CREATE TABLE contact_requests (
                id INT PRIMARY KEY AUTO_INCREMENT,
                trip_id INT NOT NULL,
                sender_id INT NOT NULL,
                recipient_id INT NOT NULL,
                message TEXT NOT NULL,
                status ENUM('pending', 'sent', 'seen') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
                FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_trip (trip_id),
                INDEX idx_sender (sender_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    if (!tableExists($conn, 'user_availability')) {
        $conn->query("
            CREATE TABLE user_availability (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                available_from DATE,
                available_to DATE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    echo "Database schema initialized successfully!";

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
