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
    <title>Weather | CropCast</title>
    <link rel="stylesheet" href="weather.css" />
</head>
<body>
<div class="weather-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar">
        <h2>🌾 CropCast</h2>
        <ul>
            <li><a href="../dashboard.php">📊 Dashboard</a></li>
            <li><a href="../profile/profile.html">👤 Profile</a></li>
            <li><a href="../fields/fields.php">🌱 Fields</a></li>
            <li><a href="weather.php" class="active">☁️ Weather</a></li>
            <li><a href="../soil/soil.php">🧪 Soil Data</a></li>
            <li><a href="../reports/reports.php">📄 Reports</a></li>
            <li><a href="../settings/settings.php">⚙️ Settings</a></li>
            <li><a href="../../logout.php" id="logout-link">🚪 Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <div class="weather-container">
        <header>
            <h1>☁️ Weather Forecast</h1>
            
        </header>

        <section class="weather-section">
            <div class="location-selector">
                <label for="location">Select Location:</label>
                <select id="location">
                    <option value="dhaka">Dhaka</option>
                    <option value="rajshahi">Rajshahi</option>
                    <option value="sylhet">Sylhet</option>
                </select>
            </div>

            <div class="weather-data" id="weatherOutput">
                <p>Loading weather...</p>
            </div>
        </section>
    </div>
</div>

<script src="weather.js"></script>
</body>
</html>
