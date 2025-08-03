<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
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
            
        </header>

        <div id="main-content">
            <section class="cards">
                <div class="card" id="weatherCard">
                    <h2>Weather Forecast</h2>
                    <p id="weatherData">Loading...</p>
                </div>
                <div class="card">
                    <h2>Suggested Crops</h2>
                    <ul>
                        <li>Tomato</li>
                        <li>Maize</li>
                        <li>Rice (IRRI)</li>
                    </ul>
                </div>
                <div class="card" id="soilCard">
                    <h2>Soil Conditions</h2>
                    <p id="soilData">Loading soil data...</p>
                </div>
                <div class="card">
                    <h2>Farm Map</h2>
                    <div id="map" style="height: 300px;"></div>
                </div>
            </section>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="dashboard.js"></script>
</body>
</html>
