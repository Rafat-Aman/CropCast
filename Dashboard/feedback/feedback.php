<?php
// Start session at the top of the file
session_start();

// Include database connection
include('../../main.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get the logged-in user's ID from the session
$userID = $_SESSION['user_id'];

// Flash message variable
$flash_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize the input to prevent SQL injection
    $message = htmlspecialchars($_POST['message']);
    
    // Insert feedback into the database
    $stmt = $conn->prepare("INSERT INTO feedback (userID, message) VALUES (?, ?)");
    $stmt->bind_param("is", $userID, $message);
    
    // Check if the query was successful
    if ($stmt->execute()) {
        $flash_message = "<div class='flash ok'>Feedback submitted successfully!</div>";
    } else {
        $flash_message = "<div class='flash err'>Error submitting feedback.</div>";
    }
}

// Fetch existing feedback from the database
$sql = "SELECT * FROM feedback";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback</title>
    <link rel="stylesheet" href="feedback.css">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <h2>🌾 CropCast</h2>
        <ul>
            <li><a href="dashboard.php" id="menu-dashboard">📊 Dashboard</a></li>
            <li><a href="profile/profile.php" id="menu-profile">👤 Profile</a></li>
            <li><a href="fields/fields.php" id="menu-fields">🌱 Fields</a></li>
            <li><a href="weather/weather.php" id="menu-weather">☁️ Weather</a></li>
            <li><a href="soil/soil.php" id="menu-soil">🧪 Soil Data</a></li>
            <li><a href="reports/reports.php" id="menu-reports">📄 Reports</a></li>
            <li><a href="settings/settings.php" id="menu-settings">⚙️ Settings</a></li>
            <li><a href="feedback.php" id="feedback-link" class="active">💬 Feedback</a></li>
            <li><a href="../logout.php" id="logout-link">🚪 Logout</a></li>
        </ul>
    </aside>

    <div class="dashboard-wrapper">
        <div class="dashboard-container">
            <div class="page-head">
                <h1>Feedback</h1>
            </div>

            <!-- Flash message -->
            <?php echo $flash_message; ?>

            <!-- Feedback List -->
            <div class="feedback-list">
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<div class='feedback-item'>
                                <h4>User ID: " . $row['userID'] . "</h4>
                                <p>" . $row['message'] . "</p>
                              </div>";
                    }
                } else {
                    echo "<p>No feedback yet.</p>";
                }
                ?>
            </div>

            <!-- Feedback Form -->
            <form method="POST" action="feedback.php">
                <textarea name="message" rows="5" placeholder="Write your feedback here..." required></textarea>
                <br>
                <button type="submit">Submit Feedback</button>
            </form>
        </div>
    </div>
</body>
</html>
