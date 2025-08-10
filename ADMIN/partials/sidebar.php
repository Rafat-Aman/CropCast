<?php
// ------------------------------
// Absolute URL bases (browser):
// ------------------------------
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'];

// http://localhost/ProjectFolder/admin
$ADMIN_URL = $scheme . '://' . $host . '/ProjectFolder/admin';

// http://localhost/ProjectFolder/admin/adminDashboard
$ADASH_URL = $ADMIN_URL . '/adminDashboard';

// expects: $active = 'dashboard' | 'users' | 'farms' | 'settings' ...
?>
<aside class="sidebar">
  <h2>🌾 CropCast Admin</h2>
  <ul>
    <li>
      <a href="<?= $ADASH_URL ?>/maindash/dashboard.php"
         class="<?= ($active==='dashboard') ? 'active' : '' ?>">📊 Dashboard</a>
    </li>
    <li>
      <a href="<?= $ADASH_URL ?>/users/users.php"
         class="<?= ($active==='users') ? 'active' : '' ?>">👥 Users</a>
    </li>
    <li>
      <a href="<?= $ADASH_URL ?>/farms/farms.php"
         class="<?= ($active==='farms') ? 'active' : '' ?>">🌱 Farms</a>
    </li>
    <!-- Add more items as you add pages -->
    <li><a href="<?= $ADMIN_URL ?>/../logout.php">🚪 Logout</a></li>
  </ul>
</aside>
