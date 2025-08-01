<?php
session_start();
header('Content-Type: application/json');
include '../main.php';

// Guard: must be logged in
if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['success'=>false,'message'=>'Unauthorized']);
  exit;
}
$userID = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  // Fetch both USERS.name and FARMER.* fields
  $stmt = $conn->prepare("
    SELECT U.name,
           F.address_line1,
           F.address_line2,
           F.city,
           F.state,
           F.postal_code,
           F.country,
           F.phone,
           F.date_of_birth,
           F.gender,
           F.farm_name,
           F.farm_size,
           F.years_experience
      FROM FARMER F
      JOIN USERS U ON F.userID = U.userID
     WHERE F.userID = ?
  ");
  $stmt->bind_param('i', $userID);
  $stmt->execute();
  $data = $stmt->get_result()->fetch_assoc() ?: [];
  echo json_encode($data);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Allowed fields in FARMER
  $fields = [
    'address_line1','address_line2','city','state',
    'postal_code','country','phone','date_of_birth',
    'gender','farm_name','farm_size','years_experience'
  ];

  // Build dynamic SET
  $sets = []; $types = ''; $values = [];
  foreach ($fields as $f) {
    if (isset($_POST[$f])) {
      $sets[]   = "F.`$f` = ?";
      // farm_size & years_experience are floats
      $types   .= in_array($f, ['farm_size','years_experience']) ? 'd' : 's';
      $values[] = $_POST[$f];
    }
  }

  // Always allow changing USERS.name
  if (isset($_POST['name'])) {
    $u = $conn->prepare("UPDATE USERS SET `name` = ? WHERE userID = ?");
    $u->bind_param('si', $_POST['name'], $userID);
    $u->execute();
    $u->close();
  }

  if ($sets) {
    $sql  = 'UPDATE FARMER F SET '.implode(', ', $sets).' WHERE F.userID = ?';
    $stmt = $conn->prepare($sql);
    $types    .= 'i';
    $values[]  = $userID;
    $stmt->bind_param($types, ...$values);
    $stmt->execute();
    $stmt->close();
  }

  echo json_encode(['success'=>true]);
  exit;
}

// Other methods not allowed
http_response_code(405);
echo json_encode(['success'=>false,'message'=>'Method Not Allowed']);
exit;
