<?php
// Database connection details
$db_host = 'localhost';
$db_user = 'root'; // Default XAMPP user
$db_pass = '';     // Default XAMPP password
$db_name = 'bms_db'; // Your database name

// Establish the database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set for proper data handling
$conn->set_charset("utf8mb4");


?>