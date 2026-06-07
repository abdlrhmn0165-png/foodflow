<?php
// ============================================================
// FILE: includes/db.php
// PURPOSE: PDO database connection - included by all pages
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Change to your MySQL username
define('DB_PASS', '');           // Change to your MySQL password
define('DB_NAME', 'foodflow');
define('SITE_NAME', 'FoodFlow');
define('SITE_URL', 'http://localhost/foodflow');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('<div style="font-family:sans-serif;padding:40px;background:#0a0a0a;color:#ff4d4d;text-align:center;">
        <h2>⚠ Database Connection Failed</h2>
        <p>' . htmlspecialchars($e->getMessage()) . '</p>
        <p style="color:#888">Check your credentials in <code>includes/db.php</code></p>
    </div>');
}
?>