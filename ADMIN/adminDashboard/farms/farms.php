<?php
// farms.php — Admin view: Users (non-admin via farmer join) -> Farms -> Region location
// Requires: main.php sets $conn = new mysqli(...)
session_start();
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../../../main.php'; // mysqli $conn from project root

/* =========================
   Session / connection guard
   ========================= */
if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo "<!doctype html><html><body><p>Unauthorized</p></body></html>";
  exit;
}

if (!isset($conn) || !($conn instanceof mysqli)) {
  http_response_code(500);
  echo "<!doctype html><html><body><p>Database connection not configured.</p></body></html>";
  exit;
}

// ---- Search (by name or userID) ----
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

// Build query
$sql = "
  SELECT
    u.userID,
    u.name          AS user_name,
    f.farmerID,
    fa.farmID,
    fa.area_size,
    fa.regionID,
    r.name          AS region_name,
    r.location      AS region_location
  FROM users u
  INNER JOIN farmer f     ON f.userID = u.userID       -- ensures non-admin users only (those who are farmers)
  LEFT  JOIN farm   fa    ON fa.farmerID = f.farmerID
  LEFT  JOIN region r     ON r.regionID = fa.regionID
";

$params = [];
$types  = '';
if ($q !== '') {
    // Filter by user name (LIKE) or exact userID if q is numeric
    $sql .= " WHERE (u.name LIKE CONCAT('%', ?, '%')";
    $params[] = $q; $types .= 's';

    if (ctype_digit($q)) {
        $sql .= " OR u.userID = ?)";
        $params[] = (int)$q; $types .= 'i';
    } else {
        $sql .= ")";
    }
}
$sql .= " ORDER BY u.userID ASC, fa.farmID ASC";

$stmt = $conn->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$res = $stmt->get_result();

// Group rows by user
$users = [];         // userID => ['userID','user_name','farmerID','farms'=>[...]]
$totalFarms = 0;

while ($row = $res->fetch_assoc()) {
    $uid = (int)$row['userID'];
    if (!isset($users[$uid])) {
        $users[$uid] = [
            'userID'    => $uid,
            'user_name' => $row['user_name'],
            'farmerID'  => (int)$row['farmerID'],
            'farms'     => []
        ];
    }
    if ($row['farmID'] !== null) {
        $users[$uid]['farms'][] = [
            'farmID'          => (int)$row['farmID'],
            'area_size'       => (float)$row['area_size'],
            'regionID'        => (int)$row['regionID'],
            'region_name'     => $row['region_name'],
            'region_location' => $row['region_location'],
        ];
        $totalFarms++;
    }
}
$stmt->close();

$totalUsers = count($users);

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Farms — Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="farms.css?v=1" />
</head>
<body class="farms-admin">
  <?php @include __DIR__ . '/../../partials/sidebar.php'; ?>
  <div class="container">
    <header class="page-head">
      <div>
        <h1>Farms — Admin</h1>
        <p class="muted">All farmers (non-admin users) and their farms with locations.</p>
      </div>
      <form class="search" method="get" action="">
        <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search by name or user ID…" />
        <button type="submit">Search</button>
      </form>
    </header>

    <section class="stats">
      <div class="stat">
        <div class="value"><?= number_format($totalUsers) ?></div>
        <div class="label">Users</div>
      </div>
      <div class="stat">
        <div class="value"><?= number_format($totalFarms) ?></div>
        <div class="label">Farms</div>
      </div>
    </section>

    <?php if ($totalUsers === 0): ?>
      <div class="empty">No matching users.</div>
    <?php else: ?>
      <div class="users">
        <?php foreach ($users as $u): ?>
          <details class="user-card" open>
            <summary class="user-summary">
              <div class="user-meta">
                <div class="user-name"><?= h($u['user_name'] ?: 'Unnamed') ?></div>
                <div class="user-ids">User #<?= (int)$u['userID'] ?> · Farmer #<?= (int)$u['farmerID'] ?></div>
              </div>
              <div class="chips">
                <span class="chip"><?= count($u['farms']) ?> farm<?= count($u['farms'])===1?'':'s' ?></span>
              </div>
            </summary>

            <?php if (empty($u['farms'])): ?>
              <div class="panel-body">
                <p class="muted">No farms recorded for this user.</p>
              </div>
            <?php else: ?>
              <div class="farms-grid">
                <?php foreach ($u['farms'] as $f): ?>
                  <div class="farm-card">
                    <div class="farm-title">Farm #<?= (int)$f['farmID'] ?></div>
                    <div class="farm-row">
                      <span class="k">Area</span>
                      <span class="v"><?= number_format((float)$f['area_size'], 2) ?> acres</span>
                    </div>
                    <div class="farm-row">
                      <span class="k">Region</span>
                      <span class="v">
                        <?php
                          $rName = $f['region_name'] ?: ('ID '.$f['regionID']);
                          $rLoc  = $f['region_location'] ? ' — '.h($f['region_location']) : '';
                          echo h($rName) . $rLoc;
                        ?>
                      </span>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </details>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
