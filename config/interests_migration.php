<?php
require 'db.php';

function tableExists($conn, $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    return $result && $result->num_rows > 0;
}

try {
    if (!tableExists($conn, 'interests')) {
        $conn->query("
            CREATE TABLE interests (
                id INT PRIMARY KEY AUTO_INCREMENT,
                interest_name VARCHAR(100) UNIQUE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        $interestsList = [
            'Adventure',
            'Beach',
            'Mountains',
            'Culture',
            'Food',
            'Art',
            'History',
            'Nature',
            'Photography',
            'Music',
            'Sports',
            'Nightlife',
            'Shopping',
            'Relaxation',
            'Wildlife',
            'Hiking',
            'Diving',
            'Camping',
            'City Tour',
            'Festivals'
        ];
        
        foreach($interestsList as $interest) {
            $conn->query("INSERT IGNORE INTO interests (interest_name) VALUES ('$interest')");
        }
    }
    
    if (!tableExists($conn, 'user_interests')) {
        $conn->query("
            CREATE TABLE user_interests (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                interest_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (interest_id) REFERENCES interests(id) ON DELETE CASCADE,
                UNIQUE KEY unique_user_interest (user_id, interest_id),
                INDEX idx_user (user_id),
                INDEX idx_interest (interest_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
    
    echo "Tables created successfully!";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
