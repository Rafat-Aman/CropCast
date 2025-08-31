<?php
/**
 * feedback.php — messaging-style page using mysqli (consistent with main.php)
 * - Reads userID from session only
 * - Uses $conn from ../../main.php
 */
session_start();
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../../main.php'; // provides $conn (mysqli)

// ---- Session guard ----
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "<!doctype html><html><body><p>Unauthorized</p></body></html>";
    exit;
}
$userID = (int)$_SESSION['user_id'];

// ---- Ensure we have a mysqli connection ----
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo "<!doctype html><html><body><p>Database connection not configured. main.php must set $conn = new mysqli(...)</p></body></html>";
    exit;
}

$flash = null;
$flash_class = 'ok';

// Handle new message submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim((string)$_POST['message']);
    if ($message === '') {
        $flash = "Message cannot be empty.";
        $flash_class = "err";
    } else {
        if (mb_strlen($message) > 4000) {
            $message = mb_substr($message, 0, 4000);
        }
        $stmt = $conn->prepare("INSERT INTO feedback (userID, date, message, admin_reply) VALUES (?, NOW(), ?, NULL)");
        if ($stmt === false) {
            http_response_code(500);
            $flash = "Prepare failed: " . htmlspecialchars($conn->error);
            $flash_class = "err";
        } else {
            $stmt->bind_param("is", $userID, $message);
            if (!$stmt->execute()) {
                http_response_code(500);
                $flash = "Insert failed: " . htmlspecialchars($stmt->error);
                $flash_class = "err";
            } else {
                // PRG
                header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
                exit;
            }
            $stmt->close();
        }
    }
}

// Load conversation for this user
$rows = [];
$stmt = $conn->prepare("SELECT feedbackID, userID, date, message, admin_reply
                        FROM feedback
                        WHERE userID = ?
                        ORDER BY date ASC, feedbackID ASC");
if ($stmt) {
    $stmt->bind_param("i", $userID);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();
    }
    $stmt->close();
} else {
    // Surface prepare error
    $flash = "Query prepare failed: " . htmlspecialchars($conn->error);
    $flash_class = "err";
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Feedback</title>
  <link rel="stylesheet" href="feedback.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="dashboard-wrapper">
  <!-- Sidebar -->
  <aside class="sidebar">
    <h2>Menu</h2>
    <ul>
      <li><a href="/dashboard.php" id="menu-dashboard">📊 Dashboard</a></li>
      <li><a href="/profile/profile.php" id="menu-profile">👤 Profile</a></li>
      <li><a href="/fields/fields.php" id="menu-fields">🌱 Fields</a></li>
      <li><a href="/crop/crop.php" id="menu-crop">🌾 Crop</a></li>
      <li><a href="/soil/soil.php" id="menu-soil">🧪 Soil Data</a></li>
      <li><a href="/reports/reports.php" id="menu-reports">📄 Reports</a></li>
      <li><a class="active" href="/feedback/feedback.php" id="menu-feedback">💬 Feedback</a></li>
      <li><a href="/settings/settings.php" id="menu-settings">⚙️ Settings</a></li>
    </ul>
  </aside>

  <!-- Main -->
  <main class="dashboard-container">
    <div class="page-head">
      <h1>Support Chat</h1>
      <div class="actions">
        <form method="get">
          <button type="submit" class="btn secondary">🔄 Refresh</button>
        </form>
      </div>
    </div>

    <?php if ($flash): ?>
      <div class="flash <?php echo $flash_class; ?>"><?php echo htmlspecialchars($flash); ?></div>
    <?php endif; ?>

    <section class="chat-shell" aria-label="Conversation">
      <div class="chat-scroll" id="chat">
        <?php if (empty($rows)): ?>
          <div class="feedback-item">No messages yet. Start the conversation below.</div>
        <?php else: foreach ($rows as $r): ?>
            <div class="msg-row">
              <div class="bubble user">
                <?php echo nl2br(htmlspecialchars($r['message'] ?? '')); ?>
              </div>
              <div class="meta right">
                You • <?php echo date("M d, Y H:i", strtotime($r['date'])); ?>
              </div>

              <?php if (!is_null($r['admin_reply']) && $r['admin_reply'] !== ''): ?>
                <div class="bubble admin">
                  <?php echo nl2br(htmlspecialchars($r['admin_reply'])); ?>
                </div>
                <div class="meta left">
                  Admin • reply to #<?php echo (int)$r['feedbackID']; ?>
                </div>
              <?php else: ?>
                <div class="meta left">Admin • (pending reply)</div>
              <?php endif; ?>
            </div>
        <?php endforeach; endif; ?>
      </div>

      <!-- Composer -->
      <form class="composer" method="post" action="">
        <textarea name="message" maxlength="4000" placeholder="Type your message to admin..." required></textarea>
        <button type="submit" class="btn">Send</button>
      </form>
    </section>
  </main>
</div>
</body>
</html>
