<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../main.php';

// only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Method Not Allowed']);
    exit;
}

$fullname = trim($_POST['fullname'] ?? '');
$email    = trim($_POST['email']    ?? '');
$password = $_POST['password'] ?? '';

if ($fullname === '' || $email === '' || $password === '') {
    echo json_encode(['success'=>false,'message'=>'Please fill all fields.']);
    exit;
}

// 1) create user
$stmt = $conn->prepare("
    INSERT INTO `USERS` (`name`,`email`,`password`)
    VALUES (?, ?, ?)
");
$hashed = password_hash($password, PASSWORD_DEFAULT);
$stmt->bind_param("sss", $fullname, $email, $hashed);

if (!$stmt->execute()) {
    echo json_encode([
      'success'=>false,
      'message'=>'User insert error: '.$stmt->error
    ]);
    exit;
}

$userID = $conn->insert_id;
$stmt->close();

// 2) create farmer row (with NULL regionID for now)
$stmt = $conn->prepare("
    INSERT INTO `FARMER` (`userID`,`regionID`,`phone`)
    VALUES (?, NULL, '')
");
$stmt->bind_param("i", $userID);

if (!$stmt->execute()) {
    echo json_encode([
      'success'=>false,
      'message'=>'Farmer insert error: '.$stmt->error
    ]);
    exit;
}

$stmt->close();
$conn->close();

// 3) all done!
echo json_encode(['success'=>true]);
