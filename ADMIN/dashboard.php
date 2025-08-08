<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include '../main.php';
$userID = (int)$_SESSION['user_id'];

// Your query
$sql = "SELECT COUNT(*) AS total_farmers FROM USERS WHERE role = 'user'";
$res = $conn->query($sql);
$totalFarmers = 0;
if ($res && $row = $res->fetch_assoc()) {
    $totalFarmers = (int)$row['total_farmers'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="dashboard.css">
</head>

<body>
    <div class="admin-cards">
        <div class="admin-card">
            <h3>Total Farmers</h3>
            <div class="metric"><?php echo $totalFarmers; ?></div>
        </div>
    </div>


</body>

</html>