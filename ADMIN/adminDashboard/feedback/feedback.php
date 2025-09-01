<?php

/**
 * Admin • Feedback (messaging hub)
 * Path: /admin/adminDashboard/feedback/feedback.php
 *
 * Table `message`:
 *   messageID INT PK AI
 *   SID INT (sender userID)
 *   RID INT (receiver userID)
 *   timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
 *   text TEXT
 *   unread_count INT NOT NULL DEFAULT 0  <-- integer unread counter
 *
 * Conversation logic (admin side):
 *   - Selected farmer userID = $selectedUID
 *   - Admin userID = $_SESSION['user_id']  (current admin)
 *   - Thread = (SID=admin & RID=farmer) OR (SID=farmer & RID=admin)
 *   - On open, mark all farmer→admin messages as read (unread_count=0)
 *   - Admin-sent bubbles align RIGHT, farmer-sent bubbles align LEFT
 *   - Farmer tiles show unread badge = SUM(unread_count) where RID=admin
 */

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

/* =========================
   Locals & helpers
   ========================= */
$flash = null;
$flash_class = 'ok';

$adminID     = (int)$_SESSION['user_id'];                   // current admin user id
$selectedUID = isset($_GET['uid']) ? (int)$_GET['uid'] : 0; // farmer user id chosen from tiles

/**
 * Resolve profile image URL stored like "uploads/user_18_*.png" under /Dashboard/profile/
 */
function farmer_img_src(?string $path): string
{
  if ($path && $path !== '') {
    return '../../../Dashboard/profile/' . ltrim($path, '/');
  }
  return '../../../Dashboard/images/default-avatar.png';
}

/* ===========================================
   Handle POST: send a new admin message to user
   - Inserts one row into `message` with:
       SID = $adminID, RID = $selectedUID, text = $adminText, unread_count = 1
     (so the user sees 1 more unread)
   - Text limited to 4000 chars
   =========================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedUID > 0) {
  // Extract and validate input
  $adminText = trim((string)($_POST['admin_text'] ?? ''));

  if ($adminText === '') {
    $flash = "Message cannot be empty.";
    $flash_class = "err";
  } else {
    if (mb_strlen($adminText) > 4000) {
      $adminText = mb_substr($adminText, 0, 4000);
    }

    // Insert new message row (SID=admin, RID=selected farmer) with unread_count=1
    $stmt = $conn->prepare("INSERT INTO message (SID, RID, text, unread_count) VALUES (?, ?, ?, 1)");
    if ($stmt === false) {
      http_response_code(500);
      $flash = "Prepare failed: " . htmlspecialchars($conn->error);
      $flash_class = "err";
    } else {
      $stmt->bind_param("iis", $adminID, $selectedUID, $adminText);
      if (!$stmt->execute()) {
        http_response_code(500);
        $flash = "Send failed: " . htmlspecialchars($stmt->error);
        $flash_class = "err";
      } else {
        // PRG pattern: reload same page with selected uid
        header("Location: ?uid=" . $selectedUID);
        exit;
      }
      $stmt->close();
    }
  }
}

/* ===========================================
   Left column: farmer tiles (from `farmer`)
   Also compute unread sums per user (RID = admin)
   =========================================== */
$farmers = [];
if ($res = $conn->query(
  "SELECT f.farmerID, f.userID, f.profile_picture, f.city, f.country, u.name AS farm_name, f.phone
   FROM farmer f
   JOIN users u ON f.userID = u.userID
   ORDER BY u.name ASC"
)) {
  while ($row = $res->fetch_assoc()) $farmers[] = $row;
  $res->free();
}

/* Build map of unread counts keyed by SID (sender = farmer), where receiver is admin */
$unreadByUser = [];
$stmt = $conn->prepare(
  "SELECT SID, SUM(unread_count) AS unread_sum
   FROM message
   WHERE RID = ?
   GROUP BY SID"
);
if ($stmt) {
  $stmt->bind_param("i", $adminID);
  if ($stmt->execute()) {
    $r = $stmt->get_result();
    while ($row = $r->fetch_assoc()) {
      $unreadByUser[(int)$row['SID']] = (int)$row['unread_sum'];
    }
    $r->free();
  }
  $stmt->close();
}

