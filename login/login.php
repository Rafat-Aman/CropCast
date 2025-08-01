<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../main.php';

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password =          $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        // adjust to your actual table/column names if different
        $stmt = $conn->prepare(
          "SELECT userID, name, password 
             FROM `USERS` 
            WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($userID, $name, $hashedPassword);
            $stmt->fetch();

            if (password_verify($password, $hashedPassword)) {
                $_SESSION['user_id']   = $userID;
                $_SESSION['fullname']  = $name;
                $_SESSION['email']     = $email;
                header("Location: ../Dashboard/dashboard.php");
                exit;
            } else {
                $error = 'Incorrect password.';
            }
        } else {
            $error = 'No user found with that email.';
        }
        $stmt->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <link rel="stylesheet" href="login.css">
  <script src="https://smtpjs.com/v3/smtp.js"></script>
</head>
<body>
  <div class="container">
    <h2>Login</h2>
    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form action="login.php" method="POST">
      <input 
        type="email" 
        name="email" 
        placeholder="Email" 
        value="<?= htmlspecialchars($email) ?>" 
        required 
      />
      <input 
        type="password" 
        name="password" 
        placeholder="Password" 
        required 
      />
      <button type="submit">Login</button>
    </form>

    <!-- POST‐only Sign-Up navigation -->
    <form action="../signup/signup.html" method="POST" class="signup-nav">
      <input type="hidden" name="action" value="showForm">
      <button type="submit" class="link-button">
        Don’t have an account? Sign Up
      </button>
    </form>
  </div>
</body>
</html>
