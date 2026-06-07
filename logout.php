<?php
// ============================================================
// FILE: logout.php  (ROOT: foodflow/logout.php)
// PURPOSE: Destroy session and redirect to login
// ============================================================
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    logActivity($pdo, $_SESSION['user_id'], 'Logged out');
}
session_destroy();
header('Location: login.php?msg=logged_out');
exit;
?>