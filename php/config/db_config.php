<?php
// Database configuration file

$servername = "localhost";  // Your database server
$username = "root";         // Your database username
$password = "";             // Your database password (empty for localhost default)
$dbname = "flight";         // Your database name

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
