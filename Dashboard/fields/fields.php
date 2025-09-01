<?php
/**
 * Fields — CRUD for maindb.farm
 * - GET  => renders page (HTML)
 * - POST => JSON API: list | create | delete
 *
 * Restores the dual-mode behavior and endpoints you had before,
 * while switching the sidebar to the new shared partial.
 *
 * Requires:
 *   - $_SESSION['user_id'] (farmerID)
 *   - main.php defining either $conn (mysqli) or $pdo (PDO)
 */
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../main.php';

/* ──────────────────────────────────────────────────────────────
   Session guard
   ────────────────────────────────────────────────────────────── */
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
//
// Map the logged-in user to the farmer primary key
$userID = (int) $_SESSION['user_id'];
$row = db_query_all("SELECT farmerID FROM farmer WHERE userID = ? LIMIT 1", [$userID]);
if (!$row) {
  http_response_code(403);
  echo json_encode(['success' => false, 'message' => 'No farmer profile for this user.']);
  exit;
}
$farmerID = (int)$row[0]['farmerID'];


/* 
 * Important: switch to HTML content-type for GET
 * so this same file can render the page.
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: text/html; charset=utf-8');
}

/* ──────────────────────────────────────────────────────────────
   DB helpers: work with either $pdo (PDO) or $conn (MySQLi)
   ────────────────────────────────────────────────────────────── */
