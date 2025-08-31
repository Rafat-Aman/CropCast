<?php
/**
 * Admin • Feedback (messaging hub)
 * Path: /admin/adminDashboard/feedback/feedback.php
 */
session_start();
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../../../main.php'; // mysqli $conn from project root

/* ---- Session guard ---- */
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

$flash = null;
$flash_class = 'ok';

$selectedUID = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;

/* ---- Handle POST (admin replies or new outbound) ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedUID > 0) {
  $adminText = trim((string)($_POST['admin_text'] ?? ''));
  $replyTo   = isset($_POST['reply_to']) ? (int)$_POST['reply_to'] : 0;

  if ($adminText === '') {
    $flash = "Message cannot be empty."; $flash_class = "err";
  } else {
    if (mb_strlen($adminText) > 4000) $adminText = mb_substr($adminText, 0, 4000);

    if ($replyTo > 0) {
      $stmt = $conn->prepare(
        "UPDATE feedback
         SET admin_reply = ?
         WHERE feedbackID = ? AND userID = ? AND (admin_reply IS NULL OR admin_reply = '')"
      );
      if ($stmt) {
        $stmt->bind_param("sii", $adminText, $replyTo, $selectedUID);
        if (!$stmt->execute()) {
          $flash = "Reply failed: " . htmlspecialchars($stmt->error); $flash_class = "err";
        } elseif ($stmt->affected_rows === 0) {
          $flash = "Nothing updated (maybe already replied)."; $flash_class = "err";
        } else {
          header("Location: ?uid=" . $selectedUID); exit;
        }
        $stmt->close();
      } else {
        $flash = "Prepare failed: " . htmlspecialchars($conn->error); $flash_class = "err";
      }
    } else {
      // New outbound to this user — feedback.message is NOT NULL, use placeholder
      $placeholder = "[ADMIN]";
      $stmt = $conn->prepare(
        "INSERT INTO feedback (userID, date, message, admin_reply) VALUES (?, NOW(), ?, ?)"
      );
      if ($stmt) {
        $stmt->bind_param("iss", $selectedUID, $placeholder, $adminText);
        if (!$stmt->execute()) {
          $flash = "Send failed: " . htmlspecialchars($stmt->error); $flash_class = "err";
        } else {
          header("Location: ?uid=" . $selectedUID); exit;
        }
        $stmt->close();
      } else {
        $flash = "Prepare failed: " . htmlspecialchars($conn->error); $flash_class = "err";
      }
    }
  }
}

/* ---- Farmer tiles (from `farmer`) ---- */
$farmers = [];
if ($res = $conn->query(
  "SELECT farmerID, userID, profile_picture, city, country, farm_name, phone
   FROM farmer
   ORDER BY farm_name ASC"
)) {
  while ($row = $res->fetch_assoc()) $farmers[] = $row;
  $res->free();
}

/* ---- Selected farmer details + thread ---- */
$farmer = null;
$thread = [];
if ($selectedUID > 0) {
  $stmt = $conn->prepare(
    "SELECT farmerID, userID, profile_picture, address_line1, address_line2, city, state,
            country, phone, gender, farm_name, farm_size, years_experience
     FROM farmer
     WHERE userID = ? LIMIT 1"
  );
  if ($stmt) {
    $stmt->bind_param("i", $selectedUID);
    if ($stmt->execute()) $farmer = $stmt->get_result()->fetch_assoc();
    $stmt->close();
  }

  $stmt = $conn->prepare(
    "SELECT feedbackID, userID, date, message, admin_reply
     FROM feedback
     WHERE userID = ?
     ORDER BY date ASC, feedbackID ASC"
  );
  if ($stmt) {
    $stmt->bind_param("i", $selectedUID);
    if ($stmt->execute()) {
      $r = $stmt->get_result();
      while ($row = $r->fetch_assoc()) $thread[] = $row;
      $r->free();
    }
    $stmt->close();
  }
}

