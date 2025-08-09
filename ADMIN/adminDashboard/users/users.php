<?php
// admin/adminDashboard/users.php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: ../login/login.html'); // adjust if needed
  exit;
}
//absolute path
require_once $_SERVER['DOCUMENT_ROOT'] . '/ProjectFolder/main.php';

$userID = (int)$_SESSION['user_id'];

// (Optional) admin-only gate
// if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { http_response_code(403); exit('Forbidden'); }

$active = 'users'; // highlight "Users" in the admin sidebar

/* URL bases (adjust if your served path differs) */
if (!defined('ADMIN_BASE_URL'))     define('ADMIN_BASE_URL', '/users/ProjectFolder/admin');
if (!defined('DASHBOARD_BASE_URL')) define('DASHBOARD_BASE_URL', '/users/ProjectFolder/Dashboard'); // for avatar path

/* CSRF */
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

/* -------------------------
   DELETE (POST)
--------------------------*/
if (
  $_SERVER['REQUEST_METHOD'] === 'POST'
  && isset($_POST['action'], $_POST['csrf'])
  && $_POST['action'] === 'delete'
  && hash_equals($_SESSION['csrf'], $_POST['csrf'])
) {

  $deleteId = (int)($_POST['user_id'] ?? 0);

  $conn->begin_transaction();
  try {
    // delete child rows first
    $stmt = $conn->prepare("DELETE FROM FARMER WHERE userID = ?");
    $stmt->bind_param('i', $deleteId);
    if (!$stmt->execute()) throw new Exception($stmt->error);
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM USERS WHERE userID = ?");
    $stmt->bind_param('i', $deleteId);
    if (!$stmt->execute()) throw new Exception($stmt->error);
    $stmt->close();

    $conn->commit();
    $flash = ['type' => 'ok', 'text' => 'User deleted.'];
  } catch (Exception $e) {
    $conn->rollback();
    $flash = ['type' => 'err', 'text' => 'Delete failed. Try again.'];
  }
}

/* -------------------------
   SEARCH + PAGINATION (POST)
--------------------------*/
$searchBy = $_POST['by']   ?? 'name';        // name|email|role
$q        = trim($_POST['q'] ?? '');
$page     = max(1, (int)($_POST['page'] ?? 1));
$perPage  = 10;
$offset   = ($page - 1) * $perPage;

/* WHERE clause */
$where  = '1';
$params = [];
$types  = '';

if ($q !== '') {
  switch ($searchBy) {
    case 'email':
      $where .= ' AND U.email LIKE ?';
      $params[] = "%$q%";
      $types .= 's';
      break;
    case 'role':
      $where .= ' AND U.role = ?';
      $params[] = $q;
      $types .= 's';
      break;
    default: // name
      $where .= ' AND U.name LIKE ?';
      $params[] = "%$q%";
      $types .= 's';
  }
}

/* Count */
$sqlCount = "SELECT COUNT(*) AS c
             FROM USERS U
             LEFT JOIN FARMER F ON F.userID = U.userID
             WHERE $where";
$countStmt = $conn->prepare($sqlCount);
if ($types) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalRows = ($countStmt->get_result()->fetch_assoc()['c']) ?? 0;
$countStmt->close();
$totalPages = (int)ceil($totalRows / $perPage);

