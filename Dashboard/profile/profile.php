<?php
session_start();
header('Content-Type: application/json');
include '../../main.php';

// Guard: must be logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['message'=>'Unauthorized']);
    exit;
}

$userID = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // join USERS + FARMER
    $stmt = $conn->prepare("
      SELECT U.name           AS fullname,
             U.email          AS email,
             F.address_line1  AS address1,
             F.address_line2  AS address2,
             F.city           AS city,
             F.state          AS state,
             F.postal_code    AS postal,
             F.country        AS country,
             F.phone          AS phone,
             DATE_FORMAT(F.date_of_birth,'%Y-%m-%d') AS dob,
             F.gender         AS gender,
             F.farm_name      AS farmName,
             F.farm_size      AS farmSize,
             F.years_experience AS expYears
        FROM FARMER F
        JOIN USERS U ON F.userID = U.userID
       WHERE F.userID = ?
    ");
    $stmt->bind_param('i',$userID);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();
    echo json_encode($result);
    exit;
}

// If we ever wanted POST for updates, handle here...

http_response_code(405);
echo json_encode(['message'=>'Method Not Allowed']);
exit;
