<?php
// ============================================================
// FILE: includes/auth.php
// PURPOSE: Session helpers, login checks, role guards
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log an activity
function logActivity($pdo, $user_id, $activity) {
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, activity) VALUES (?,?)");
    $stmt->execute([$user_id, $activity]);
}

// Require admin login
function requireAdmin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header('Location: ' . SITE_URL . '/login.php?error=unauthorized');
        exit;
    }
}

// Require customer login
function requireCustomer() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
        header('Location: ' . SITE_URL . '/login.php?error=unauthorized');
        exit;
    }
}

// Require any login
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

// Check if logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isCustomer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
}

// Get unread notification count (admin)
function getUnreadNotifications($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM notifications WHERE status='unread'");
    return $stmt->fetchColumn();
}
?>