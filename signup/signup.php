<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 0) Include your existing MySQLi connection
include '../main.php';  // ← adjust path if needed

// 1) Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// 2) Collect & trim
$fullname = trim($_POST['fullname'] ?? '');
$email    = trim($_POST['email']    ?? '');
$password =      $_POST['password'] ?? '';
$role     = trim($_POST['role']     ?? '');

// 3) Basic validation
if ($fullname === '' || $email === '' || $password === '' || $role === '') {
    echo json_encode(['success' => false, 'message' => 'Please fill all fields.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
    exit;
}
if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
    exit;
}
$allowed = ['user','admin'];
if (!in_array($role, $allowed, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid role selected.']);
    exit;
}

// 4) Hash password
$hashed = password_hash($password, PASSWORD_DEFAULT);

// ------------------------------------------------------------------
// 5) INSERT INTO users
// ------------------------------------------------------------------
// adjust these to match your actual table + column names
$tbl_users   = 'users';      // or 'USERS'
$col_name    = 'name';       // your column for full name
$col_email   = 'email';
$col_pass    = 'password';   // if your column is literally named "password"
$col_role    = 'role';

$sql1 = "
    INSERT INTO `{$tbl_users}` 
      (`{$col_name}`, `{$col_email}`, `{$col_pass}`, `{$col_role}`)
    VALUES (?, ?, ?, ?)
";
if (! $stmt = $conn->prepare($sql1)) {
    echo json_encode([
      'success' => false,
      'message' => 'Prepare failed (users): ' . $conn->error
    ]);
    exit;
}
$stmt->bind_param('ssss', $fullname, $email, $hashed, $role);
if (! $stmt->execute()) {
    $msg = $conn->errno === 1062
         ? 'Email already in use.'
         : 'User insert error: ' . $stmt->error;
    echo json_encode(['success'=>false,'message'=>$msg]);
    exit;
}
$userID = $conn->insert_id;
$stmt->close();

// ------------------------------------------------------------------
// 6) INSERT INTO farmer
// ------------------------------------------------------------------
// adjust these to match your actual table + column names
$tbl_farmer = 'farmer';  // or 'FARMER'
$sql2 = "
    INSERT INTO `{$tbl_farmer}` 
      (`userID`, `regionID`, `phone`)
    VALUES (?, NULL, '')
";
if (! $stmt = $conn->prepare($sql2)) {
    echo json_encode([
      'success' => false,
      'message' => 'Prepare failed (farmer): ' . $conn->error
    ]);
    exit;
}
$stmt->bind_param('i', $userID);
if (! $stmt->execute()) {
    echo json_encode([
      'success'=>false,
      'message'=>'Farmer insert error: '.$stmt->error
    ]);
    exit;
}
$stmt->close();
$conn->close();

// 7) Success!
header('Location: ../login/login.php');
exit;
