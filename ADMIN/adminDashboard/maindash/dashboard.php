<?php
// dashboard.php — PHP + CSS only (no JS), includes Total Visits KPI + admin greeting/photo

session_start();
require_once __DIR__ . '../../../../main.php'; // must set $conn = new mysqli(...)

/* ---------- Helpers ---------- */
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function fetch_count(mysqli $conn, string $sql): int {
  $res = $conn->query($sql);
  if (!$res) return 0;
  $row = $res->fetch_row();
  return (int)($row[0] ?? 0);
}

/* ---------- KPIs ---------- */
$totalUsers   = fetch_count($conn, "SELECT COUNT(*) FROM users");
$totalFarmers = fetch_count($conn, "SELECT COUNT(*) FROM farmer");
$totalFarms   = fetch_count($conn, "SELECT COUNT(*) FROM farm");
$totalVisits  = fetch_count($conn, "SELECT COUNT(userID) FROM visite");

/* ---------- Admin greeting + photo ---------- */
/* We try session first; if not present, fall back to users.name by userID */
$adminID    = isset($_SESSION['userID']) ? (int)$_SESSION['userID'] : 0;
// $adminName  = $_SESSION['admin_name'] ?? $_SESSION['name'] ?? 'Admin';
// Fetch greeting + avatar path


$adminPhoto = null;
if ($adminID) {
  $stmt = $conn->prepare("
    SELECT U.name, F.profile_picture
    FROM users U
    JOIN farmer F ON F.userID = U.userID
    WHERE U.userID = ?
  ");
  $stmt->bind_param('i', $adminID);
  $stmt->execute();
  $stmt->bind_result($name, $picPath);
  if ($stmt->fetch()) {
    if (!$adminName && $name) $adminName = $name;
    $adminPhoto = $picPath
      ? '/ProjectFolder/Dashboard/profile/' . $picPath
      : '/ProjectFolder/Dashboard/images/default-avatar.png';
  }
  $stmt->close();
}



if ($adminID && !$adminPhoto) {
  // Try optional photo column; if it doesn't exist, we just ignore.
  if ($stmt = $conn->prepare("SELECT name FROM users WHERE userID = ? LIMIT 1")) {
    $stmt->bind_param('i', $adminID);
    $stmt->execute();
    $stmt->bind_result($nm);
    if ($stmt->fetch() && !$adminName) $adminName = $nm ?: $adminName;
    $stmt->close();
  }
}
// Fallback avatar if none in session
if (!$adminPhoto) {
  $adminPhoto = "https://i.pravatar.cc/100?img=3";
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- Polished CSS (safe drop-in) -->
  <link rel="stylesheet" href="dashboard.css" />
</head>
<body>
<div class="dashboard-wrapper">
  <?php @include __DIR__ . '/../../partials/sidebar.php'; ?>
  <main class="app">
    <!-- Topbar -->
    <div class="topbar">
      <div class="brand">
        <button class="ghost-btn" aria-label="Menu">☰</button>
        <span>CropCast Admin</span>
      </div>

      <!-- Search: INPUT ONLY (no button) -->
      <div class="search" role="search" aria-label="Quick search">
        <input type="text" placeholder="Search…" />
      </div>

      <!-- Admin greeting + profile picture -->
      <div class="top-actions">
       
         <div class="user-chip">
          <img src="<?= h($adminPhoto) ?>" alt="Profile picture" />
          
        </div>
      </div>
    </div>

    <div class="content">
      <!-- KPI cards -->
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

        <!-- Total Visits from visite.userID count -->
        <div class="kpi-card grad-lime" aria-label="Total visits">
          <span class="kpi-icon">👀</span>
          <div class="kpi-meta">
            <p>Total Visits</p>
            <h3><?= number_format($totalVisits) ?></h3>
          </div>
        </div>
      </section>

      <!-- Optional placeholders if your layout expects the charts section -->
      <section class="charts-grid">
        <div class="card">
          <div class="card-head">
            <h4>Overview</h4>
            <div class="legend">
              <span><span class="dot dot-green"></span>Products</span>
              <span><span class="dot dot-blue"></span>Services</span>
            </div>
          </div>
          <div class="card-empty">Charts disabled (no JS).</div>
        </div>

        <div class="card">
          <div class="card-head"><h4>Breakdown</h4></div>
          <div class="card-empty">Charts disabled (no JS).</div>
        </div>
      </section>
    </div>
  </main>
</div>
</body>
</html>
