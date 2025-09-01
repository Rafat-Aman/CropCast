<?php
session_start();
include '../../main.php';
if (!isset($_SESSION['user_id'])) {
  header("Location: ../login/login.html");
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Settings | CropCast</title>
    <link rel="stylesheet" href="settings.css" />
</head>
<body>
<div class="settings-wrapper">
    <?php
    // Include the shared sidebar (absolute path)
    // Adjust $BASE inside the partial if your app base changes.
    include __DIR__ . '../../partials/partials.php';
  ?>

    <!-- Main Content -->
    <div class="settings-container">
        <header>
            <h1>⚙️ User Settings</h1>
            
        </header>

        <section class="settings-section">
            <form id="settingsForm">
                <div class="form-group">
                    <label for="notif">Email Notifications:</label>
                    <select id="notif">
                        <option value="on">Enabled</option>
                        <option value="off">Disabled</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="theme">Theme:</label>
                    <select id="theme">
                        <option value="light">Light</option>
                        <option value="dark">Dark</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="language">Language:</label>
                    <select id="language">
                        <option value="en">English</option>
                        <option value="bn">বাংলা</option>
                    </select>
                </div>

                <button type="submit" id="saveBtn">💾 Save Settings</button>
            </form>
        </section>
    </div>
</div>

<script src="settings.js"></script>
</body>
</html>
