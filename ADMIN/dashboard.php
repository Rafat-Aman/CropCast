<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include '../main.php';
$userID = (int)$_SESSION['user_id'];

// Fetch name and picture path
$stmt = $conn->prepare("
  SELECT U.name,
         F.profile_picture
    FROM USERS U
    JOIN FARMER F ON U.userID = F.userID
   WHERE U.userID = ?
");
$stmt->bind_param('i', $userID);
$stmt->execute();
$stmt->bind_result($name, $picPath);
$stmt->fetch();
$stmt->close();

// Build the URL to the uploaded picture (or a default)
if ($picPath) {
    // assuming uploads live in /Dashboard/profile/uploads/
    $profilePicUrl = "profile/uploads/" . $picPath;
} else {
    $profilePicUrl = "images/default-avatar.png"; // put a default here
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>CropCast Dashboard</title>
    <link rel="stylesheet" href="dashboard.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body>
<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar">
        <h2>🌾 CropCast</h2>
        <ul>
            <li><a href="dashboard.php" id="menu-dashboard" class="active">📊 Dashboard</a></li>
            <li><a href="profile/profile.php" id="menu-profile">👤 Profile</a></li>
            <li><a href="fields/fields.php" id="menu-fields">🌱 Fields</a></li>
            <li><a href="weather/weather.php" id="menu-weather">☁️ Weather</a></li>
            <li><a href="soil/soil.php" id="menu-soil">🧪 Soil Data</a></li>
            <li><a href="reports/reports.php" id="menu-reports">📄 Reports</a></li>
            <li><a href="settings/settings.php" id="menu-settings">⚙️ Settings</a></li>
            <li><a href="../logout.php" id="logout-link">🚪 Logout</a></li>
        </ul>
    </aside>

    <!-- Main content -->
    <div class="dashboard-container">
        <header>
            <h1>CropCast Dashboard</h1>
            <div class="user-info">
                <span>Welcome, <?= htmlspecialchars($name) ?></span>
                <img src="<?= htmlspecialchars($profilePicUrl) ?>"
                     alt="Profile Picture"
                     class="profile-pic" />
            </div>
        </header>

        <div id="main-content">
            <section class="cards">
                <!-- cards as before… -->
            </section>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="dashboard.js"></script>
</body>
</html>
