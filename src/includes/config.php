<?php
// Start session first, before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Load environment variables from .env file
 */
function loadEnv($path) {
    if (!file_exists($path)) {
        die('.env file not found. Please copy .env.example to .env and configure your settings.');
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            $value = trim($value, '"\'');

            // Set as environment variable and global
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// Load .env file - try multiple locations
$envPath = __DIR__ . '/../../.env'; // Project root (e.g., when dist/includes/config.php)
if (!file_exists($envPath)) {
    $envPath = __DIR__ . '/../.env'; // Parent directory (e.g., when src/includes/config.php or dist root)
}
loadEnv($envPath);

// Set timezone from environment
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Europe/London');

/**
 * Database Configuration
 * Loaded from .env file
 */

define('DB_HOST', getenv('DB_HOST'));
define('DB_NAME', getenv('DB_NAME'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASS', getenv('DB_PASS'));
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');



/**
 * Venue Configuration
 * Dynamically detects venue based on domain from database
 */

/**
 * Normalize domain for matching
 * Strips www, extracts root domain
 *
 * Examples:
 * - www.belsonic.com → belsonic.com
 * - admin.belsonic.com → belsonic.com
 * - belsonic.com → belsonic.com
 */
function normalizeDomain($domain) {
    // Remove port if present
    $domain = preg_replace('/:\d+$/', '', $domain);

    // Handle localhost and IP addresses specially
    if ($domain === 'localhost' || preg_match('/^\d+\.\d+\.\d+\.\d+$/', $domain)) {
        return $domain;
    }

    // Remove www prefix
    $domain = preg_replace('/^www\./', '', $domain);

    // For subdomains, extract the root domain (last two parts)
    // e.g., admin.belsonic.com → belsonic.com
    $parts = explode('.', $domain);
    if (count($parts) > 2) {
        // Keep only the last two parts (domain + TLD)
        $domain = implode('.', array_slice($parts, -2));
    }

    return strtolower($domain);
}

/**
 * Get venue ID by domain from database
 * Matches against domain and domain_aliases fields
 * Caches result for current request
 */
function getVenueIdByDomain($requestDomain) {
    static $cache = [];

    // Return cached result if available
    if (isset($cache[$requestDomain])) {
        return $cache[$requestDomain];
    }

    // Normalize the request domain
    $normalizedDomain = normalizeDomain($requestDomain);

    // Special case: localhost defaults to venue ID 6 (Jonnys Venue - dont delete me)
    // BUT: If there's a test_venue_id in session (from venue switcher), use that instead
    // Matches: localhost, 127.0.0.1, localhost:8080, etc.
    if ($normalizedDomain === 'localhost' || $normalizedDomain === '127.0.0.1') {
        // Check if venue switcher has set a specific venue
        if (isset($_SESSION['test_venue_id'])) {
            $cache[$requestDomain] = $_SESSION['test_venue_id'];
            return $_SESSION['test_venue_id'];
        }
        // Otherwise, default to venue 6
        $cache[$requestDomain] = 6;
        return 6;
    }

    try {
        $pdo = getDB();

        // Query venues table for matching domain
        // Check both primary domain and aliases
        $stmt = $pdo->prepare("
            SELECT id FROM venues
            WHERE (
                domain = :exact_domain
                OR domain = :normalized_domain
                OR FIND_IN_SET(:exact_domain2, REPLACE(domain_aliases, ' ', '')) > 0
                OR FIND_IN_SET(:normalized_domain2, REPLACE(domain_aliases, ' ', '')) > 0
            )
            LIMIT 1
        ");

        $stmt->execute([
            ':exact_domain' => $requestDomain,
            ':normalized_domain' => $normalizedDomain,
            ':exact_domain2' => $requestDomain,
            ':normalized_domain2' => $normalizedDomain
        ]);

        $result = $stmt->fetch();

        if ($result) {
            $venueId = $result['id'];
        } else {
            // No match found, return first venue as fallback
            $stmt = $pdo->prepare("SELECT id FROM venues ORDER BY id LIMIT 1");
            $stmt->execute();
            $result = $stmt->fetch();
            $venueId = $result ? $result['id'] : 2;
        }

        $cache[$requestDomain] = $venueId;
        return $venueId;

    } catch (Exception $e) {
        // If DB query fails, return default
        error_log("Failed to get venue by domain: " . $e->getMessage());
        return 2;
    }
}

// Detect venue from current domain
$currentDomain = $_SERVER['HTTP_HOST'] ?? 'localhost';
$detectedVenueId = getVenueIdByDomain($currentDomain);

define('DEFAULT_VENUE_ID', $detectedVenueId);

/**
 * Get current venue ID
 * Checks session for test override, otherwise uses default
 */
function getCurrentVenueId() {
    return $_SESSION['test_venue_id'] ?? DEFAULT_VENUE_ID;
}

/**
 * Create database connection
 */
function getDB() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

        // Set MySQL timezone to Europe/London (BST/GMT) to match PHP timezone
        // Use offset as fallback if named timezone not available
        try {
            $pdo->exec("SET time_zone = 'Europe/London'");
        } catch (PDOException $e) {
            // Fallback to UTC offset (+00:00 for GMT, +01:00 for BST)
            $pdo->exec("SET time_zone = '+00:00'");
        }

        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        die("Database connection failed. Please check configuration.");
    }
}
