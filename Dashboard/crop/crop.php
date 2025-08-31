<?php
session_start();
header('Content-Type: application/json');
include '../../main.php';

// Session guard
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

/* Serve HTML on GET, JSON on POST */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: text/html; charset=utf-8');
}

/* ---------- DB helpers (PDO or MySQLi from main.php) ---------- */
function db_is_pdo() { return isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO; }
function db_is_mysqli() { return isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli; }

function db_query_all($sql, $params = []) {
    if (db_is_pdo()) {
        $st = $GLOBALS['pdo']->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    } elseif (db_is_mysqli()) {
        $st = $GLOBALS['conn']->prepare($sql);
        if ($params) {
            $types=''; $bind=[];
            foreach ($params as $p) { $types.= is_int($p)?'i' : (is_float($p)?'d':'s'); $bind[]=$p; }
            $st->bind_param($types, ...$bind);
        }
        $st->execute();
        $res = $st->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $st->close();
        return $rows;
    }
    throw new Exception('No database connection found.');
}

/* ---------- Season helpers (Bangladesh windows) ---------- */
function months_for_season($season) {
    if ($season === 'Rabi')      return [11,12,1,2];   // Nov–Feb
    if ($season === 'Kharif-1')  return [3,4,5,6];     // Mar–Jun
    if ($season === 'Kharif-2')  return [7,8,9,10];    // Jul–Oct
    return [];
}
function season_of_month($m) {
    if (in_array($m,[11,12,1,2],true)) return 'Rabi';
    if (in_array($m,[3,4,5,6],true))   return 'Kharif-1';
    return 'Kharif-2';
}
function next_three_seasons_from_month($m) {
    $cur = season_of_month($m);
    $order = ['Rabi','Kharif-1','Kharif-2'];
    $i = array_search($cur, $order, true);
    return [$order[$i], $order[($i+1)%3], $order[($i+2)%3]];
}
function month_abbr_to_num($abbr) {
    static $map = ['Jan'=>1,'Feb'=>2,'Mar'=>3,'Apr'=>4,'May'=>5,'Jun'=>6,'Jul'=>7,'Aug'=>8,'Sep'=>9,'Oct'=>10,'Nov'=>11,'Dec'=>12];
    $k = ucfirst(strtolower(substr(trim($abbr),0,3)));
    return $map[$k] ?? null;
}
function parse_sow_months($text) {
    $text = trim((string)$text);
    if ($text === '') return [];
    if (stripos($text,'year') !== false) return [1,2,3,4,5,6,7,8,9,10,11,12];
    preg_match_all('/Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec/i', $text, $m);
    $months = array_values(array_map('month_abbr_to_num', $m[0]));
    if (count($months) === 1) return $months;
    if (count($months) >= 2) {
        $a = $months[0]; $b = end($months);
        $out = []; $x = $a;
        while (true) {
            $out[] = $x;
            if ($x === $b) break;
            $x = $x === 12 ? 1 : $x + 1;
            if (count($out) > 12) break;
        }
        return array_values(array_unique($out));
    }
    return [];
}

