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
    <title>Reports | CropCast</title>
    <link rel="stylesheet" href="reports.css" />
</head>
<body>
<div class="reports-wrapper">
    <?php
    // Include the shared sidebar (absolute path)
    // Adjust $BASE inside the partial if your app base changes.
    include __DIR__ . '../../partials/partials.php';
  ?>


    <!-- Main Content -->
    <div class="reports-container">
        <header>
            <h1>📄 Field Reports</h1>
            
        </header>

        <section class="reports-section">
            <h2>Recent Reports</h2>
            <ul id="reportList">
                <li>Loading reports...</li>
            </ul>
            <button id="downloadBtn">📥 Download All Reports (PDF)</button>
        </section>
    </div>
</div>

<script src="reports.js"></script>
</body>
</html>