/* ===========================================
   Right panel: selected farmer profile
   Middle panel: conversation thread from `message`
   On open: mark farmer→admin messages as read (unread_count=0)
   =========================================== */
$farmer = null;
$thread = [];

if ($selectedUID > 0) {
  // Load farmer profile (by userID)
  $stmt = $conn->prepare(
    "SELECT
      f.farmerID, f.userID, f.profile_picture, f.address_line1, f.address_line2, f.city, f.state,
      f.country, f.phone, f.gender, u.name AS farm_name, COALESCE(fs.farm_size, 0) AS farm_size, f.years_experience
   FROM farmer f
   JOIN users u ON f.userID = u.userID
   LEFT JOIN (
       SELECT farmerID, SUM(area_size) AS farm_size
       FROM farm
       GROUP BY farmerID
   ) fs ON fs.farmerID = f.farmerID
   WHERE f.userID = ? LIMIT 1"
  );

  if ($stmt) {
    $stmt->bind_param("i", $selectedUID);
    if ($stmt->execute()) $farmer = $stmt->get_result()->fetch_assoc();
    $stmt->close();
  }

  // Mark as read: all farmer -> admin messages for this conversation
  $u = $conn->prepare(
    "UPDATE message
     SET unread_count = 0
     WHERE SID = ? AND RID = ? AND unread_count > 0"
  );
  if ($u) {
    $u->bind_param("ii", $selectedUID, $adminID);
    $u->execute();
    $u->close();
    // After zeroing, also zero our local badge cache for this farmer
    $unreadByUser[$selectedUID] = 0;
  }

  // Load conversation (admin <-> selected farmer), ordered oldest to newest
  $stmt = $conn->prepare(
    "SELECT messageID, SID, RID, timestamp, text
     FROM message
     WHERE (SID = ? AND RID = ?) OR (SID = ? AND RID = ?)
     ORDER BY timestamp ASC, messageID ASC"
  );
  if ($stmt) {
    $stmt->bind_param("iiii", $adminID, $selectedUID, $selectedUID, $adminID);
    if ($stmt->execute()) {
      $r = $stmt->get_result();
      while ($row = $r->fetch_assoc()) $thread[] = $row;
      $r->free();
    }
    $stmt->close();
  }
}
?>
<!doctype html>
<html lang="en">

<head>
  <!-- ======= HTML HEAD (styles only, no logic) ======= -->
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin • Feedback</title>
  <link rel="stylesheet" href="feedback.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../maindash/dashboard.css?v=<?php echo time(); ?>">
  <style>
    /* tiny badge for unread counts on farmer tiles */
    .badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 18px;
      height: 18px;
      padding: 0 6px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 700;
      background: #ef4444;
      color: #fff;
      margin-left: 8px;
    }
  </style>
</head>

