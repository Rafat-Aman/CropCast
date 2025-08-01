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
    <title>Settings | CropCast</title>
    <link rel="stylesheet" href="settings.css" />
</head>
<body>
<div class="settings-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar">
        <h2>🌾 CropCast</h2>
        <ul>
            <li><a href="../dashboard.php">📊 Dashboard</a></li>
            <li><a href="../profile/profile.html">👤 Profile</a></li>
            <li><a href="../fields/fields.php">🌱 Fields</a></li>
            <li><a href="../weather/weather.php">☁️ Weather</a></li>
            <li><a href="../soil/soil.php">🧪 Soil Data</a></li>
            <li><a href="../reports/reports.php">📄 Reports</a></li>
            <li><a href="settings.php" class="active">⚙️ Settings</a></li>
            <li><a href="../../logout.php" id="logout-link">🚪 Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <div class="settings-container">
        <header>
            <h1>⚙️ User Settings</h1>
            
        </header>

        <section class="settings-section">
            <form id="settingsForm">
                <div class="form-group">
                    <label for="notif">Email Notifications:</label>
                    <select id="notif">
                        <option value="on">Enabled</option>
                        <option value="off">Disabled</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="theme">Theme:</label>
                    <select id="theme">
                        <option value="light">Light</option>
                        <option value="dark">Dark</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="language">Language:</label>
                    <select id="language">
                        <option value="en">English</option>
                        <option value="bn">বাংলা</option>
                    </select>
                </div>

                <button type="submit" id="saveBtn">💾 Save Settings</button>
            </form>
        </section>
    </div>
</div>

<script src="settings.js"></script>
</body>
</html>
