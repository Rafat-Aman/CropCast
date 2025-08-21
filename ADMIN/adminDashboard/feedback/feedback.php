<?php
// feedback.php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/ProjectFolder/main.php';

if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /ProjectFolder/login/login.html');
    exit;
}

$active = 'feedback';  // highlight "Feedback" in the admin sidebar

/* -------------------------
   CSRF Token for security
--------------------------*/
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

// Fetch all feedback reports
$sql = "SELECT f.feedbackID, f.userID, f.message, f.date, u.name as user_name
        FROM feedback f
        LEFT JOIN users u ON f.userID = u.userID
        WHERE f.admin_reply IS NULL ORDER BY f.date DESC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$feedbacks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle admin reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedbackID'], $_POST['admin_reply'], $_POST['csrf'])) {
    if (hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        $feedbackID = (int)$_POST['feedbackID'];
        $admin_reply = trim($_POST['admin_reply']);

        $stmt = $conn->prepare("UPDATE feedback SET admin_reply = ? WHERE feedbackID = ?");
        $stmt->bind_param('si', $admin_reply, $feedbackID);
        if ($stmt->execute()) {
            $flash = ['type' => 'ok', 'text' => 'Reply sent successfully.'];
        } else {
            $flash = ['type' => 'err', 'text' => 'Failed to send reply.'];
        }
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin • Feedback</title>
    <link rel="stylesheet" href="/ProjectFolder/admin/adminDashboard/maindash/dashboard.css" />
    <link rel="stylesheet" href="/ProjectFolder/admin/adminDashboard/farms/farms.css" />
</head>

<body>
    <div class="admin-wrapper">
        <!-- Sidebar include -->
        <?php include $_SERVER['DOCUMENT_ROOT'] . '/ProjectFolder/admin/partials/sidebar.php'; ?>

        <main class="admin-main">
            <div class="page-head">
                <h1><i class="fa-solid fa-comment-dots"></i> Feedback</h1>
            </div>

            <?php if (!empty($flash)): ?>
                <div class="flash <?= $flash['type'] === 'ok' ? 'ok' : 'err' ?>">
                    <?= htmlspecialchars($flash['text']) ?>
                </div>
            <?php endif; ?>

            <!-- Feedback List -->
            <div class="feedback-list">
                <?php if (empty($feedbacks)): ?>
                    <p>No new feedback to respond to.</p>
                <?php else: ?>
                    <?php foreach ($feedbacks as $feedback): ?>
                        <div class="feedback-item">
                            <h4>User: <?= htmlspecialchars($feedback['user_name']) ?> (ID: <?= $feedback['userID'] ?>)</h4>
                            <p><strong>Message:</strong> <?= htmlspecialchars($feedback['message']) ?></p>
                            <p><strong>Date:</strong> <?= $feedback['date'] ?></p>

                            <!-- Reply Form -->
                            <form method="POST" action="feedback.php">
                                <input type="hidden" name="feedbackID" value="<?= $feedback['feedbackID'] ?>" />
                                <input type="hidden" name="csrf" value="<?= $csrf ?>" />
                                <textarea name="admin_reply" placeholder="Write your reply here..." rows="5" required></textarea>
                                <button type="submit" class="btn btn-primary">Send Reply</button>
                            </form>
                        </div>
                        <hr />
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>

</html>