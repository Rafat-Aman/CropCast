<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.html");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST['fullname'];
    $email    = $_POST['email'];
    $password = $_POST['password'];
    $age      = $_POST['age'];
    $location = $_POST['location'];
    $image_path = "";

    if (!empty($_FILES['profile_image']['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir);
        $target_file = $target_dir . basename($_FILES["profile_image"]["name"]);
        move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file);
        $image_path = $target_file;
    }

    // Update or insert profile
    $check_sql = "SELECT user_id FROM profile WHERE user_id = $user_id";
    $result = $conn->query($check_sql);

    if ($result->num_rows > 0) {
        $sql = "UPDATE profile SET 
                fullname='$fullname', 
                age=$age, 
                location='$location'";
        if ($image_path) {
            $sql .= ", profile_image='$image_path'";
        }
        $sql .= " WHERE user_id=$user_id";
    } else {
        $sql = "INSERT INTO profile (user_id, fullname, age, location, profile_image) 
                VALUES ($user_id, '$fullname', $age, '$location', '$image_path')";
    }
    $conn->query($sql);

    // Update users table
    $sql = "UPDATE users SET email='$email', password='$password' WHERE ID=$user_id";
    $conn->query($sql);
}

// Load user info
$sql = "SELECT u.email, u.password, p.fullname, p.age, p.location, p.profile_image 
        FROM users u
        LEFT JOIN profile p ON u.ID = p.user_id
        WHERE u.ID = $user_id";

$result = $conn->query($sql);
$user = $result->fetch_assoc();

if ($user):
echo "<script>
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('fullname').value = '" . addslashes($user['fullname']) . "';
    document.getElementById('email').value = '" . addslashes($user['email']) . "';
    document.getElementById('password').value = '" . addslashes($user['password']) . "';
    document.getElementById('age').value = '" . (int)$user['age'] . "';
    document.getElementById('location').value = '" . addslashes($user['location']) . "';
    document.getElementById('profilePicPreview').src = '" . ($user['profile_image'] ?? 'default.png') . "';
});
</script>";
endif;
?>
