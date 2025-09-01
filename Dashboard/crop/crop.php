<?php
// crop.php — Layout v2 to match sketch: left farm grid, right details + suggestions in separate panels
session_start();
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../../main.php'; // provides $conn (mysqli)

/* =========================
   Session / connection guard
   ========================= */
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "<!doctype html><html><body><p>Unauthorized</p></body></html>";
    exit;
}
$userID = (int)$_SESSION['user_id'];

// Get farmerID
$farmerID = null;
if ($stmt = $conn->prepare('SELECT farmerID FROM farmer WHERE userID = ? LIMIT 1')) {
  $stmt->bind_param('i', $userID); $stmt->execute(); $stmt->bind_result($farmerID); $stmt->fetch(); $stmt->close();
}
if (!$farmerID) { http_response_code(404); echo 'No farmer profile linked to this account.'; exit; }

// Farms list
$farms = [];
if ($stmt = $conn->prepare('SELECT f.farmID, f.regionID, f.area_size, r.name AS region_name, r.location FROM farm f JOIN region r ON r.regionID = f.regionID WHERE f.farmerID = ? ORDER BY f.farmID')) {
  $stmt->bind_param('i', $farmerID); $stmt->execute(); $res=$stmt->get_result(); while($row=$res->fetch_assoc()){ $farms[]=$row; } $stmt->close();
}

// Selected farm
$selectedFarmID = isset($_GET['farm_id']) ? (int)$_GET['farm_id'] : (!empty($farms) ? (int)$farms[0]['farmID'] : null);
$selectedFarm = null; $regionSoils = []; $latestWeather = null; $recommendations = [];

