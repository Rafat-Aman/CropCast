<?php
// fields.php — minimal, working CRUD for farms (scoped to the logged-in farmer)

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


/* ----------------------- helpers ----------------------- */
function json_exit($payload) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

function db_assert($ok, $conn) {
    if (!$ok) {
        throw new Exception('Database error: ' . $conn->error);
    }
}

/** Resolve farmerID from userID (1:1) */
function get_farmer_id(mysqli $conn, int $userID): ?int {
    $farmerID = null;
    if ($stmt = $conn->prepare('SELECT farmerID FROM farmer WHERE userID = ? LIMIT 1')) {
        $stmt->bind_param('i', $userID);
        $stmt->execute();
        $stmt->bind_result($farmerID);
        $stmt->fetch();
        $stmt->close();
    }
    return $farmerID ?: null;
}

$farmerID = get_farmer_id($conn, $userID);
if (!$farmerID) {
    if (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        http_response_code(404);
        json_exit(['success' => false, 'message' => 'No farmer profile linked to this account.']);
    }
    http_response_code(404);
    echo 'No farmer profile linked to this account.';
    exit;
}

/* ----------------------- API (same-file endpoint) ----------------------- */
$isJson = ($_SERVER['REQUEST_METHOD'] === 'POST') &&
          (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

if ($isJson) {
    try {
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?? [];
        $action = $body['action'] ?? '';

        switch ($action) {
            case 'list': {
                $farms = [];
                if ($stmt = $conn->prepare('
                    SELECT f.farmID, f.regionID, f.area_size,
                           r.name AS region_name, r.location
                    FROM farm f
                    JOIN region r ON r.regionID = f.regionID
                    WHERE f.farmerID = ?
                    ORDER BY f.farmID DESC
                ')) {
                    $stmt->bind_param('i', $farmerID);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while ($row = $res->fetch_assoc()) { $farms[] = $row; }
                    $stmt->close();
                }
                json_exit(['success' => true, 'farms' => $farms]);
            }

            case 'create': {
                $regionID  = isset($body['regionID']) ? (int)$body['regionID'] : 0;
                $area_size = isset($body['area_size']) ? (float)$body['area_size'] : 0.0;
                if ($regionID <= 0 || $area_size <= 0) {
                    json_exit(['success' => false, 'message' => 'Invalid region or area size.']);
                }

                // (optional) ensure region exists
                $regionExists = false;
                if ($stmt = $conn->prepare('SELECT 1 FROM region WHERE regionID = ?')) {
                    $stmt->bind_param('i', $regionID);
                    $stmt->execute();
                    $stmt->bind_result($one);
                    $regionExists = (bool)$stmt->fetch();
                    $stmt->close();
                }
                if (!$regionExists) {
                    json_exit(['success' => false, 'message' => 'Region not found.']);
                }

                if ($stmt = $conn->prepare('INSERT INTO farm (farmerID, regionID, area_size) VALUES (?, ?, ?)')) {
                    $stmt->bind_param('iid', $farmerID, $regionID, $area_size);
                    $ok = $stmt->execute();
                    $stmt->close();
                    if (!$ok) throw new Exception('Insert failed.');
                }
                json_exit(['success' => true, 'message' => 'Farm added.']);
            }

            case 'delete': {
                $farmID = isset($body['farmID']) ? (int)$body['farmID'] : 0;
                if ($farmID <= 0) json_exit(['success' => false, 'message' => 'Invalid farmID.']);

                if ($stmt = $conn->prepare('DELETE FROM farm WHERE farmID = ? AND farmerID = ? LIMIT 1')) {
                    $stmt->bind_param('ii', $farmID, $farmerID);
                    $stmt->execute();
                    $rows = $stmt->affected_rows;
                    $stmt->close();
                    if ($rows < 1) json_exit(['success' => false, 'message' => 'Farm not found or not owned by you.']);
                }
                json_exit(['success' => true, 'message' => 'Farm deleted.']);
            }

            case 'update_area': {
                $farmID    = isset($body['farmID']) ? (int)$body['farmID'] : 0;
                $area_size = isset($body['area_size']) ? (float)$body['area_size'] : 0.0;
                if ($farmID <= 0 || $area_size <= 0) {
                    json_exit(['success' => false, 'message' => 'Invalid input.']);
                }
                if ($stmt = $conn->prepare('UPDATE farm SET area_size = ? WHERE farmID = ? AND farmerID = ?')) {
                    $stmt->bind_param('dii', $area_size, $farmID, $farmerID);
                    $stmt->execute();
                    $rows = $stmt->affected_rows;
                    $stmt->close();
                    if ($rows < 1) json_exit(['success' => false, 'message' => 'Farm not found or unchanged.']);
                }
                json_exit(['success' => true, 'message' => 'Farm updated.']);
            }

            default:
                json_exit(['success' => false, 'message' => 'Unknown action.']);
        }
    } catch (Throwable $e) {
        http_response_code(500);
        json_exit(['success' => false, 'message' => $e->getMessage()]);
    }
}

/* ----------------------- Page (GET) ----------------------- */
/* Load regions for the add form */
$regions = [];
if ($stmt = $conn->prepare('SELECT regionID, name FROM region ORDER BY name')) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $regions[] = $row; }
    $stmt->close();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>My Farms</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="fields.css?v=3" />
</head>
<body class="fields-page kts-has-sidebar">
  <?php
    // Include the shared sidebar (absolute path)
    // Adjust $BASE inside the partial if your app base changes.
    include __DIR__ . '../../partials/partials.php';
  ?>

  <div class="container">
    <header>
      <h1 class="page-title">My Farms</h1>
      <p class="subtitle">
        Manage your farm records (add or remove). Logged in as Farmer #<?= htmlspecialchars($farmerID) ?>
      </p>
    </header>

    <div class="layout">
      <!-- Left: Add Farms -->
      <aside class="panel add-panel">
        <div class="panel-header">Add Farms</div>
        <div class="panel-body">
          <form id="addFarmForm" class="form-grid">
            <div class="field">
              <label for="regionID">Region</label>
              <select id="regionID" name="regionID" required>
                <?php if (!empty($regions)): ?>
                  <option value="" disabled selected>Select a region</option>
                  <?php foreach ($regions as $reg): ?>
                    <option value="<?= (int)$reg['regionID'] ?>"><?= htmlspecialchars($reg['name']) ?></option>
                  <?php endforeach; ?>
                <?php else: ?>
                  <option value="" disabled selected>No regions found</option>
                <?php endif; ?>
              </select>
            </div>

            <div class="field">
              <label for="area_size">Area Size (acres)</label>
              <input type="number" step="0.01" min="0.01" id="area_size" name="area_size"
                     placeholder="e.g., 2.50" required />
            </div>

            <button type="submit" class="btn btn-primary">Add</button>
            <div id="formMsg" class="msg"></div>
          </form>
        </div>
      </aside>

      <!-- Right: Your farms grid -->
      <section class="panel right-panel">
        <div class="panel-header">Your farms</div>
        <div class="panel-body">
          <div id="farmsList" class="farms-grid"></div>
          <div id="listMsg" class="msg"></div>
        </div>
      </section>
    </div>
  </div>

<script>
/* Frontend: fetch-based CRUD hitting this same file as endpoint */
const farmsList = document.getElementById('farmsList');
const listMsg   = document.getElementById('listMsg');
const formMsg   = document.getElementById('formMsg');
const addForm   = document.getElementById('addFarmForm');

async function loadFarms() {
  listMsg.textContent = 'Loading...';
  farmsList.innerHTML = '';
  try {
    const res = await fetch('fields.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ action: 'list' })
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Failed to load');
    listMsg.textContent = '';
    if (!data.farms || data.farms.length === 0) {
      farmsList.innerHTML = '<div class="empty">No farms yet. Add your first farm on the left.</div>';
      return;
    }
    farmsList.innerHTML = data.farms.map(f => `
      <div class="farm-card">
        <div class="farm-name">Farm #${f.farmID}</div>
        <div class="farm-meta">${escapeHtml(f.region_name || ('Region ID ' + f.regionID))}</div>
        <div class="farm-meta">Area: ${Number(f.area_size).toFixed(2)} acres</div>
        <div class="card-actions">
          <button class="btn-outline" onclick="editFarm(${f.farmID}, ${Number(f.area_size)})">Edit</button>
          <button class="btn-outline btn-danger" onclick="deleteFarm(${f.farmID})">Delete</button>
        </div>
      </div>
    `).join('');
  } catch (e) {
    listMsg.textContent = e.message || 'Error loading farms';
  }
}

async function deleteFarm(farmID) {
  if (!confirm('Delete this farm?')) return;
  try {
    const res = await fetch('fields.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ action: 'delete', farmID })
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Delete failed');
    await loadFarms();
  } catch (e) {
    alert(e.message || 'Delete failed');
  }
}

async function editFarm(farmID, currentArea) {
  const val = prompt('New area size (acres):', String(currentArea ?? ''));
  if (val == null) return;
  const area_size = parseFloat(val);
  if (!isFinite(area_size) || area_size <= 0) { alert('Enter a valid positive number.'); return; }
  try {
    const res = await fetch('fields.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ action: 'update_area', farmID, area_size })
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Update failed');
    await loadFarms();
  } catch (e) {
    alert(e.message || 'Update failed');
  }
}

addForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  formMsg.textContent = '';
  const regionID  = parseInt(document.getElementById('regionID').value, 10);
  const area_size = parseFloat(document.getElementById('area_size').value);
  if (!regionID || !area_size || area_size <= 0) {
    formMsg.textContent = 'Please choose a region and enter a valid area size.';
    return;
  }
  try {
    const res = await fetch('fields.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ action: 'create', regionID, area_size })
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Add failed');
    addForm.reset();
    await loadFarms();
    formMsg.textContent = 'Farm added successfully.';
  } catch (e) {
    formMsg.textContent = e.message || 'Add failed';
  }
});

function escapeHtml(s) {
  return String(s)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}

loadFarms();
</script>
</body>
</html>
