<?php
session_start();
// Choose ONE response style: JSON or redirect. This example uses JSON:
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../main.php'; // adjust if your signup.php lives elsewhere

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// Collect
$fullname = trim($_POST['fullname'] ?? '');
$email    = trim($_POST['email']    ?? '');
$password =        $_POST['password'] ?? '';
$role     = strtolower(trim($_POST['role'] ?? ''));

// Validate
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
if (!in_array($role, ['user','admin'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid role selected.']);
    exit;
}

$hashed = password_hash($password, PASSWORD_DEFAULT);

// Table names (adjust case to match your schema; MySQL on Windows is case-insensitive)
$tbl_users  = 'USERS';
$tbl_farmer = 'FARMER';
$tbl_admin  = 'ADMIN';

// Start transaction so all-or-nothing
$conn->begin_transaction();
try {
    // Insert USERS
    $sqlUser = "INSERT INTO `$tbl_users` (`name`,`email`,`password`,`role`) VALUES (?,?,?,?)";
    $st = $conn->prepare($sqlUser);
    if (!$st) throw new Exception('Prepare users failed: '.$conn->error);
    $st->bind_param('ssss', $fullname, $email, $hashed, $role);
    if (!$st->execute()) {
        if ($conn->errno === 1062) {
            throw new Exception('Email already in use.');
        }
        throw new Exception('User insert error: '.$st->error);
    }
    $userID = $conn->insert_id;
    $st->close();

    // Role-specific inserts
    if ($role === 'user') {
        // Insert FARMER (minimal placeholder row)
        $sqlFarmer = "INSERT INTO `$tbl_farmer` (`userID`,`regionID`,`phone`) VALUES (?, NULL, '')";
        $sf = $conn->prepare($sqlFarmer);
        if (!$sf) throw new Exception('Prepare farmer failed: '.$conn->error);
        $sf->bind_param('i', $userID);
        if (!$sf->execute()) throw new Exception('Farmer insert error: '.$sf->error);
        $sf->close();
    } elseif ($role === 'admin') {
        // Insert ADMIN (adjust columns as your ADMIN table requires)
        $sqlAdmin = "INSERT INTO `$tbl_admin` (`userID`,`profile_pic_path`) VALUES (?, NULL)";
        $sa = $conn->prepare($sqlAdmin);
        if (!$sa) throw new Exception('Prepare admin failed: '.$conn->error);
        $sa->bind_param('i', $userID);
        if (!$sa->execute()) throw new Exception('Admin insert error: '.$sa->error);
        $sa->close();
    }

    // Commit
    $conn->commit();

     header('Location: ../login/login.php'); exit;

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $conn->close();
}