function db_is_pdo() {
    return isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO;
}
function db_is_mysqli() {
    return isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli;
}
function db_query_all(string $sql, array $params = []) : array {
    if (db_is_pdo()) {
        $stmt = $GLOBALS['pdo']->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif (db_is_mysqli()) {
        $stmt = $GLOBALS['conn']->prepare($sql);
        if ($params) {
            $types = '';
            $bind  = [];
            foreach ($params as $p) {
                if (is_int($p)) $types .= 'i';
                elseif (is_float($p)) $types .= 'd';
                else $types .= 's';
                $bind[] = $p;
            }
            $stmt->bind_param($types, ...$bind);
        }
        $stmt->execute();
        $res  = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $rows;
    }
    throw new Exception('No database connection ($pdo or $conn) found.');
}
function db_exec(string $sql, array $params = []) : bool {
    if (db_is_pdo()) {
        $stmt = $GLOBALS['pdo']->prepare($sql);
        return $stmt->execute($params);
    } elseif (db_is_mysqli()) {
        $stmt = $GLOBALS['conn']->prepare($sql);
        if ($params) {
            $types = '';
            $bind  = [];
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
function db_last_id() : int {
    if (db_is_pdo())     return (int)$GLOBALS['pdo']->lastInsertId();
    if (db_is_mysqli())  return (int)$GLOBALS['conn']->insert_id;
    return 0;
}

/* ──────────────────────────────────────────────────────────────
   POST → JSON API
   - list:   farms of this farmer (join region for name)
   - create: add a farm (regionID, area_size)
   - delete: delete a farm you own
   ────────────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Accept JSON or form-urlencoded
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
        if ($action === 'list') {
            $farms = db_query_all(
                "SELECT f.farmID, f.regionID, r.name AS region_name, f.area_size
                   FROM farm f
                   JOIN region r ON r.regionID = f.regionID
                  WHERE f.farmerID = ?
               ORDER BY f.farmID DESC",
                [$farmerID]
            );
            echo json_encode(['success' => true, 'farms' => $farms]);
            exit;

        } elseif ($action === 'create') {
            $regionID  = isset($payload['regionID'])  ? (int)$payload['regionID']  : 0;
            $area_size = isset($payload['area_size']) ? (float)$payload['area_size'] : 0.0;

            if ($regionID <= 0 || $area_size <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'regionID and area_size are required and must be positive']);
                exit;
            }

            // Validate region
            $exists = db_query_all("SELECT regionID FROM region WHERE regionID = ?", [$regionID]);
            if (!$exists) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid regionID']);
                exit;
            }

            $ok = db_exec(
                "INSERT INTO farm (farmerID, regionID, area_size) VALUES (?, ?, ?)",
                [$farmerID, $regionID, $area_size]
            );
            echo json_encode($ok
                ? ['success' => true, 'farmID' => db_last_id()]
                : ['success' => false, 'message' => 'Insert failed']
            );
            exit;

        } elseif ($action === 'delete') {
            $farmID = isset($payload['farmID']) ? (int)$payload['farmID'] : 0;
            if ($farmID <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'farmID is required']);
                exit;
            }

            // Ownership check
            $own = db_query_all("SELECT farmID FROM farm WHERE farmID = ? AND farmerID = ?", [$farmID, $farmerID]);
            if (!$own) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Not allowed']);
                exit;
            }

            $ok = db_exec("DELETE FROM farm WHERE farmID = ?", [$farmID]);
            echo json_encode(['success' => $ok]);
            exit;

        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
            exit;
        }
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
        exit;
    }
}

/* ──────────────────────────────────────────────────────────────
   GET → Render the page
   - Loads regions for the dropdown
   - Sidebar included via the new shared partial
   ────────────────────────────────────────────────────────────── */
$regions = [];
try {
    $regions = db_query_all("SELECT regionID, name FROM region ORDER BY name ASC");
} catch (Throwable $e) {
    // render page anyway; form will show a helpful message
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>My Farms</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="fields.css" />
</head>
<body class="kts-has-sidebar"><!-- ensures page offset for sidebar -->
  <?php
    // Include the shared sidebar (absolute path)
    // Adjust $BASE inside the partial if your app base changes.
    include __DIR__ . '../../partials/partials.php';
  ?>

  <div class="container">
    <!-- Page header -->
    <header class="header">
      <h1>My Farms</h1>
      <p class="subtitle">Manage your farm records (add or remove). Logged in as User #<?= htmlspecialchars($farmerID) ?></p>
    </header>

    <!-- Add form -->
    <section class="panel">
      <h2>Add a Farm</h2>
      <form id="addFarmForm" class="form">
        <div class="form-row">
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
        <div class="form-row">
          <label for="area_size">Area Size (acres)</label>
          <input type="number" step="0.01" min="0.01" id="area_size" name="area_size" placeholder="e.g., 2.50" required />
        </div>
        <button type="submit" class="btn">Add Farm</button>
        <div id="formMsg" class="msg"></div>
      </form>
    </section>

    <!-- List -->
    <section class="panel">
      <h2>Your Farms</h2>
      <div id="farmsList" class="list"></div>
      <div id="listMsg" class="msg"></div>
    </section>
  </div>

<script>
/* Frontend: fetch-based CRUD using this same file as endpoint */
const farmsList = document.getElementById('farmsList');
const listMsg   = document.getElementById('listMsg');
const formMsg   = document.getElementById('formMsg');
const addForm   = document.getElementById('addFarmForm');

/** Load farms for this user */
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
      farmsList.innerHTML = '<div class="empty">No farms yet. Add your first farm above.</div>';
      return;
    }
    farmsList.innerHTML = data.farms.map(f => `
      <div class="card">
        <div class="card-main">
          <div><strong>Farm #${f.farmID}</strong></div>
          <div>Region: ${escapeHtml(f.region_name || ('ID ' + f.regionID))}</div>
          <div>Area Size: ${Number(f.area_size).toFixed(2)} acres</div>
        </div>
        <div class="card-actions">
          <button class="btn btn-danger" onclick="deleteFarm(${f.farmID})">Delete</button>
        </div>
      </div>
    `).join('');
  } catch (e) {
    listMsg.textContent = e.message || 'Error loading farms';
  }
}

/** Delete a farm (with confirmation) */
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

/** Handle create */
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

/** Small helper to keep text safe */
function escapeHtml(s) {
  return String(s)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}

// initial load
loadFarms();
</script>
</body>
</html>
