<?php
// profile_update.php
session_start();
header('Content-Type: application/json');
include '../../main.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit;
}
$userID = (int)$_SESSION['user_id'];

// collect POST fields
$name  = $_POST['name']  ?? null;
$email = $_POST['email'] ?? null;

// wrap in transaction
$conn->begin_transaction();

try {
    // 1) USERS
    if ($name !== null || $email !== null) {
        $sets  = [];
        $types = '';
        $vals  = [];
        if ($name  !== null) { $sets[]='name=?';  $types.='s'; $vals[]=$name; }
        if ($email !== null) { $sets[]='email=?'; $types.='s'; $vals[]=$email; }
        $types .= 'i';
        $vals[] = $userID;
        $sql = "UPDATE USERS SET ".implode(',', $sets)." WHERE userID=?";
        $st  = $conn->prepare($sql);
        $st->bind_param($types, ...$vals);
        if (!$st->execute()) throw new Exception($st->error);
        $st->close();
    }

    // 2) FARMER
    $fields = [
      'address_line1','address_line2','city','state',
      'postal_code','country','phone','date_of_birth',
      'gender','farm_name','farm_size','years_experience'
    ];
    $sets=[]; $types=''; $vals=[];
    foreach($fields as $f){
      if(isset($_POST[$f])) {
        $sets[]="$f=?";
        $types .= in_array($f,['farm_size','years_experience'])?'d':'s';
        $vals[] = $_POST[$f];
      }
    }
    if($sets){
      $types.='i'; $vals[]=$userID;
      $sql = "UPDATE FARMER SET ".implode(',', $sets)." WHERE userID=?";
      $st  = $conn->prepare($sql);
      $st->bind_param($types, ...$vals);
      if (!$st->execute()) throw new Exception($st->error);
      $st->close();
    }

    $conn->commit();
    echo json_encode(['success'=>true]);
} catch(Exception $e) {
    $conn->rollback();
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
