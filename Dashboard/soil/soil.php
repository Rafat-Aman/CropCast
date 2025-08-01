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
    <title>Soil Data | CropCast</title>
    <link rel="stylesheet" href="soil.css" />
</head>
<body>
<div class="soil-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar">
        <h2>🌾 CropCast</h2>
        <ul>
            <li><a href="../dashboard.php">📊 Dashboard</a></li>
            <li><a href="../profile/profile.html">👤 Profile</a></li>
            <li><a href="../fields/fields.php">🌱 Fields</a></li>
            <li><a href="../weather/weather.php">☁️ Weather</a></li>
            <li><a href="soil.php" class="active">🧪 Soil Data</a></li>
            <li><a href="../reports/reports.php">📄 Reports</a></li>
            <li><a href="../settings/settings.php">⚙️ Settings</a></li>
            <li><a href="../../logout.php" id="logout-link">🚪 Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <div class="soil-container">
        <header>
            <h1>🧪 Soil Conditions</h1>
            
        </header>

        <section class="soil-section">
            <div class="soil-select">
                <label for="field">Select Field:</label>
                <select id="field">
                    <option value="north">North Plot</option>
                    <option value="south">South Field</option>
                </select>
            </div>

            <div class="soil-data" id="soilOutput">
                <p>Loading soil data...</p>
            </div>
        </section>
    </div>
</div>

<script src="soil.js"></script>
</body>
</html>