/* ---------- POST API ---------- */
$farmerID = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = $_POST;
    if (empty($payload)) {
        $raw = file_get_contents('php://input');
        if ($raw) { $d = json_decode($raw, true); if (is_array($d)) $payload = $d; }
    }
    $action = isset($payload['action']) ? trim($payload['action']) : '';

    try {
        if ($action === 'list_farms') {
            $farms = db_query_all(
                "SELECT f.farmID, f.regionID, r.name AS region_name, f.area_size
                 FROM farm f
                 JOIN region r ON r.regionID = f.regionID
                 WHERE f.farmerID = ?
                 ORDER BY f.farmID DESC",
                [$farmerID]
            );
            echo json_encode(['success'=>true,'farms'=>$farms]);
            exit;
        }

        if ($action === 'recommend_for_farm') {
            $farmID = isset($payload['farmID']) ? (int)$payload['farmID'] : 0;
            $startMonth = isset($payload['startMonth']) ? (int)$payload['startMonth'] : (int)date('n');
            if ($farmID <= 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'farmID required']); exit; }

            // Verify ownership + grab region
            $farm = db_query_all(
                "SELECT f.farmID, f.regionID, r.name AS region_name, f.area_size
                 FROM farm f
                 JOIN region r ON r.regionID = f.regionID
                 WHERE f.farmID = ? AND f.farmerID = ? LIMIT 1",
                [$farmID, $farmerID]
            );
            if (!$farm) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Not allowed or farm not found']); exit; }
            $farm = $farm[0];

            // Candidate crop cycles where region soils intersect crop soils
            $rows = db_query_all(
                "SELECT c.cropID, c.name AS crop_name, c.type,
                        cy.duration, cy.sow_period, cy.harvest_period
                 FROM crop c
                 JOIN crop_soil_type cs   ON cs.cropID = c.cropID
                 JOIN region_soil_type rs ON rs.soilID = cs.soilID AND rs.regionID = ?
                 JOIN crop_cycle cy       ON cy.cropID = c.cropID
                 ORDER BY c.name, cy.sow_period",
                [(int)$farm['regionID']]
            );

            $seasonOrder = next_three_seasons_from_month($startMonth);
            $plan = [];
            $seen = ['Rabi'=>[], 'Kharif-1'=>[], 'Kharif-2'=>[]];
            foreach ($seasonOrder as $s) { $plan[$s] = ['season'=>$s, 'months'=>months_for_season($s), 'crops'=>[]]; }

            foreach ($rows as $r) {
                $sowMonths = parse_sow_months($r['sow_period']);
                foreach ($seasonOrder as $season) {
                    if (array_intersect($sowMonths, months_for_season($season))) {
                        $cid = (int)$r['cropID'];
                        if (!isset($seen[$season][$cid])) {
                            $seen[$season][$cid] = true;
                            $plan[$season]['crops'][] = [
                                'cropID'=>$cid,
                                'name'=>$r['crop_name'],
                                'type'=>$r['type'],
                                'sow'=>$r['sow_period'],
                                'harvest'=>$r['harvest_period'],
                                'duration'=>(int)$r['duration']
                            ];
                        }
                    }
                }
            }
            foreach ($plan as &$p) { usort($p['crops'], fn($a,$b)=>strcmp($a['name'],$b['name'])); }

            echo json_encode([
                'success'=>true,
                'farm'=>$farm,
                'seasonOrder'=>$seasonOrder,
                'plan'=>array_values($plan)
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
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <title>Crop Planner</title>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <link rel="stylesheet" href="crop.css"/>
</head>
<body>
  <!-- Left Sidebar -->
  <aside class="sidebar">
    <nav>
      <ul>
        <li><a href="../dashboard.php" id="menu-dashboard" class="active">📊 Dashboard</a></li>
        <li><a href="../profile/profile.php" id="menu-profile">👤 Profile</a></li>
        <li><a href="../fields/fields.php" id="menu-fields">🌱 Fields</a></li>
        <li><a href="../crop/crop.php" id="menu-weather">🌾 Crop</a></li>
        <li><a href="../soil/soil.php" id="menu-soil">🧪 Soil Data</a></li>
        <li><a href="../reports/reports.php" id="menu-reports">📄 Reports</a></li>
        <li><a href="../settings/settings.php" id="menu-settings">⚙️ Settings</a></li>
        <li><a href="../feedback/feedback.php" id="feedback-link">💬 feedback</a></li>
        <li><a href="../../logout.php" id="logout-link">🚪 Logout</a></li>
      </ul>
    </nav>
  </aside>

  <!-- Main page content -->
  <main class="page">
    <div class="container">
      <header class="header">
        <h1>Crop Planner</h1>
        <p class="subtitle">Pick one of your farms to see recommended crops for the next three seasons.</p>
      </header>

      <div class="layout">
        <!-- Center: Farm tiles -->
        <section class="panel">
          <h2>Your Farms</h2>
          <div id="farmsGrid" class="farm-grid"></div>
          <div id="farmMsg" class="msg"></div>
        </section>

        <!-- Right: Recommendations -->
        <aside class="rightbar">
          <div class="panel sticky">
            <h2>Recommendations</h2>
            <div class="right-info" id="rightInfo">Select a farm to view recommendations.</div>
            <div id="planGrid" class="plan-grid"></div>
          </div>
        </aside>
      </div>
    </div>
  </main>

<script>
const farmsGrid = document.getElementById('farmsGrid');
const farmMsg   = document.getElementById('farmMsg');
const rightInfo = document.getElementById('rightInfo');
const planGrid  = document.getElementById('planGrid');

let selectedFarmId = null;

function escapeHtml(s){ return String(s)
  .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
  .replaceAll('"','&quot;').replaceAll("'","&#39;"); }

function monthBadges(nums){
  const L = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  return nums.map(n=>`<span class="badge">${L[n]}</span>`).join('');
}

function renderPlan(plan){
  if (!plan || !plan.length) {
    planGrid.innerHTML = '<div class="empty">No matching crops for this farm\'s region.</div>';
    return;
  }
  planGrid.innerHTML = plan.map(p => `
    <div class="season-col">
      <div class="season-head">
        <div class="season-title">${p.season}</div>
        <div class="months">${monthBadges(p.months)}</div>
      </div>
      <div class="season-body">
        ${p.crops.length ? p.crops.map(c => `
          <div class="card">
            <div class="card-main">
              <div><strong>${escapeHtml(c.name)}</strong> <span class="type">(${escapeHtml(c.type || 'Crop')})</span></div>
              <div class="muted">Sow: ${escapeHtml(c.sow)} • Harvest: ${escapeHtml(c.harvest)} • ~${c.duration} days</div>
            </div>
          </div>
        `).join('') : '<div class="empty">No crops recommended for this season.</div>'}
      </div>
    </div>
  `).join('');
}

async function loadFarms() {
  farmMsg.textContent = 'Loading farms...';
  farmsGrid.innerHTML = '';
  try {
    const res = await fetch('crop.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ action: 'list_farms' })
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Failed to load farms');

    farmMsg.textContent = '';
    if (!data.farms || data.farms.length === 0) {
      farmsGrid.innerHTML = '<div class="empty">No farms found. Please add farms first in Fields.</div>';
      return;
    }

    farmsGrid.innerHTML = data.farms.map(f => `
      <button class="farm-tile" data-id="${f.farmID}">
        <div class="farm-title">Farm #${f.farmID}</div>
        <div class="farm-sub">${escapeHtml(f.region_name || ('Region ' + f.regionID))}</div>
        <div class="farm-meta">${Number(f.area_size).toFixed(2)} acres</div>
      </button>
    `).join('');

    // Auto-select first farm
    const first = farmsGrid.querySelector('.farm-tile');
    if (first) first.click();

    // Bind clicks
    farmsGrid.querySelectorAll('.farm-tile').forEach(el=>{
      el.addEventListener('click', () => {
        farmsGrid.querySelectorAll('.farm-tile').forEach(e=>e.classList.remove('selected'));
        el.classList.add('selected');
        selectedFarmId = parseInt(el.dataset.id,10);
        recommendForFarm(selectedFarmId);
      });
    });

  } catch (e) {
    farmMsg.textContent = e.message || 'Error loading farms';
  }
}

async function recommendForFarm(farmID) {
  rightInfo.textContent = 'Calculating...';
  planGrid.innerHTML = '';
  try {
    const res = await fetch('crop.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ action: 'recommend_for_farm', farmID })
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Failed');

    rightInfo.innerHTML = `
      <div class="farm-ctx">
        <div><strong>Farm #${data.farm.farmID}</strong></div>
        <div class="muted">${escapeHtml(data.farm.region_name)} • ${Number(data.farm.area_size).toFixed(2)} acres</div>
      </div>`;
    renderPlan(data.plan);

  } catch (e) {
    rightInfo.textContent = e.message || 'Error generating recommendations';
  }
}

/* Highlight Crop menu item regardless of .active on others */
document.addEventListener('DOMContentLoaded', ()=>{
  const cropLink = document.querySelector('.sidebar a[href*="crop/crop.php"]');
  if (cropLink) cropLink.classList.add('force-active');
});

loadFarms();
</script>
</body>
</html>
