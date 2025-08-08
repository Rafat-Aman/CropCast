<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: ../login/login.html"); // adjust if needed
  exit;
}

include '../main.php'; // dashboard.php is in /Dashboard, main.php in project root

/**
 * Helper to fetch a COUNT(*) style metric.
 * Usage: $count = metric($conn, "SELECT COUNT(*) AS c FROM ...", 'c');
 */
function metric(mysqli $conn, string $sql, string $alias = 'c'): int {
  $res = $conn->query($sql);
  if ($res && ($row = $res->fetch_assoc())) return (int)$row[$alias];
  return 0;
}

/* =========================================
   METRIC QUERIES — add more here later
   ========================================= */

// Total Users (where role='user'). Change WHERE or add more queries below.
$totalUsers = metric($conn, "SELECT COUNT(*) AS c FROM USERS WHERE role='user'", 'c');

/*
$allUsers         = metric($conn, "SELECT COUNT(*) AS c FROM USERS", 'c');
$admins           = metric($conn, "SELECT COUNT(*) AS c FROM USERS WHERE role='admin'", 'c');
$farmersWithProf  = metric($conn, "SELECT COUNT(*) AS c FROM USERS U JOIN FARMER F ON F.userID=U.userID WHERE U.role='user'", 'c');
$farmersNoProfile = metric($conn, "SELECT COUNT(*) AS c FROM USERS U LEFT JOIN FARMER F ON F.userID=U.userID WHERE U.role='user' AND F.userID IS NULL", 'c');
// Add more metrics above as you need
*/
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
            <!-- You can keep your welcome/avatar here if you like -->
        </header>

        <!-- =========================================
             DASH TILES — copy a tile <a> to add more
             ========================================= -->
        <section class="admin-cards">
            <!-- Total Users tile -->
            <a class="admin-card admin-card--clickable admin-card--blue"
               href="../users/users.php" aria-label="Go to Users">
                <div class="admin-card__title">Total Users</div>
                <div class="admin-card__metric"><?= $totalUsers ?></div>
                <div class="admin-card__hint">View users →</div>
            </a>

            <!-- Add more tiles below (example templates) -->
            <!--
            <a class="admin-card admin-card--clickable admin-card--green" href="../users/users.php">
              <div class="admin-card__title">All Users</div>
              <div class="admin-card__metric"><?= $allUsers ?? 0 ?></div>
              <div class="admin-card__hint">Manage →</div>
            </a>

            <a class="admin-card admin-card--clickable admin-card--orange" href="../users/users.php?role=admin">
              <div class="admin-card__title">Admins</div>
              <div class="admin-card__metric"><?= $admins ?? 0 ?></div>
              <div class="admin-card__hint">Manage →</div>
            </a>
            -->
        </section>

        <div id="main-content">
            <!-- Keep your existing user-facing cards below if you still want them -->
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
