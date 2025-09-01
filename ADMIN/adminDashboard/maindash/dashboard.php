<?php
// dashboard.php — PHP + CSS only (no JS), with Total Visits KPI
// Assumes main.php sets $conn = new mysqli(...)

session_start();
require_once __DIR__ . '/../../../main.php';

// ---- Fetch KPIs ----
function fetch_count(mysqli $conn, string $sql): int {
    $res = $conn->query($sql);
    if (!$res) return 0;
    $row = $res->fetch_row();
    return (int)($row[0] ?? 0);
}

$totalUsers   = fetch_count($conn, "SELECT COUNT(*) FROM users");
$totalFarmers = fetch_count($conn, "SELECT COUNT(*) FROM farmer");
$totalFarms   = fetch_count($conn, "SELECT COUNT(*) FROM farm");

/* New KPI — Total Visits:
   You asked specifically for 'visite.userID counts'.
   This counts the number of rows with a userID (i.e., total visits recorded). */
$totalVisits  = fetch_count($conn, "SELECT COUNT(userID) FROM visite");

// (Optional) identify current admin for the top-right chip if needed
$adminName = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin';

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="dashboard.css?v=2" />
</head>
<body>
<div class="dashboard-wrapper">
  <!-- If you include an admin sidebar, keep it here -->
  <?php @include __DIR__ . '/../../partials/sidebar.php'; ?>
  <main class="app">
    <!-- Topbar (kept structure/classes to avoid breaking layout) -->
    <div class="topbar">
      <div class="brand">
        <button class="ghost-btn" aria-label="Menu">☰</button>
        <span>CropCast Admin</span>
      </div>
      <div class="search" role="search">
        <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="#64748b" d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16a6.471 6.471 0 004.23-1.57l.27.28v.79L20 21.5 21.5 20 15.5 14zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
        <input type="text" placeholder="Search…" />
      </div>
      <div class="top-actions">
        <button class="icon-btn" title="Notifications">🔔</button>
        <div class="user-chip">
          <img src="https://i.pravatar.cc/100?img=12" alt="" />
          <span><?= h($adminName) ?></span>
        </div>
      </div>
    </div>

    <div class="content">
      <!-- KPI cards (layout/classes unchanged). New Visits card added -->
      <section class="kpi-grid">
        <a href="users.php" class="kpi-card grad-pink" aria-label="Total users">
          <span class="kpi-icon">👥</span>
          <div class="kpi-meta">
            <p>Total Users</p>
            <h3><?= number_format($totalUsers) ?></h3>
          </div>
        </a>

        <a href="farmers.php" class="kpi-card grad-green" aria-label="Total farmers">
          <span class="kpi-icon">👨‍🌾</span>
          <div class="kpi-meta">
            <p>Total Farmers</p>
            <h3><?= number_format($totalFarmers) ?></h3>
          </div>
        </a>

        <a href="farms.php" class="kpi-card grad-orange" aria-label="Total farms">
          <span class="kpi-icon">🌾</span>
          <div class="kpi-meta">
            <p>Total Farms</p>
            <h3><?= number_format($totalFarms) ?></h3>
          </div>
        </a>

        <!-- NEW: Total Visits -->
        <div class="kpi-card grad-lime" aria-label="Total visits">
          <span class="kpi-icon">👀</span>
          <div class="kpi-meta">
            <p>Total Visits</p>
            <h3><?= number_format($totalVisits) ?></h3>
          </div>
        </div>
      </section>

      <!-- Charts section removed — but keep containers if your layout expects them.
           You can keep these two cards as placeholders so nothing else breaks. -->
      <section class="charts-grid">
        <div class="card">
          <div class="card-head">
            <h4>Overview</h4>
            <div class="legend">
              <span><span class="dot dot-green"></span>Products</span>
              <span><span class="dot dot-blue"></span>Services</span>
            </div>
          </div>
          <div style="padding:12px;color:#64748b;">
            Charts are disabled on this dashboard. (JS removed)
          </div>
        </div>

        <div class="card">
          <div class="card-head"><h4>Breakdown</h4></div>
          <div style="padding:12px;color:#64748b;">
            Charts are disabled on this dashboard. (JS removed)
          </div>
        </div>
      </section>

      <!-- Keep any remaining sections/tables your page already uses -->
      <!-- Example: Recent items table placeholder (optional) -->
      <!--
      <section class="grid-2">
        <div class="card">
          <div class="card-head"><h4>Recent Activity</h4></div>
          <div class="table-wrap">
            <table class="data-table">
              <thead><tr><th>ID</th><th>Item</th><th>Date</th></tr></thead>
              <tbody>
                <tr><td>—</td><td>No data</td><td>—</td></tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="card side-card">
          <div class="card-head"><h4>Top Regions</h4></div>
          <ul class="country-list">
            <li><span>—</span><span>—</span></li>
          </ul>
        </div>
      </section>
      -->
    </div>
  </main>
</div>
</body>
</html>
