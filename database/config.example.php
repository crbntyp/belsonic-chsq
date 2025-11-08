<?php
/**
 * Database Configuration Example
 *
 * Copy this file to config.php and update with your database credentials
 * DO NOT commit config.php to version control
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'shine_festivals');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_CHARSET', 'utf8mb4');

/**
 * Create database connection
 *
 * @return PDO
 */
function getDatabaseConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        die("Database connection failed. Please check your configuration.");
    }
}

/**
 * Example usage:
 *
 * require_once 'database/config.php';
 * $db = getDatabaseConnection();
 *
 * // Query example
 * $stmt = $db->prepare("SELECT * FROM festivals WHERE status = ?");
 * $stmt->execute(['upcoming']);
 * $festivals = $stmt->fetchAll();
 */
