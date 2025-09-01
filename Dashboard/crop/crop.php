<?php
/**
 * Crop Planner — show farms and recommend crops to sow for the current season.
 * - UI style consistent with fields.php (uses shared sidebar partial)
 * - Left: farm tiles (for the current farmer)
 * - Right: seasonal recommendations (demand-supply deficit + sow window + weather)
 *
 * Tables used (auto-detects common name variants):
 *   crop, crop_cycle, region, farm, demand, supply, weatherdetails
 */
session_start();
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../../main.php'; // must provide $conn (mysqli) or $pdo (PDO)

// -----------------------------
// Auth guard
// -----------------------------
if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo "<!doctype html><html><body><p>Unauthorized</p></body></html>";
  exit;
}

// -----------------------------
// DB helpers (PDO or mysqli)
// -----------------------------
function db_is_pdo()    { return isset($GLOBALS['pdo'])  && $GLOBALS['pdo']  instanceof PDO; }
function db_is_mysqli() { return isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli; }

function db_query_all($sql, $params = []) {
  if (db_is_pdo()) {
    $stmt = $GLOBALS['pdo']->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  } elseif (db_is_mysqli()) {
    $stmt = $GLOBALS['conn']->prepare($sql);
    if ($params) {
      $types = ''; $bind = [];
      foreach ($params as $p) {
        if (is_int($p)) $types .= 'i';
        elseif (is_float($p)) $types .= 'd';
        else $types .= 's';
        $bind[] = $p;
      }
      $stmt->bind_param($types, ...$bind);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
  }
  throw new Exception('No database connection ($pdo or $conn) found.');
}
function db_exec($sql, $params = []) {
  if (db_is_pdo()) {
    $stmt = $GLOBALS['pdo']->prepare($sql);
    return $stmt->execute($params);
  } elseif (db_is_mysqli()) {
    $stmt = $GLOBALS['conn']->prepare($sql);
    if ($params) {
      $types = ''; $bind = [];
      foreach ($params as $p) {
        if (is_int($p)) $types .= 'i';
        elseif (is_float($p)) $types .= 'd';
        else $types .= 's';
        $bind[] = $p;
      }
      $stmt->bind_param($types, ...$bind);
    }
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
  }
  throw new Exception('No database connection ($pdo or $conn) found.');
}

// -----------------------------
// Utility: table name chooser
// -----------------------------
function table_exists_any(array $candidates) {
  $names = array_map(function($n){ return "'".$n."'"; }, $candidates);
  $in    = implode(',', $names);
  $sql   = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ($in) LIMIT 1";
  $hit   = db_query_all($sql);
  return $hit ? $hit[0]['TABLE_NAME'] : null;
}
$T_SUPPLY   = table_exists_any(['supply','Supply','supplycrop','SupplyCrop','supply_crop']);
$T_DEMAND   = table_exists_any(['demand','Demand','regionalDemand','RegionalDemand','regional_demand']);
$T_CYCLE    = table_exists_any(['crop_cycle','cropcycle','Crop_Cycle']);
$T_WEATHER  = table_exists_any(['weatherdetails','WeatherDetails','weather_details']);
$T_CROP     = table_exists_any(['crop','Crop']);
$T_FARM     = table_exists_any(['farm','Farm']);
$T_REGION   = table_exists_any(['region','Region']);

if (!$T_SUPPLY || !$T_DEMAND || !$T_CYCLE || !$T_WEATHER || !$T_CROP || !$T_FARM || !$T_REGION) {
  http_response_code(500);
  echo "<!doctype html><html><body><p>Missing expected tables. Please ensure crop, crop_cycle, supply, demand, weatherdetails, farm, region exist.</p></body></html>";
  exit;
}

// -----------------------------
// Seasonal helpers
// -----------------------------
/** Month to label helpers (supports 2-month and 3-month windows from your crop_cycle data) */
function period_labels_for_month(int $m): array {
  // Two-month windows
  $pairs = [
    'Jan-Feb'=>[1,2], 'Feb-Mar'=>[2,3], 'Mar-Apr'=>[3,4], 'Apr-May'=>[4,5],
    'May-Jun'=>[5,6], 'Jun-Jul'=>[6,7], 'Jul-Aug'=>[7,8], 'Aug-Sep'=>[8,9],
    'Sep-Oct'=>[9,10], 'Oct-Nov'=>[10,11], 'Nov-Dec'=>[11,12], 'Dec-Jan'=>[12,1],
  ];
  // Three-month windows seen in your data
  $triples = ['Nov-Jan'=>[11,12,1]];
  $active = [];
  foreach ($pairs as $label=>$arr)   if (in_array($m,$arr,true)) $active[]=$label;
  foreach ($triples as $label=>$arr) if (in_array($m,$arr,true)) $active[]=$label;
  // Always consider Year-round valid
  $active[] = 'Year-round';
  return array_values(array_unique($active));
}
function months_for_label(string $label): array {
  $map = [
    'Jan-Feb'=>[1,2], 'Feb-Mar'=>[2,3], 'Mar-Apr'=>[3,4], 'Apr-May'=>[4,5],
    'May-Jun'=>[5,6], 'Jun-Jul'=>[6,7], 'Jul-Aug'=>[7,8], 'Aug-Sep'=>[8,9],
    'Sep-Oct'=>[9,10], 'Oct-Nov'=>[10,11], 'Nov-Dec'=>[11,12], 'Dec-Jan'=>[12,1],
    'Nov-Jan'=>[11,12,1], 'Year-round'=>[1,2,3,4,5,6,7,8,9,10,11,12],
  ];
  return $map[$label] ?? [];
}

// -----------------------------
// POST → JSON API
// -----------------------------
$userID = (int)$_SESSION['user_id'];           // in your app this equals farmerID
$farmerID = $userID;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // accept JSON or form-encoded
  $payload = $_POST;
  if (empty($payload)) {
    $raw = file_get_contents('php://input');
    if ($raw) {
      $decoded = json_decode($raw, true);
      if (is_array($decoded)) $payload = $decoded;
    }
  }

  $action = isset($payload['action']) ? trim($payload['action']) : '';

  try {
    // 1) Return all farms for this farmer
    if ($action === 'list_farms') {
      $farms = db_query_all(
        "SELECT f.farmID, f.regionID, r.name AS region_name, f.area_size
         FROM {$T_FARM} f
         JOIN {$T_REGION} r ON r.regionID = f.regionID
         WHERE f.farmerID = ?
         ORDER BY f.farmID DESC",
        [$farmerID]
      );
      echo json_encode(['success'=>true,'farms'=>$farms]);
      exit;
    }

    // 2) Recommend crops for a given farm
    if ($action === 'recommend_for_farm') {
      $farmID = isset($payload['farmID']) ? (int)$payload['farmID'] : 0;
      if ($farmID <= 0) {
        http_response_code(400);
        echo json_encode(['success'=>false,'message'=>'farmID is required']);
        exit;
      }

      // Ensure ownership & fetch farm context
      $farmRows = db_query_all(
        "SELECT f.farmID, f.regionID, r.name AS region_name, f.area_size
         FROM {$T_FARM} f
         JOIN {$T_REGION} r ON r.regionID = f.regionID
         WHERE f.farmID = ? AND f.farmerID = ?
         LIMIT 1",
        [$farmID, $farmerID]
      );
      if (!$farmRows) {
        http_response_code(403);
        echo json_encode(['success'=>false,'message'=>'Farm not found or not yours']);
        exit;
      }
      $farm = $farmRows[0];
      $regionID = (int)$farm['regionID'];

      // Current seasonal windows
      $mNow = (int)date('n');
      $activePeriods = period_labels_for_month($mNow);

      // Demand vs Supply deficit by crop for this region
      $sql = "
        WITH d AS (
          SELECT CropID, SUM(Quantity) AS dem
          FROM {$T_DEMAND}
          WHERE RegionID = ?
          GROUP BY CropID
        ),
        s AS (
          SELECT CropID, SUM(Quantity) AS sup
          FROM {$T_SUPPLY}
          WHERE RegionID = ?
          GROUP BY CropID
        )
        SELECT c.cropID, c.name, c.type,
               cy.sow_period, cy.harvest_period, cy.duration,
               COALESCE(d.dem,0) AS demand,
               COALESCE(s.sup,0) AS supply,
               (COALESCE(d.dem,0) - COALESCE(s.sup,0)) AS deficit
        FROM {$T_CROP} c
        LEFT JOIN {$T_CYCLE} cy ON cy.cropID = c.cropID
        LEFT JOIN d ON d.CropID = c.cropID
        LEFT JOIN s ON s.CropID = c.cropID
        WHERE cy.sow_period IN (" . str_repeat('?,', count($activePeriods)-1) . "?) 
        ORDER BY deficit DESC, c.name ASC
        LIMIT 12";
      $params = [$regionID, $regionID, ...$activePeriods];
      $rows   = db_query_all($sql, $params);

      // Weather snapshot for the months in the first matching sow window
      $months = months_for_label($activePeriods[0]);
      $monthLabels = array_map(fn($n)=>date('M', mktime(0,0,0,$n,1)), $months);
      $weather = [];
      if ($months) {
        $placeholders = implode(',', array_fill(0, count($monthLabels), '?'));
        $w = db_query_all(
          "SELECT MonthLabel, WeatherPrediction
           FROM {$T_WEATHER}
           WHERE RegionID = ? AND MonthLabel IN ($placeholders)
           ORDER BY FIELD(MonthLabel, $placeholders)",
          array_merge([$regionID], $monthLabels, $monthLabels) // for FIELD() order
        );
        foreach ($w as $r) $weather[] = $r['MonthLabel'].': '.$r['WeatherPrediction'];
      }

      // Shape the result for UI
      $plan = array_map(function($r){
        return [
          'id'      => (int)$r['cropID'],
          'name'    => (string)$r['name'],
          'type'    => (string)($r['type'] ?? ''),
          'sow'     => (string)($r['sow_period'] ?? ''),
          'harvest' => (string)($r['harvest_period'] ?? ''),
          'duration'=> (int)($r['duration'] ?? 0),
          'demand'  => (float)$r['demand'],
          'supply'  => (float)$r['supply'],
          'deficit' => (float)$r['deficit'],
        ];
      }, $rows);

      echo json_encode([
        'success'=>true,
        'farm'=>$farm,
        'season_label'=>$activePeriods[0],
        'weather'=>$weather,
        'plan'=>$plan
      ]);
      exit;
    }

    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Unknown action']);
    exit;

  } catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
    exit;
  }
}

