<?php
// Load config to ensure .env variables are loaded
require_once __DIR__ . '/../includes/config.php';

// Fallback credentials must be set in .env file
// These are used if the admin_users database table doesn't exist yet
// Required: ADMIN_USERNAME and ADMIN_PASSWORD in .env

// Check if environment variables are set
if (!getenv('ADMIN_USERNAME') || !getenv('ADMIN_PASSWORD')) {
    die('Security Error: ADMIN_USERNAME and ADMIN_PASSWORD must be configured in your .env file. See .env.example for setup instructions.');
}

if (!defined('FALLBACK_USERNAME')) {
    define('FALLBACK_USERNAME', getenv('ADMIN_USERNAME'));
}
if (!defined('FALLBACK_PASSWORD')) {
    define('FALLBACK_PASSWORD', getenv('ADMIN_PASSWORD'));
}

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . admin_url('login.php'));
        exit;
    }
}

function login($username, $password) {
    try {
        $db = getDB();

        // Try to get user from database
        $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            return true;
        }

        return false;
    } catch (PDOException $e) {
        // If table doesn't exist yet, use fallback credentials
        if ($username === FALLBACK_USERNAME && $password === FALLBACK_PASSWORD) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            return true;
        }
        return false;
    }
}

function logout() {
    session_destroy();
    header('Location: ' . admin_url('login.php'));
    exit;
}
