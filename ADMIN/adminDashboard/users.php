<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: ../login/login.html'); // adjust if needed
  exit;
}
include '../../main.php';
echo"this gets userlist";