// -----------------------------
// GET → Render page
// -----------------------------
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Crop Planner</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="crop.css?v=<?= time() ?>" />
</head>
<body>
  <!-- Sidebar (absolute include) -->
   <?php
    // Include the shared sidebar (absolute path)
    // Adjust $BASE inside the partial if your app base changes.
    include __DIR__ . '../../partials/partials.php';
  ?>


  <div class="container">
    <header class="header">
      <h1>Crop Planner</h1>
      <p class="subtitle">Choose a farm to see what’s best to sow this season.</p>
    </header>

    <section class="panel grid">
      <div class="left">
        <h2>Your Farms</h2>
        <div id="farms" class="farms"></div>
        <div id="farmMsg" class="msg"></div>
      </div>

      <div class="right">
        <h2>Recommendations</h2>
        <div id="rightInfo" class="right-info muted">Select a farm to see recommendations.</div>
        <div id="plan" class="list"></div>
      </div>
    </section>
  </div>

<script>
const farmsGrid = document.getElementById('farms');
const farmMsg   = document.getElementById('farmMsg');
const rightInfo = document.getElementById('rightInfo');
const planGrid  = document.getElementById('plan');
let   selectedFarmId = null;

// Small helpers
function escapeHtml(s){
  return String(s)
    .replaceAll('&','&amp;').replaceAll('<','&lt;')
    .replaceAll('>','&gt;').replaceAll('"','&quot;')
    .replaceAll("'",'&#39;');
}
function niceNumber(n){ return Number(n).toLocaleString(undefined, {maximumFractionDigits:2}); }