/* Fetch page */
$sql = "SELECT
          U.userID, U.name, U.email, U.role,
          F.farmerID, F.profile_picture, F.phone, F.city, F.country
        FROM USERS U
        LEFT JOIN FARMER F ON F.userID = U.userID
        WHERE $where
        ORDER BY U.userID DESC
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($types) {
  $types2 = $types . 'ii';
  $params2 = array_merge($params, [$perPage, $offset]);
  $stmt->bind_param($types2, ...$params2);
} else {
  $stmt->bind_param('ii', $perPage, $offset);
}
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* Avatar URL helper */
function avatarUrl(?string $relPath): string
{
  if (!$relPath) return DASHBOARD_BASE_URL . '/images/default-avatar.png';
  return DASHBOARD_BASE_URL . '/profile/' . $relPath; // stored like "uploads/xxx.jpg"
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin • Users</title>
  <link rel="stylesheet" href="../dashboard.css" />
  <link rel="stylesheet" href="../../dashboard.css">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
  <div class="admin-wrapper">

    <!-- here the sidebar is loading -->
    <?php
    include dirname(__DIR__, 2) . '/partials/sidebar.php';
    // or absolute:
    // include $_SERVER['DOCUMENT_ROOT'] . '/ProjectFolder/admin/partials/sidebar.php';

    ?>

    <main class="admin-main">
      <div class="page-head">
        <h1><i class="fa-solid fa-users"></i> Users</h1>

        <!-- SEARCH (POST) -->
        <form class="search-bar" method="post" action="users.php">
          <select name="by">
            <option value="name" <?= $searchBy === 'name'  ? 'selected' : '' ?>>Name</option>
            <option value="email" <?= $searchBy === 'email' ? 'selected' : '' ?>>Email</option>
            <option value="role" <?= $searchBy === 'role'  ? 'selected' : '' ?>>Role</option>
          </select>
          <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search…" />
          <button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
          <?php if ($q !== ''): ?>
            <button class="btn btn-muted" type="submit" name="clear" value="1">Clear</button>
          <?php endif; ?>
          <!-- keep current page at 1 on new search -->
          <input type="hidden" name="page" value="1">
        </form>
      </div>

      <?php if (!empty($flash)): ?>
        <div class="flash <?= $flash['type'] === 'ok' ? 'ok' : 'err' ?>"><?= htmlspecialchars($flash['text']) ?></div>
      <?php endif; ?>

      <div class="table-wrap">
        <table class="users-table">
          <thead>
            <tr>
              <th>Avatar</th>
              <th>User</th>
              <th>Email</th>
              <th>Role</th>
              <th>FarmerID</th>
              <th>Phone</th>
              <th>Location</th>
              <th style="width:120px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$rows): ?>
              <tr>
                <td colspan="8">No users found.</td>
              </tr>
              <?php else: foreach ($rows as $r): ?>
                <tr>
                  <td><img class="avatar" src="<?= htmlspecialchars(avatarUrl($r['profile_picture'])) ?>" alt="avatar"></td>
                  <td><?= htmlspecialchars($r['name']) ?></td>
                  <td><?= htmlspecialchars($r['email']) ?></td>
                  <td><?= htmlspecialchars($r['role']) ?></td>
                  <td><?= $r['farmerID'] ? (int)$r['farmerID'] : '—' ?></td>
                  <td><?= htmlspecialchars($r['phone'] ?: '—') ?></td>
                  <td><?= htmlspecialchars(trim(($r['city'] ?: '') . ', ' . ($r['country'] ?: ''), ', ')) ?></td>
                  <td>
                    <div class="actions">
                      <form method="post" action="users.php"
                        onsubmit="return confirm('Delete this user and all related farmer data?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" value="<?= (int)$r['userID'] ?>">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                        <!-- preserve current filters after delete -->
                        <input type="hidden" name="by" value="<?= htmlspecialchars($searchBy) ?>">
                        <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
                        <input type="hidden" name="page" value="<?= (int)$page ?>">
                        <button class="btn btn-danger" type="submit" title="Delete">
                          <i class="fa-solid fa-trash"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
            <?php endforeach;
            endif; ?>
          </tbody>
        </table>
      </div>

      <!-- PAGINATION (POST buttons) -->
      <?php if ($totalPages > 1): ?>
        <form class="pagination" method="post" action="users.php">
          <input type="hidden" name="by" value="<?= htmlspecialchars($searchBy) ?>">
          <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
          <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <button class="<?= $p === $page ? 'active' : '' ?>" type="submit" name="page" value="<?= $p ?>">
              <?= $p ?>
            </button>
          <?php endfor; ?>
        </form>
      <?php endif; ?>

    </main>
  </div>
</body>

</html>