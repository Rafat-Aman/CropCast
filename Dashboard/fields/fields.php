<?php
session_start();
header('Content-Type: application/json');
include '../../main.php';

// Session guard
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userID = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare(
        "SELECT F.farmerID,
                U.name AS name,
                U.email AS email,
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
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update USERS (name, email)
    if (isset($_POST['name'], $_POST['email'])) {
        $uStmt = $conn->prepare("UPDATE USERS SET name = ?, email = ? WHERE userID = ?");
        $uStmt->bind_param('ssi', $_POST['name'], $_POST['email'], $userID);
        $uStmt->execute();
        $uStmt->close();
    }

    // Prepare FARMER update
    $fields = ['address_line1','address_line2','city','state',
               'postal_code','country','phone','date_of_birth',
               'gender','farm_name','farm_size','years_experience'];
    $sets = [];
    $types = '';
    $values = [];
    foreach ($fields as $f) {
        if (isset($_POST[$f])) {
            $sets[] = "{$f} = ?";
            $types .= in_array($f, ['farm_size','years_experience']) ? 'd' : 's';
            $values[] = $_POST[$f];
        }
    }
    if ($sets) {
        $sql = 'UPDATE FARMER SET ' . implode(', ', $sets) . ' WHERE userID = ?';
        $types .= 'i';
        $values[] = $userID;
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode(['success' => true]);
    exit;
}

// Method not allowed
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
exit;
