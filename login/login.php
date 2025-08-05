<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../main.php';

$error = '';
$email = '';
$role = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password =          $_POST['password'] ?? '';
    $role     = trim($_POST['role']     ?? '');

    // basic validation
    if ($email === '' || $password === '' || $role === '') {
        $error = 'Please enter email, password, and select a role.';
    } else {
        // prepare query including role field
        $stmt = $conn->prepare(
          "SELECT userID, name, password, role
           FROM `USERS`
           WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($userID, $name, $hashedPassword, $dbRole);
            $stmt->fetch();

            if (!password_verify($password, $hashedPassword)) {
                $error = 'Incorrect password.';
            } elseif ($dbRole !== $role) {
                $error = 'Role does not match. Please select the correct role.';
            } else {
                // successful login
                $_SESSION['user_id']   = $userID;
                $_SESSION['fullname']  = $name;
                $_SESSION['email']     = $email;
                $_SESSION['role']      = $role;

                // redirect based on role
                if ($role === 'admin') {
                    header("Location: ../admin/dashboard.php");
                } else {
                    header("Location: ../Dashboard/dashboard.php");
                }
                exit;
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

      <!-- Role selection buttons -->
      <div style="display: flex; justify-content: space-between; gap: 10px; margin-top: 10px;">
        <button type="submit" name="role" value="user">Login as User</button>
        <button type="submit" name="role" value="admin">Login as Admin</button>
      </div>
    </form>

    <!-- Sign-Up navigation -->
    <form action="../signup/signup.html" method="POST" class="signup-nav">
      <input type="hidden" name="action" value="showForm">
      <button type="submit" class="link-button">
        Don’t have an account? Sign Up
      </button>
    </form>
  </div>
</body>
</html>
