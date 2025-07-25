<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "maindb";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// REMOVE this in production:
// echo "Connected successfully!";
?>
