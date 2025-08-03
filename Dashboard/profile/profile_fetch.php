<?php
session_start();
header('Content-Type: application/json');
include '../../main.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit;
}
$userID = (int)$_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT F.farmerID,
            U.name       AS name,
            U.email      AS email,
            F.profile_picture,
            F.address_line1,
            F.address_line2,
            F.city,
            F.state,
            F.postal_code,
            F.country,
            F.phone,
            DATE_FORMAT(F.date_of_birth,'%Y-%m-%d') AS date_of_birth,
            F.gender,
            F.farm_name,
            F.farm_size,
            F.years_experience
     FROM FARMER F
     JOIN USERS U ON F.userID = U.userID
     WHERE F.userID = ?"
);
$stmt->bind_param('i', $userID);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

// If there's a stored path, prefix it with the correct URL base
if (!empty($data['profile_picture'])) {
    // assuming your web server serves /Dashboard/profile/uploads/...
    $data['profile_picture'] = "../profile/" . $data['profile_picture'];
} else {
    $data['profile_picture'] = null;
}

echo json_encode(['success'=>true,'data'=>$data]);