<body>
  <div class="admin-wrapper">
    <?php @include __DIR__ . '/../../partials/sidebar.php'; ?>
    <main class="admin-main">
      <!-- ============== PAGE HEADER ============== -->
      <div class="page-head">
        <h1>Admin Feedback</h1>
        <form method="get" class="inline">
          <?php if ($selectedUID): ?>
            <input type="hidden" name="uid" value="<?php echo (int)$selectedUID; ?>">
          <?php endif; ?>
          <button class="btn secondary" type="submit">🔄 Refresh</button>
        </form>
      </div>

      <!-- ============== FLASH MESSAGE (if any) ============== -->
      <?php if ($flash): ?>
        <div class="flash <?php echo $flash_class; ?>"><?php echo htmlspecialchars($flash); ?></div>
      <?php endif; ?>

      <div class="admin-grid">
        <!-- ============== LEFT: FARMER TILES ============== -->
        <section class="tiles" aria-label="Farmers">
          <?php foreach ($farmers as $f):
            $uid    = (int)$f['userID'];
            $active = $selectedUID === $uid ? 'active' : '';
            $pic    = farmer_img_src($f['profile_picture'] ?? '');
            $unread = $unreadByUser[$uid] ?? 0;
          ?>
            <a class="tile <?php echo $active; ?>" href="?uid=<?php echo $uid; ?>">
              <img src="<?php echo htmlspecialchars($pic); ?>" alt="Profile">
              <div>
                <div class="tile-title">
                  <?php echo htmlspecialchars($f['farm_name'] ?: ('User #' . $uid)); ?>
                  <?php if ($unread > 0): ?><span class="badge"><?php echo (int)$unread; ?></span><?php endif; ?>
                </div>
                <div class="tile-sub">
                  <?php echo htmlspecialchars(trim(($f['city'] ?: '') . ($f['country'] ? ', ' . $f['country'] : ''))); ?>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        </section>

        <!-- ============== MIDDLE: CHAT THREAD ============== -->
        <section class="chat-shell" aria-label="Conversation">
          <div class="chat-scroll">

            <?php if (!$selectedUID): ?>
              <!-- Empty state before selecting a farmer -->
              <div class="feedback-item">Select a farmer tile to start messaging.</div>

            <?php else: ?>
              <?php if (empty($thread)): ?>
                <!-- Empty state when no messages exist yet -->
                <div class="feedback-item">No messages yet. Start the conversation below.</div>
              <?php else: ?>
                <?php foreach ($thread as $m): ?>
                  <?php
                  // Who sent it? Align accordingly:
                  // - Admin (SID == $adminID) => right bubble (admin)
                  // - Farmer (SID == $selectedUID) => left bubble (user)
                  $isAdmin  = ((int)$m['SID'] === $adminID);
                  $bubble   = $isAdmin ? 'admin' : 'user';
                  $metaSide = $isAdmin ? 'right' : 'left';
                  $whoLabel = $isAdmin ? 'Admin' : ('User #' . $selectedUID);
                  $when     = date("M d, Y H:i", strtotime($m['timestamp']));
                  ?>
                  <div class="msg-row">
                    <div class="bubble <?php echo $bubble; ?>">
                      <?php echo nl2br(htmlspecialchars((string)$m['text'])); ?>
                    </div>
                    <div class="meta <?php echo $metaSide; ?>">
                      <?php echo $whoLabel; ?> • <?php echo $when; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            <?php endif; ?>

          </div>

          <!-- Composer appears only when a farmer is selected -->
          <?php if ($selectedUID): ?>
            <form class="composer" method="post">
              <textarea name="admin_text" maxlength="4000" placeholder="Send a new message to this farmer..." required></textarea>
              <button class="btn" type="submit">Send</button>
            </form>
          <?php endif; ?>
        </section>

        <!-- ============== RIGHT: FARMER PROFILE ============== -->
        <aside class="profile" aria-label="Farmer details">
          <?php if (!$farmer): ?>
            <div>Select a farmer to view profile.</div>
          <?php else: ?>
            <img src="<?php echo htmlspecialchars(farmer_img_src($farmer['profile_picture'] ?? '')); ?>" alt="Profile">
            <div class="line title"><?php echo htmlspecialchars($farmer['farm_name'] ?: ('User #' . $selectedUID)); ?></div>
            <div class="line"><?php echo htmlspecialchars(trim(($farmer['city'] ?: '') . ($farmer['country'] ? ', ' . $farmer['country'] : ''))); ?></div>
            <div class="line">Phone: <?php echo htmlspecialchars($farmer['phone']); ?></div>
            <div class="line">Gender: <?php echo htmlspecialchars($farmer['gender']); ?></div>
            <div class="line">Farm size: <?php echo htmlspecialchars($farmer['farm_size']); ?></div>
            <div class="line">Experience: <?php echo htmlspecialchars($farmer['years_experience']); ?> yrs</div>
            <div class="line">Address: <?php echo htmlspecialchars(trim(($farmer['address_line1'] ?? '') . ' ' . ($farmer['address_line2'] ?? ''))); ?></div>
          <?php endif; ?>
        </aside>
      </div>
    </main>
  </div>
</body>

</html>