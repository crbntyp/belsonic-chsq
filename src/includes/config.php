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
// Try closest location first (dist root) to avoid conflicts with parent site .env
$envPath = __DIR__ . '/../.env'; // One level up (e.g., dist root when in dist/includes/)
if (!file_exists($envPath)) {
    $envPath = __DIR__ . '/../../.env'; // Two levels up (e.g., project root)
}
loadEnv($envPath);

// Set timezone from environment
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Europe/London');

/**
 * Database Configuration
 * Localhost + carbontype.co use dev/demo database
 * All other domains use production database
 */

// Detect current domain
$currentHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isDemoEnvironment = (
    strpos($currentHost, 'localhost') !== false ||
    strpos($currentHost, '127.0.0.1') !== false ||
    strpos($currentHost, 'carbontype.co') !== false
);

// Set database credentials based on environment
if ($isDemoEnvironment) {
    // Development/Demo: localhost or carbontype.co
    $dbHost = getenv('LOCAL_DB_HOST');
    $dbName = getenv('LOCAL_DB_NAME');
    $dbUser = getenv('LOCAL_DB_USER');
    $dbPass = getenv('LOCAL_DB_PASS');

    if (!$dbHost || !$dbName || !$dbUser || !$dbPass) {
        die('<h3>Environment Configuration Error</h3><p>Missing required LOCAL_DB_* variables in .env file. Current host: ' . htmlspecialchars($currentHost) . '</p>');
    }
} else {
    // Production: Client's live site
    $dbHost = getenv('PROD_DB_HOST');
    $dbName = getenv('PROD_DB_NAME');
    $dbUser = getenv('PROD_DB_USER');
    $dbPass = getenv('PROD_DB_PASS');

    if (!$dbHost || !$dbName || !$dbUser || !$dbPass) {
        die('<h3>Environment Configuration Error</h3><p>Missing required PROD_DB_* variables in .env file. Current host: ' . htmlspecialchars($currentHost) . '</p>');
    }
}

define('DB_HOST', $dbHost);
define('DB_NAME', $dbName);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
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

    try {
        $pdo = getDB();

        // First, try with domain_aliases (newer schema)
        try {
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
        } catch (PDOException $e) {
            // domain_aliases column doesn't exist, try simpler query
            error_log("domain_aliases column not found, using simple query: " . $e->getMessage());
            $stmt = $pdo->prepare("
                SELECT id FROM venues
                WHERE domain = :exact_domain OR domain = :normalized_domain
                LIMIT 1
            ");

            $stmt->execute([
                ':exact_domain' => $requestDomain,
                ':normalized_domain' => $normalizedDomain
            ]);

            $result = $stmt->fetch();
        }

        if ($result) {
            $venueId = $result['id'];
        } else {
            // No match found, return first venue as fallback
            $stmt = $pdo->prepare("SELECT id FROM venues ORDER BY id LIMIT 1");
            $stmt->execute();
            $result = $stmt->fetch();
            $venueId = $result ? $result['id'] : 2;
            error_log("No venue match for domain '{$requestDomain}' (normalized: '{$normalizedDomain}'), defaulting to venue {$venueId}");
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
 * Returns venue based on domain matching from database
 */
function getCurrentVenueId() {
    return DEFAULT_VENUE_ID;
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
