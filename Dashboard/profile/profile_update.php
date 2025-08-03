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

$conn->begin_transaction();
try {
    // 1) Update USERS
    if (isset($_POST['name'], $_POST['email'])) {
        $u = $conn->prepare("UPDATE USERS SET name = ?, email = ? WHERE userID = ?");
        $u->bind_param('ssi', $_POST['name'], $_POST['email'], $userID);
        if (!$u->execute()) throw new Exception($u->error);
        $u->close();
    }

    // 2) Prepare FARMER updates (including optional picture)
    $sets = []; $types = ''; $vals = [];

    // Handle file upload
    if (!empty($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $tmp  = $_FILES['profile_picture']['tmp_name'];
        $ext  = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $name = 'user_'.$userID.'_'.time().'.'.$ext;
        $dest = $uploadDir . $name;

        if (!move_uploaded_file($tmp, $dest)) {
            throw new Exception('Failed to move uploaded file.');
        }

        // store relative path under /Dashboard/profile/
        $sets[]  = "profile_picture = ?";
        $types  .= 's';
        $vals[]  = 'uploads/'.$name;
    }

    // Other farmer fields
    $fields = [
      'address_line1','address_line2','city','state',
      'postal_code','country','phone','date_of_birth',
      'gender','farm_name','farm_size','years_experience'
    ];
    foreach ($fields as $f) {
        if (isset($_POST[$f])) {
            $sets[]    = "{$f} = ?";
            $types    .= in_array($f,['farm_size','years_experience'])?'d':'s';
            $vals[]    = $_POST[$f];
        }
    }

    if ($sets) {
        $types .= 'i';        // for userID
        $vals[] = $userID;

        $sql = 'UPDATE FARMER SET ' . implode(', ', $sets) . ' WHERE userID = ?';
        $fstmt = $conn->prepare($sql);
        $fstmt->bind_param($types, ...$vals);
        if (!$fstmt->execute()) throw new Exception($fstmt->error);
        $fstmt->close();
    }

    $conn->commit();
    echo json_encode(['success'=>true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success'=>false,'message'=>'Update failed: '.$e->getMessage()]);
}
