<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: ../login/login.html'); // adjust if needed
  exit;
}
include '../main.php';
$userID = (int)$_SESSION['user_id'];

/* Basic metric example (you can add more later) */
function metric(mysqli $conn, string $sql, string $alias='c'): int {
  $res = $conn->query($sql);
  return ($res && $row = $res->fetch_assoc()) ? (int)$row[$alias] : 0;
}
$totalUsers = metric($conn, "SELECT COUNT(*) AS c FROM USERS WHERE role='user'");

/* Fetch greeting + avatar path */
$stmt = $conn->prepare("
  SELECT U.name, F.profile_picture
  FROM USERS U
  LEFT JOIN FARMER F ON F.userID = U.userID
  WHERE U.userID = ?
");
$stmt->bind_param('i', $userID);
$stmt->execute();
$stmt->bind_result($name, $picPath);
$stmt->fetch();
$stmt->close();
$profilePicUrl = $picPath ? "profile/".$picPath : "images/default-avatar.png"; // adjust default path
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard | CropCast</title>
  <link rel="stylesheet" href="dashboard.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
</head>
<body>
<div class="dashboard-wrapper">
  <!-- Sidebar (kept) -->
  <aside class="sidebar">
    <h2>🌾 CropCast</h2>
    <ul>
      <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
      <li><a href="profile/profile.php">👤 Profile</a></li>
      <li><a href="fields/fields.php">🌱 Fields</a></li>
      <li><a href="weather/weather.php">☁️ Weather</a></li>
      <li><a href="soil/soil.php">🧪 Soil Data</a></li>
      <li><a href="reports/reports.php">📄 Reports</a></li>
      <li><a href="settings/settings.php">⚙️ Settings</a></li>
      <li><a href="../logout.php">🚪 Logout</a></li>
    </ul>
  </aside>

  <main class="app">
    <!-- Topbar -->
    <header class="topbar">
      <div class="brand">
        <button class="ghost-btn"><i class="fa-solid fa-bars"></i></button>
        <span>Dashboard</span>
      </div>
      <div class="search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="search" placeholder="Search data & reports…"/>
      </div>
      <div class="top-actions">
        <button class="icon-btn"><i class="fa-regular fa-bell"></i></button>
        <button class="icon-btn"><i class="fa-regular fa-envelope"></i></button>
        <div class="user-chip">
          <img src="<?= htmlspecialchars($profilePicUrl) ?>" alt="avatar"/>
          <span>Welcome, <?= htmlspecialchars($name ?: 'User') ?></span>
        </div>
      </div>
    </header>

    <div class="content">
      <!-- KPI tiles -->
      <section class="kpi-grid">
        <!-- Total Users tile (clickable) -->
        <a class="kpi-card grad-pink" href="../admin/adminDashboard/users.php">
          <div class="kpi-icon"><i class="fa-solid fa-user-group"></i></div>
          <div class="kpi-meta">
            <p>Total Users</p>
            <h3><?= $totalUsers ?></h3>
          </div>
        </a>

        <!-- Add more KPI cards as needed -->
        <div class="kpi-card grad-green">
          <div class="kpi-icon"><i class="fa-solid fa-seedling"></i></div>
          <div class="kpi-meta">
            <p>Active Farms</p>
            <h3>128</h3>
          </div>
        </div>
        <div class="kpi-card grad-orange">
          <div class="kpi-icon"><i class="fa-regular fa-calendar-days"></i></div>
          <div class="kpi-meta">
            <p>This Week</p>
            <h3>36</h3>
          </div>
        </div>
        <div class="kpi-card grad-lime">
          <div class="kpi-icon"><i class="fa-solid fa-chart-line"></i></div>
          <div class="kpi-meta">
            <p>Yield Index</p>
            <h3>74%</h3>
          </div>
        </div>
      </section>
      <!-- (KPI SECTION) To add more tiles later, copy a .kpi-card block above. -->

      <!-- Charts -->
      <section class="charts-grid">
        <div class="card">
          <div class="card-head">
            <h4>Recent Reports</h4>
            <div class="legend">
              <span class="dot dot-green"></span> Products
              <span class="dot dot-blue"></span> Services
            </div>
          </div>
          <canvas id="areaChart" height="120"></canvas>
        </div>

        <div class="card">
          <div class="card-head"><h4>Chart By %</h4></div>
          <canvas id="pieChart" height="120"></canvas>
        </div>
      </section>

      <!-- Table + Side list -->
      <section class="grid-2">
        <div class="card">
          <div class="card-head"><h4>Earnings By Items</h4></div>
          <div class="table-wrap">
            <table class="data-table">
              <thead>
              <tr>
                <th>Date</th><th>Order ID</th><th>Name</th><th>Price</th><th>Qty</th><th>Total</th>
              </tr>
              </thead>
              <tbody>
              <tr><td>2025-08-01</td><td>100397</td><td>Soil Sensor</td><td>$59.00</td><td>2</td><td>$118.00</td></tr>
              <tr><td>2025-08-02</td><td>100398</td><td>Weather Node</td><td>$129.00</td><td>1</td><td>$129.00</td></tr>
              <tr><td>2025-08-03</td><td>100399</td><td>Drip Kit</td><td>$89.00</td><td>3</td><td>$267.00</td></tr>
              <tr><td>2025-08-03</td><td>100400</td><td>Seed Pack</td><td>$19.00</td><td>10</td><td>$190.00</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card side-card">
          <div class="card-head"><h4>Top Countries</h4></div>
          <ul class="country-list">
            <li><span>United States</span><strong>$110,386.98</strong></li>
            <li><span>Australia</span><strong>$70,281.65</strong></li>
            <li><span>United Kingdom</span><strong>$48,392.12</strong></li>
            <li><span>Turkey</span><strong>$35,384.60</strong></li>
            <li><span>Germany</span><strong>$20,368.59</strong></li>
          </ul>
        </div>
      </section>
    </div>
  </main>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="dashboard.js"></script>
</body>
</html>
