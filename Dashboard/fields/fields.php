<?php
session_start();
include '../../main.php';
if (!isset($_SESSION['user_id'])) {
  header("Location: ../login/login.html");
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Fields | CropCast</title>
    <link rel="stylesheet" href="fields.css" />
</head>
<body>
<div class="fields-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar">
        <h2>🌾 CropCast</h2>
        <ul>
            <li><a href="../dashboard.php">📊 Dashboard</a></li>
            <li><a href="../profile/profile.html">👤 Profile</a></li>
            <li><a href="fields.php" class="active">🌱 Fields</a></li>
            <li><a href="../weather/weather.php">☁️ Weather</a></li>
            <li><a href="../soil/soil.php">🧪 Soil Data</a></li>
            <li><a href="../reports/reports.php">📄 Reports</a></li>
            <li><a href="../settings/settings.php">⚙️ Settings</a></li>
            <li><a href="../../logout.php" id="logout-link">🚪 Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <div class="fields-container">
        <header>
            <h1>🌱 Your Fields</h1>
            
        </header>

        <section class="field-list">
            <h2>Registered Fields</h2>
            <ul id="fieldData">
                <li>Loading fields...</li>
            </ul>
            <button id="addFieldBtn">+ Add New Field</button>
        </section>
    </div>
</div>

<script src="fields.js"></script>
</body>
</html>
