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
    <title>Reports | CropCast</title>
    <link rel="stylesheet" href="reports.css" />
</head>
<body>
<div class="reports-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar">
        <h2>🌾 CropCast</h2>
        <ul>
            <li><a href="../dashboard.php">📊 Dashboard</a></li>
            <li><a href="../profile/profile.html">👤 Profile</a></li>
            <li><a href="../fields/fields.php">🌱 Fields</a></li>
            <li><a href="../weather/weather.php">☁️ Weather</a></li>
            <li><a href="../soil/soil.php">🧪 Soil Data</a></li>
            <li><a href="reports.php" class="active">📄 Reports</a></li>
            <li><a href="../settings/settings.php">⚙️ Settings</a></li>
            <li><a href="../../logout.php" id="logout-link">🚪 Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <div class="reports-container">
        <header>
            <h1>📄 Field Reports</h1>
            
        </header>

        <section class="reports-section">
            <h2>Recent Reports</h2>
            <ul id="reportList">
                <li>Loading reports...</li>
            </ul>
            <button id="downloadBtn">📥 Download All Reports (PDF)</button>
        </section>
    </div>
</div>

<script src="reports.js"></script>
</body>
</html>
