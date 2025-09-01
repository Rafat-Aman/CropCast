<?php
/**
 * feedback.php — User messaging page (mysqli)
 * - Reads userID from session only
 * - Uses $conn from ../../main.php
 * - Persists messages in `message` table: (messageID, SID, RID, timestamp, text, unread_count INT)
 * - User -> Admin inserts set unread_count = 1 (admin side will zero it when reading)
 */
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

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo "<!doctype html><html><body><p>Database connection not configured. main.php must set \$conn = new mysqli(...)</p></body></html>";
    exit;
}

/* =========================
   Config (easy to change)
   ========================= */
$ADMIN_ID = 17; // <- your admin's userID; change here if needed

$flash = null;
$flash_class = 'ok';

/* ==============================================================
   Handle new message submit
   - Validates text
   - Inserts one row into `message` with:
       SID = $userID (sender is the current user)
       RID = $ADMIN_ID (receiver is admin)
       text = message body
       unread_count = 1 (admin hasn't read this yet)
   - Uses PRG pattern to avoid form resubmission
   ============================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim((string)$_POST['message']);

    if ($message === '') {
        $flash = "Message cannot be empty.";
        $flash_class = "err";
    } else {
        if (mb_strlen($message) > 4000) {
            $message = mb_substr($message, 0, 4000);
        }

        $stmt = $conn->prepare("INSERT INTO message (SID, RID, text, unread_count) VALUES (?, ?, ?, 1)");
        if ($stmt === false) {
            http_response_code(500);
            $flash = "Prepare failed: " . htmlspecialchars($conn->error);
            $flash_class = "err";
        } else {
            $stmt->bind_param("iis", $userID, $ADMIN_ID, $message);
            if (!$stmt->execute()) {
                http_response_code(500);
                $flash = "Insert failed: " . htmlspecialchars($stmt->error);
                $flash_class = "err";
            } else {
                // PRG: reload without POST
                header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
                exit;
            }
            $stmt->close();
        }
    }
}

/* ==============================================================
   Load conversation between this user and admin
   - Any direction:
       (SID = user AND RID = admin) OR (SID = admin AND RID = user)
   - Sorted oldest -> newest
   ============================================================== */
$rows = [];
$stmt = $conn->prepare(
    "SELECT messageID, SID, RID, timestamp, text
     FROM message
     WHERE (SID = ? AND RID = ?) OR (SID = ? AND RID = ?)
     ORDER BY timestamp ASC, messageID ASC"
);
if ($stmt) {
    $stmt->bind_param("iiii", $userID, $ADMIN_ID, $ADMIN_ID, $userID);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();
    }
    $stmt->close();
} else {
    $flash = "Query prepare failed: " . htmlspecialchars($conn->error);
    $flash_class = "err";
}

?>
<!doctype html>
<html lang="en">
<head>
  <!-- Head: Title + Styles -->
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Feedback</title>
  <link rel="stylesheet" href="feedback.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="dashboard-wrapper">
  <!-- =========================
       Sidebar (static nav)
       ========================= -->
  <aside class="sidebar">
    <h2>Menu</h2>
    <ul>
      <li><a href="../dashboard.php" id="menu-dashboard">📊 Dashboard</a></li>
      <li><a href="../profile/profile.php" id="menu-profile">👤 Profile</a></li>
      <li><a href="../fields/fields.php" id="menu-fields">🌱 Fields</a></li>
      <li><a href="../crop/crop.php" id="menu-crop">🌾 Crop</a></li>
      <li><a href="../soil/soil.php" id="menu-soil">🧪 Soil Data</a></li>
      <li><a href="../reports/reports.php" id="menu-reports">📄 Reports</a></li>
      <li><a class="active" href="../feedback/feedback.php" id="menu-feedback">💬 Feedback</a></li>
      <li><a href="../settings/settings.php" id="menu-settings">⚙️ Settings</a></li>
    </ul>
  </aside>

  <!-- =========================
       Main content area
       ========================= -->
  <main class="dashboard-container">
    <!-- Page header: title + refresh -->
    <div class="page-head">
      <h1>Support Chat</h1>
      <div class="actions">
        <form method="get">
          <button type="submit" class="btn secondary">🔄 Refresh</button>
        </form>
      </div>
    </div>

    <?php
    /* ============================
       Flash message (success/error)
       ============================ */
    ?>
    <?php if ($flash): ?>
      <div class="flash <?php echo $flash_class; ?>">
        <?php echo htmlspecialchars($flash); ?>
      </div>
    <?php endif; ?>

    <?php
    /* =======================================
       Chat thread: user <-> admin
       - $rows loaded from `message`
       - Align bubbles based on sender:
           * if SID == $userID   -> "You" bubble (right)
           * if SID == $ADMIN_ID -> "Admin" bubble (left)
       ======================================= */
    ?>
    <section class="chat-shell" aria-label="Conversation">
      <div class="chat-scroll" id="chat">

        <?php if (empty($rows)): ?>
          <!-- Empty state when no messages exist yet -->
          <div class="feedback-item">No messages yet. Start the conversation below.</div>

        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <?php
              // Determine who sent this message and pick bubble/meta alignment
              $isYou = ((int)$r['SID'] === $userID);
              $bubbleClass = $isYou ? 'user' : 'admin';
              $metaClass   = $isYou ? 'right' : 'left';
              $whoLabel    = $isYou ? 'You' : 'Admin';
              $when        = date("M d, Y H:i", strtotime($r['timestamp']));
              $text        = (string)($r['text'] ?? '');
            ?>
            <div class="msg-row">
              <!-- Message bubble -->
              <div class="bubble <?php echo $bubbleClass; ?>">
                <?php echo nl2br(htmlspecialchars($text)); ?>
              </div>

              <!-- Meta line under the bubble (sender label + timestamp) -->
              <div class="meta <?php echo $metaClass; ?>">
                <?php echo $whoLabel; ?> • <?php echo $when; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

      </div>

      <!-- Composer stays INSIDE the chat shell (grid row 2) -->
      <form class="composer" method="post" action="">
        <textarea name="message" maxlength="4000" placeholder="Type your message to admin..." required></textarea>
        <button type="submit" class="btn">Send</button>
      </form>
    </section>
  </main>
</div>
</body>
</html>
