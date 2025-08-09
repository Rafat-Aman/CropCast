<?php
session_start();
include '../../main.php';                               // from /admin/adminDashboard
$active = 'users';                                      // highlights Users
include dirname(__DIR__) . '/partials/sidebar.php';     // goes up to /admin, then /partials
?>
