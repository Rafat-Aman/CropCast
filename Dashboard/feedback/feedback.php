<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include '../../main.php';
$userID = (int)$_SESSION['user_id'];

// Fetch name and picture path
$stmt = $conn->prepare("
  SELECT U.name,
         F.profile_picture
    FROM USERS U
    JOIN FARMER F ON U.userID = F.userID
   WHERE U.userID = ?
");
$stmt->bind_param('i', $userID);
$stmt->execute();
$stmt->bind_result($name, $picPath);
$stmt->fetch();
$stmt->close();

// Build the URL to the uploaded picture (or a default)
if ($picPath) {
    $profilePicUrl = "profile/" . $picPath;
} else {
    $profilePicUrl = "images/default-avatar.png"; // Default profile picture if no picture is set
}

// Handle the message submission (user sending message)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'], $_POST['csrf'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        // Insert message into the feedback table
        $stmt = $conn->prepare("INSERT INTO feedback (userID, message) VALUES (?, ?)");
        $stmt->bind_param('is', $userID, $message);  // Binding 'i' for integer (userID) and 's' for string (message)
        $stmt->execute();
        $stmt->close();
        $flash = ['type' => 'ok', 'text' => 'Message sent to admin.'];
    } else {
        $flash = ['type' => 'err', 'text' => 'Please enter a message.'];
    }
}

// Fetch previous messages (user to admin and admin's replies)
$sql = "SELECT f.feedbackID, f.message, f.date, f.admin_reply
        FROM feedback f
        WHERE f.userID = ? 
        ORDER BY f.date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $userID);  // Binding 'i' for integer (userID)
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>CropCast Dashboard</title>
    <link rel="stylesheet" href="feedback.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body>
<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar">
        <h2>🌾 CropCast</h2>
        <ul>
            <li><a href="dashboard.php" id="menu-dashboard" class="active">📊 Dashboard</a></li>
            <li><a href="profile/profile.php" id="menu-profile">👤 Profile</a></li>
            <li><a href="fields/fields.php" id="menu-fields">🌱 Fields</a></li>
            <li><a href="weather/weather.php" id="menu-weather">☁️ Weather</a></li>
            <li><a href="soil/soil.php" id="menu-soil">🧪 Soil Data</a></li>
            <li><a href="reports/reports.php" id="menu-reports">📄 Reports</a></li>
            <li><a href="settings/settings.php" id="menu-settings">⚙️ Settings</a></li>
            <li><a href="../logout.php" id="logout-link">🚪 Logout</a></li>
        </ul>
    </aside>

    <!-- Main content -->
    <div class="dashboard-container">
        <header>
            <h1>CropCast Dashboard</h1>
            <div class="user-info">
                <span>Welcome, <?= htmlspecialchars($name) ?></span>
                <img src="<?= htmlspecialchars($profilePicUrl) ?>" alt="Profile Picture" class="profile-pic" />
            </div>
        </header>

        <div id="main-content">
            <section class="cards">
                <!-- Previous Feedback Messages -->
                <div class="card">
                    <h2>Message with Admin</h2>

                    <?php if (isset($flash)): ?>
                        <div class="flash <?= $flash['type'] === 'ok' ? 'ok' : 'err' ?>">
                            <?= htmlspecialchars($flash['text']) ?>
                        </div>
                    <?php endif; ?>

                    <div class="feedback-list">
                        <?php if (empty($messages)): ?>
                            <p>No messages yet.</p>
                        <?php else: ?>
                            <?php foreach ($messages as $msg): ?>
                                <div class="feedback-item">
                                    <p><strong>User Message:</strong> <?= htmlspecialchars($msg['message']) ?></p>
                                    <p><strong>Date:</strong> <?= $msg['date'] ?></p>
                                    
                                    <?php if ($msg['admin_reply']): ?>
                                        <p><strong>Admin Reply:</strong> <?= htmlspecialchars($msg['admin_reply']) ?></p>
                                    <?php else: ?>
                                        <p><em>Admin has not replied yet.</em></p>
                                    <?php endif; ?>
                                </div>
                                <hr />
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Message Submission -->
                <div class="card">
                    <h2>Send Message to Admin</h2>
                    <form method="POST" action="dashboard.php">
                        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>" />
                        <textarea name="message" placeholder="Write your message..." rows="5" required></textarea>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
            </section>
        </div>
    </div>
</div>
</body>
</html>