if ($selectedFarmID) {
  if ($stmt = $conn->prepare('SELECT f.farmID, f.regionID, f.area_size, r.name AS region_name, r.location FROM farm f JOIN region r ON r.regionID = f.regionID WHERE f.farmID = ? AND f.farmerID = ? LIMIT 1')) {
    $stmt->bind_param('ii', $selectedFarmID, $farmerID); $stmt->execute(); $res=$stmt->get_result(); $selectedFarm=$res->fetch_assoc(); $stmt->close();
  }
  if ($selectedFarm) {
    $regionID = (int)$selectedFarm['regionID'];
    if ($stmt = $conn->prepare('SELECT st.soilID, st.name, st.ph, st.fertility FROM region_soil_type rst JOIN soiltype st ON st.soilID = rst.soilID WHERE rst.regionID = ? ORDER BY st.name')) {
      $stmt->bind_param('i', $regionID); $stmt->execute(); $res=$stmt->get_result(); while($row=$res->fetch_assoc()){ $regionSoils[]=$row; } $stmt->close();
    }
    if ($stmt = $conn->prepare('SELECT Period, WeatherPrediction FROM weatherdetails WHERE RegionID = ? ORDER BY Period DESC LIMIT 1')) {
      $stmt->bind_param('i', $regionID); $stmt->execute(); $stmt->bind_result($p,$w); if($stmt->fetch()){ $latestWeather=['Period'=>$p,'WeatherPrediction'=>$w]; } $stmt->close();
    }
    // Recommendations
    if ($stmt = $conn->prepare(
      'SELECT c.cropID, c.name, c.type, c.ideal_season,
              cc.duration, cc.sow_periods, cc.harvest_periods,
              COALESCE(d.sum_demand,0) AS demand_qty,
              COALESCE(s.sum_supply,0) AS supply_qty,
              (COALESCE(d.sum_demand,0)-COALESCE(s.sum_supply,0)) AS gap
       FROM crop c
       LEFT JOIN (
         SELECT cropID,
                MIN(duration) AS duration,
                GROUP_CONCAT(DISTINCT sow_period ORDER BY sow_period SEPARATOR ", ") AS sow_periods,
                GROUP_CONCAT(DISTINCT harvest_period ORDER BY harvest_period SEPARATOR ", ") AS harvest_periods
         FROM crop_cycle GROUP BY cropID
       ) cc ON cc.cropID=c.cropID
       LEFT JOIN (
         SELECT CropID, SUM(Quantity) AS sum_demand FROM demand WHERE RegionID=? GROUP BY CropID
       ) d ON d.CropID=c.cropID
       LEFT JOIN (
         SELECT CropID, SUM(Quantity) AS sum_supply FROM supply WHERE RegionID=? GROUP BY CropID
       ) s ON s.CropID=c.cropID
       WHERE EXISTS (
         SELECT 1 FROM crop_soil_type cst JOIN region_soil_type rst ON rst.soilID=cst.soilID
         WHERE cst.cropID=c.cropID AND rst.regionID=?
       )
       ORDER BY gap DESC, c.name ASC')
    ) {
      $stmt->bind_param('iii', $regionID,$regionID,$regionID); $stmt->execute(); $res=$stmt->get_result(); while($row=$res->fetch_assoc()){ $recommendations[]=$row; } $stmt->close();
    }
  }
}

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Crop Planner</title>
  <link rel="stylesheet" href="crop.css">
</head>
<body class="crop-page">
  <div class="container">
    <!-- =========================
       Sidebar (static nav)
       ========================= -->
  <?php
    // Include the shared sidebar (absolute path)
    // Adjust $BASE inside the partial if your app base changes.
    include __DIR__ . '../../partials/partials.php';
  ?>
    <div class="header">
      <h1>Crop Planner</h1>
      <p class="subtitle">Select a farm to see details and recommendations.</p>
    </div>

    <div class="layout-2col">
      <!-- Left: Your Farms (grid) -->
      <aside class="panel farms-panel">
        <div class="panel-header"><span>Your Farms</span></div>
        <?php if (empty($farms)): ?>
          <p class="empty">No farms found under your account.</p>
        <?php else: ?>
          <div class="farms grid-tiles">
            <?php foreach ($farms as $farm): $isSel = ($selectedFarm && (int)$farm['farmID']===(int)$selectedFarm['farmID']); ?>
              <a class="farm-tile<?= $isSel?' selected':'' ?>" href="?farm_id=<?= (int)$farm['farmID'] ?>">
                <div class="farm-title">Farm #<?= (int)$farm['farmID'] ?></div>
                <div class="farm-sub"><?= h($farm['region_name']) ?></div>
                <div class="farm-meta"><?= h($farm['location']) ?></div>
                <div class="farm-meta small"><?= h($farm['area_size']) ?> acres</div>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </aside>

      <!-- Right: Selected details then suggestions -->
      <section class="right-col">
        <div class="panel details-panel">
          <div class="panel-header"><span>Selected farm details</span></div>
          <?php if(!$selectedFarm): ?>
            <p class="empty">Pick a farm from the left.</p>
          <?php else: ?>
            <div class="details">
              <h2>Farm #<?= (int)$selectedFarm['farmID'] ?> — <?= h($selectedFarm['region_name']) ?></h2>
              <div class="muted">Location: <?= h($selectedFarm['location']) ?> · Area: <?= h($selectedFarm['area_size']) ?> acres</div>
              <?php if (!empty($regionSoils)): ?>
                <div class="muted">Soils: <?php
                  $soilLabels = array_map(function($s){ $ph = $s['ph']!==null?(' (pH '.h($s['ph']).')'):''; return h($s['name']).$ph; }, $regionSoils);
                  echo implode(', ', $soilLabels);
                ?></div>
              <?php endif; ?>
              <?php if ($latestWeather): ?>
                <div class="muted">Weather (<?= h($latestWeather['Period']) ?>): <?= h($latestWeather['WeatherPrediction']) ?></div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="panel reco-panel">
          <div class="panel-header"><span>Suggested crops</span></div>
          <?php if (empty($recommendations)): ?>
            <p class="empty">No crop recommendations found for this region/soils.</p>
          <?php else: ?>
            <div class="list">
              <?php foreach ($recommendations as $rec): $gap=(float)$rec['gap']; $deficit=$gap>0; ?>
                <div class="card">
                  <div class="card-main">
                    <div><strong><?= h($rec['name']) ?></strong><?= $rec['type']? ' · '.h($rec['type']):'' ?></div>
                    <div class="muted">
                      <?php if ($rec['sow_periods']): ?>Sow: <?= h($rec['sow_periods']) ?> · <?php endif; ?>
                      <?php if ($rec['harvest_periods']): ?>Harvest: <?= h($rec['harvest_periods']) ?> · <?php endif; ?>
                      <?php if ($rec['duration']!==null): ?>Cycle: <?= (int)$rec['duration'] ?> days<?php endif; ?>
                      <?php if ($rec['ideal_season']): ?> · Ideal season: <?= h($rec['ideal_season']) ?><?php endif; ?>
                    </div>
                  </div>
                  <div class="card-actions">
                    <span class="pill<?= $deficit?' deficit':' success' ?>">
                      <?= $deficit ? 'Demand exceeds supply' : ($gap<0 ? 'Supply exceeds demand' : 'Balanced') ?>
                    </span>
                    <span class="pill">Demand: <?= number_format((float)$rec['demand_qty'],2) ?></span>
                    <span class="pill">Supply: <?= number_format((float)$rec['supply_qty'],2) ?></span>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </section>
    </div>
  </div>
</body>
</html>
