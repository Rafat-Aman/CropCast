<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// include DB connection
include '../main.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if user exists
    $stmt = $conn->prepare("SELECT id, fullname, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $fullname, $hashedPassword);
        $stmt->fetch();

        if (password_verify($password, $hashedPassword)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['fullname'] = $fullname;
            header("Location: ../dashboard.html");
            exit;
        } else {
            echo "❌ Incorrect password!";
        }
    } else {
        echo "❌ No user found with that email.";
    }

    $stmt->close();
    $conn->close();
}
?>
