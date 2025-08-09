<?php
// Database credentials
$servername = "127.0.0.1";
$username = "root"; // আপনার MySQL username
$password = ""; // আপনার MySQL password
$dbname = "bms_db"; // আপনার ডাটাবেসের নাম

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
