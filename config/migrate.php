<?php
require 'db.php';

// Add bio column to users table if it doesn't exist
$checkBio = $conn->query("SHOW COLUMNS FROM users LIKE 'bio'");

if($checkBio->num_rows == 0) {
    $sql = "ALTER TABLE users ADD COLUMN bio LONGTEXT NULL";
    
    if($conn->query($sql) === TRUE) {
        echo "Successfully added 'bio' column to users table.<br>";
    } else {
        echo "Error adding bio column: " . $conn->error . "<br>";
    }
}

// Add interests column to users table if it doesn't exist
$checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'interests'");

if($checkColumn->num_rows == 0) {
    $sql = "ALTER TABLE users ADD COLUMN interests LONGTEXT NULL";
    
    if($conn->query($sql) === TRUE) {
        echo "Successfully added 'interests' column to users table.";
    } else {
        echo "Error adding interests column: " . $conn->error;
    }
} else {
    echo "Column 'interests' already exists.";
}

// Add trip_image column to trips table if it doesn't exist
$checkTripImage = $conn->query("SHOW COLUMNS FROM trips LIKE 'trip_image'");

if ($checkTripImage->num_rows == 0) {
    $sql = "ALTER TABLE trips ADD COLUMN trip_image VARCHAR(255) NULL";

    if ($conn->query($sql) === TRUE) {
        echo "Successfully added 'trip_image' column to trips table.<br>";
    } else {
        echo "Error adding trip_image column: " . $conn->error . "<br>";
    }
} else {
    echo "Column 'trip_image' already exists in trips table.<br>";
}

$conn->close();
?>
