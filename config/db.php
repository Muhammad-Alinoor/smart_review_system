<?php
/**
 * Database Configuration
 * PDO connection with proper error handling
 */

// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'smart_review_system');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP/WAMP password is empty

// PDO options for security and performance
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use real prepared statements
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci" // UTF-8 support
];

try {
    // Create PDO instance
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Log error and show user-friendly message
    error_log("Database Connection Error: " . $e->getMessage());
    die(json_encode([
        'success' => false,
        'message' => 'Database connection failed. Please contact administrator.'
    ]));
}

/**
 * Start session if not already started
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>