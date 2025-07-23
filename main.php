<?php
$host = "localhost";      // Database host (usually localhost in XAMPP)
$username = "root";       // MySQL username (default is root)
$password = "";           // MySQL password (empty by default in XAMPP)
$database = "maindb";    // Your database name

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "Connected successfully!";
}
?>