// Resolve profile image URL stored like "uploads/user_18_*.png" under /Dashboard/profile/
function farmer_img_src(?string $path): string {
  if ($path && $path !== '') {
    return '../../../Dashboard/profile/' . ltrim($path, '/');
  }
  return '../../../Dashboard/images/default-avatar.png';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin • Feedback</title>
  <link rel="stylesheet" href="feedback.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../maindash/dashboard.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="admin-wrapper">
  <?php /* IMPORTANT: sidebar partial INSIDE the wrapper as flex child #1 */ ?>
  <?php @include __DIR__ . '/../../partials/sidebar.php'; ?>

  <main class="admin-main">
    <div class="page-head">
      <h1>Admin Feedback</h1>
      <form method="get" class="inline">
        <?php if ($selectedUID): ?><input type="hidden" name="uid" value="<?php echo (int)$selectedUID; ?>"><?php endif; ?>
        <button class="btn secondary" type="submit">🔄 Refresh</button>
      </form>
    </div>

    <?php if ($flash): ?>
      <div class="flash <?php echo $flash_class; ?>"><?php echo htmlspecialchars($flash); ?></div>
    <?php endif; ?>

    <div class="admin-grid">
      <!-- Left: Farmer tiles -->
      <section class="tiles" aria-label="Farmers">
        <?php foreach ($farmers as $f):
          $uid    = (int)$f['userID'];
          $active = $selectedUID === $uid ? 'active' : '';
          $pic    = farmer_img_src($f['profile_picture'] ?? '');
        ?>
          <a class="tile <?php echo $active; ?>" href="?uid=<?php echo $uid; ?>">
            <img src="<?php echo htmlspecialchars($pic); ?>" alt="Profile">
            <div>
              <div class="tile-title"><?php echo htmlspecialchars($f['farm_name'] ?: ('User #'.$uid)); ?></div>
              <div class="tile-sub"><?php echo htmlspecialchars(trim(($f['city']?:'') . ($f['country']?', '.$f['country']:''))); ?></div>
            </div>
          </a>
        <?php endforeach; ?>
      </section>

      <!-- Middle: Chat -->
      <section class="chat-shell" aria-label="Conversation">
        <div class="chat-scroll">
          <?php if (!$selectedUID): ?>
            <div class="feedback-item">Select a farmer tile to start messaging.</div>
          <?php else: foreach ($thread as $r): ?>
            <?php if (!empty($r['message']) && $r['message'] !== '[ADMIN]'): ?>
              <div class="msg-row">
                <div class="bubble user"><?php echo nl2br(htmlspecialchars($r['message'])); ?></div>
                <div class="meta left">User #<?php echo (int)$r['userID']; ?> • <?php echo date("M d, Y H:i", strtotime($r['date'])); ?></div>
              </div>
            <?php endif; ?>

            <?php if (!empty($r['admin_reply'])): ?>
              <div class="msg-row">
                <div class="bubble admin"><?php echo nl2br(htmlspecialchars($r['admin_reply'])); ?></div>
                <div class="meta right">Admin • reply to #<?php echo (int)$r['feedbackID']; ?></div>
              </div>
            <?php endif; ?>

            <?php if (empty($r['admin_reply'])): ?>
              <form method="post" class="composer">
                <input type="hidden" name="reply_to" value="<?php echo (int)$r['feedbackID']; ?>">
                <textarea name="admin_text" required placeholder="Reply to #<?php echo (int)$r['feedbackID']; ?>..."></textarea>
                <button class="btn" type="submit">Reply</button>
              </form>
            <?php endif; ?>
          <?php endforeach; endif; ?>
        </div>

        <?php if ($selectedUID): ?>
        <form class="composer" method="post">
          <textarea name="admin_text" maxlength="4000" placeholder="Send a new message to this farmer..." required></textarea>
          <button class="btn" type="submit">Send</button>
        </form>
        <?php endif; ?>
      </section>

      <!-- Right: Profile -->
      <aside class="profile" aria-label="Farmer details">
        <?php if (!$farmer): ?>
          <div>Select a farmer to view profile.</div>
        <?php else: ?>
          <img src="<?php echo htmlspecialchars(farmer_img_src($farmer['profile_picture'] ?? '')); ?>" alt="Profile">
          <div class="line title"><?php echo htmlspecialchars($farmer['farm_name'] ?: ('User #'.$selectedUID)); ?></div>
          <div class="line"><?php echo htmlspecialchars(trim(($farmer['city']?:'') . ($farmer['country']?', '.$farmer['country']:''))); ?></div>
          <div class="line">Phone: <?php echo htmlspecialchars($farmer['phone']); ?></div>
          <div class="line">Gender: <?php echo htmlspecialchars($farmer['gender']); ?></div>
          <div class="line">Farm size: <?php echo htmlspecialchars($farmer['farm_size']); ?></div>
          <div class="line">Experience: <?php echo htmlspecialchars($farmer['years_experience']); ?> yrs</div>
          <div class="line">Address: <?php echo htmlspecialchars(trim(($farmer['address_line1']??'').' '.($farmer['address_line2']??''))); ?></div>
        <?php endif; ?>
      </aside>
    </div>
  </main>
</div>
</body>
</html>
