<?php
// expects: $active = 'dashboard' | 'users' | 'reports' | 'settings' ...
// Absolute URL to your /admin folder (as seen in the browser)
if (!defined('ADMIN_BASE_URL')) {
  define('ADMIN_BASE_URL', '/ProjectFolder/admin');
}
?>
<aside class="sidebar">
  <h2>🌾 CropCast Admin</h2>
  <ul>
    <li><a href="<?= ADMIN_BASE_URL ?>../dashboard.php"
           class="<?= ($active==='dashboard')?'active':'' ?>">📊 Dashboard</a></li>

    <li><a href="<?= ADMIN_BASE_URL ?>/adminDashboard/users/users.php"
           class="<?= ($active==='users')?'active':'' ?>">👥 Users</a></li>

    <!-- Add more admin links here later
    <li><a href="<?= ADMIN_BASE_URL ?>/reports/reports.php"
           class="<?= ($active==='reports')?'active':'' ?>">📄 Reports</a></li>
    <li><a href="<?= ADMIN_BASE_URL ?>/settings/settings.php"
           class="<?= ($active==='settings')?'active':'' ?>">⚙️ Settings</a></li>
    -->

    <li><a href="../ProjectFolder/logout.php">🚪 Logout</a></li>
  </ul>
</aside>

<!-- Keep or remove this style block. If you prefer, move it into dashboard.css -->
<style>
  .sidebar{width:220px;background:#2e3b4e;color:#fff;padding:20px;min-height:100vh}
  .sidebar h2{margin:0 0 20px;font-size:1.5rem;color:#a3d9a5}
  .sidebar ul{list-style:none;margin:0;padding:0}
  .sidebar li{margin-bottom:15px}
  .sidebar a{color:#fff;text-decoration:none;font-size:1rem;display:block;padding:10px;border-radius:8px;transition:background .3s}
  .sidebar a:hover,.sidebar a.active{background:#4e5d6c}
</style>

<!--
/* Base layout preserved from your sidebar */
body{margin:0;font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:#f3f5f9;color:#0f172a}
.dashboard-wrapper{display:flex;min-height:100vh}
.sidebar{width:220px;background:#2e3b4e;color:#fff;padding:20px}
.sidebar h2{margin:0 0 20px;font-size:1.4rem;color:#a3d9a5}
.sidebar ul{list-style:none;margin:0;padding:0}
.sidebar li{margin-bottom:12px}
.sidebar a{color:#fff;text-decoration:none;display:block;padding:10px;border-radius:8px}
.sidebar a:hover,.sidebar a.active{background:#4e5d6c}
-->