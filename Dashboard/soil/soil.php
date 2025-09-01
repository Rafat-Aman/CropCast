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
    <title>Soil Data | CropCast</title>
    <link rel="stylesheet" href="soil.css" />
</head>
<body>
<div class="soil-wrapper">
    <?php
    // Include the shared sidebar (absolute path)
    // Adjust $BASE inside the partial if your app base changes.
    include __DIR__ . '../../partials/partials.php';
  ?>

    <!-- Main Content -->
    <div class="soil-container">
        <header>
            <h1>🧪 Soil Conditions</h1>
            
        </header>

        <section class="soil-section">
            <div class="soil-select">
                <label for="field">Select Field:</label>
                <select id="field">
                    <option value="north">North Plot</option>
                    <option value="south">South Field</option>
                </select>
            </div>

            <div class="soil-data" id="soilOutput">
                <p>Loading soil data...</p>
            </div>
        </section>
    </div>
</div>

<script src="soil.js"></script>
</body>
</html>
