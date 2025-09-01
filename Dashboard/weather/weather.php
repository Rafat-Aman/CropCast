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
    <title>Weather | CropCast</title>
    <link rel="stylesheet" href="weather.css" />
</head>
<body>
<div class="weather-wrapper">
   <?php
    // Include the shared sidebar (absolute path)
    // Adjust $BASE inside the partial if your app base changes.
    include __DIR__ . '../../partials/partials.php';
  ?>

    <!-- Main Content -->
    <div class="weather-container">
        <header>
            <h1>☁️ Weather Forecast</h1>
            
        </header>

        <section class="weather-section">
            <div class="location-selector">
                <label for="location">Select Location:</label>
                <select id="location">
                    <option value="dhaka">Dhaka</option>
                    <option value="rajshahi">Rajshahi</option>
                    <option value="sylhet">Sylhet</option>
                </select>
            </div>

            <div class="weather-data" id="weatherOutput">
                <p>Loading weather...</p>
            </div>
        </section>
    </div>
</div>

<script src="weather.js"></script>
</body>
</html>
