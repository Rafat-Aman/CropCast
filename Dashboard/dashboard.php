<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/index.php');
    exit();
}

include '../main.php'; // DB connection file

$user_id = $_SESSION['user_id'];
$user_query = $conn->prepare("SELECT fullname FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_query->bind_result($fullname);
$user_query->fetch();
$user_query->close();
?>

