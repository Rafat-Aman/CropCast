<?php
// Dummy DB connection - replace with real DB in production
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dashboard";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check DB connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Simulate session user ID
$user_id = 1;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = $_POST['name'];
    $email    = $_POST['email'];
    $pass     = $_POST['password'];
    $location = $_POST['location'];
    $age      = $_POST['age'];

    $sql = "UPDATE users SET 
            name='$name',
            email='$email',
            password='$pass',
            location='$location',
            age=$age 
            WHERE id=$user_id";

    if ($conn->query($sql) === TRUE) {
        echo "Profile updated successfully.";
    } else {
        echo "Error updating profile: " . $conn->error;
    }
}

// Pre-fill form
$sql = "SELECT name, email, password, location, age FROM users WHERE id=$user_id";
$result = $conn->query($sql);
$user = $result->fetch_assoc();

echo "<script>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('name').value = '{$user['name']}';
            document.getElementById('email').value = '{$user['email']}';
            document.getElementById('password').value = '{$user['password']}';
            document.getElementById('location').value = '{$user['location']}';
            document.getElementById('age').value = {$user['age']};
        });
      </script>";
$conn->close();
?>
