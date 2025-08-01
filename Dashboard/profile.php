<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}

include '../main.php';

$stmt = $conn->prepare("SELECT fullname, email FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($fullname, $email);
$stmt->fetch();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Profile</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .profile-container {
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .profile-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .profile-header {
            background-color: #218838;
            color: white;
            padding: 20px;
        }
        
        .profile-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .profile-body {
            padding: 25px;
        }
        
        .profile-field {
            display: flex;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .profile-field label {
            font-weight: bold;
            width: 150px;
            color: #333;
        }
        
        .profile-field p {
            margin: 0;
            flex: 1;
            color: #555;
        }
    </style>
</head>
<body>
<div class="dashboard-wrapper">
    <aside class="sidebar">
        <h2>Menu</h2>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="profile.php">Profile</a></li>
        </ul>
    </aside>

    <div class="dashboard-container">
        <header>
            <h1>🌾 User Profile</h1>
            <div>
                <span>Welcome, <?php echo htmlspecialchars($fullname); ?></span>
                <button id="logoutBtn">Logout</button>
            </div>
        </header>

        <div class="profile-container">
            <div class="profile-card">
                <div class="profile-header">
                    <h2>Account Information</h2>
                </div>
                <div class="profile-body">
                    <div class="profile-field">
                        <label>Full Name:</label>
                        <p><?php echo htmlspecialchars($fullname); ?></p>
                    </div>
                    <div class="profile-field">
                        <label>Email:</label>
                        <p><?php echo htmlspecialchars($email); ?></p>
                    </div>
                    <div class="profile-field">
                        <label>Account Created:</label>
                        <p><?php echo date('F j, Y'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById("logoutBtn").addEventListener("click", () => {
        window.location.href = "../../logout.php";
    });
</script>
</body>
</html>