function renderPlan(plan){
  if (!plan || !plan.length) {
    planGrid.innerHTML = '<div class="empty">No strong deficits this season for this region.</div>';
    return;
  }
  planGrid.innerHTML = plan.map(r=>`
    <div class="card">
      <div class="card-main">
        <div><strong>${escapeHtml(r.name)}</strong> <span class="type">(${escapeHtml(r.type || 'Crop')})</span></div>
        <div class="muted">Sow: ${escapeHtml(r.sow)} • Harvest: ${escapeHtml(r.harvest)} • ~${r.duration || '—'} days</div>
      </div>
      <div class="card-actions">
        <div class="pill deficit" title="Demand - Supply">Deficit: ${niceNumber(r.deficit)}</div>
        <div class="pill">Demand: ${niceNumber(r.demand)}</div>
        <div class="pill">Supply: ${niceNumber(r.supply)}</div>
      </div>
    </div>
  `).join('');
}

async function loadFarms(){
  farmMsg.textContent = 'Loading farms…';
  farmsGrid.innerHTML = '';
  try{
    const res = await fetch('crop.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ action:'list_farms' })
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Failed to load farms');
    farmMsg.textContent = '';

    if (!data.farms || data.farms.length === 0) {
      farmsGrid.innerHTML = '<div class="empty">No farms found. Add farms in Fields.</div>';
      return;
    }

    farmsGrid.innerHTML = data.farms.map(f => `
      <button class="farm-tile" data-id="${f.farmID}">
        <div class="farm-title">Farm #${f.farmID}</div>
        <div class="farm-sub">${escapeHtml(f.region_name || ('Region '+f.regionID))}</div>
        <div class="farm-meta">${niceNumber(f.area_size)} acres</div>
      </button>
    `).join('');

    const first = farmsGrid.querySelector('.farm-tile');
    if (first) first.click();

    farmsGrid.querySelectorAll('.farm-tile').forEach(el=>{
      el.addEventListener('click', ()=>{
        farmsGrid.querySelectorAll('.farm-tile').forEach(e=>e.classList.remove('selected'));
        el.classList.add('selected');
        selectedFarmId = parseInt(el.dataset.id, 10);
        recommendForFarm(selectedFarmId);
      });
    });

  }catch(e){
    farmMsg.textContent = e.message || 'Error loading farms';
  }
}

async function recommendForFarm(farmID){
  rightInfo.textContent = 'Calculating…';
  planGrid.innerHTML = '';
  try{
    const res = await fetch('crop.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ action:'recommend_for_farm', farmID })
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Failed');

    const weather = (data.weather && data.weather.length)
      ? `<div class="muted">${data.weather.map(escapeHtml).join(' • ')}</div>` : '';

    rightInfo.innerHTML = `
      <div class="farm-ctx">
        <div><strong>Farm #${data.farm.farmID}</strong></div>
        <div class="muted">${escapeHtml(data.farm.region_name)} • ${Number(data.farm.area_size).toFixed(2)} acres</div>
        <div class="muted">Sowing window: <strong>${escapeHtml(data.season_label)}</strong></div>
        ${weather}
      </div>
    `;
    renderPlan(data.plan);

  }catch(e){
    rightInfo.textContent = e.message || 'Error generating recommendations';
  }
}

// Emphasize Crop menu in sidebar
document.addEventListener('DOMContentLoaded', ()=>{
  const link = document.querySelector('.sidebar a[href*="/Dashboard/crop/crop.php"]');
  if (link) link.classList.add('force-active');
});

loadFarms();
</script>
</body>
</html>
