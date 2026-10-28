<?php
/**
 * config/db.php
 * ------------------------------------------------------------
 * Central bootstrap: starts the session and opens the PDO
 * connection. Every page requires this file first.
 * ------------------------------------------------------------
 */

// Start the session once (used for flash messages + CSRF token).
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Database credentials (XAMPP / WAMP / LAMP defaults) -----
const DB_HOST    = '127.0.0.1';
const DB_NAME    = 'inventory_db';
const DB_USER    = 'root';
const DB_PASS    = '';
const DB_CHARSET = 'utf8mb4';

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // throw on any error
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // associative arrays
        PDO::ATTR_EMULATE_PREPARES   => false,                  // real prepared statements
    ]);
} catch (PDOException $e) {
    // Never expose raw driver details to the end user — log them instead.
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit(
        '<h1 style="font-family:system-ui,sans-serif;margin-top:15vh;text-align:center">' .
        'Service unavailable</h1>' .
        '<p style="font-family:system-ui,sans-serif;text-align:center">' .
        'Sorry, we could not connect to the inventory database.<br>' .
        'Make sure MySQL is running and <strong>inventory_db</strong> has been imported ' .
        '(see <code>inventory_db.sql</code>).</p>'
    );
}
