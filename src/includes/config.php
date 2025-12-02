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
    strpos($currentHost, 'carbontype.co') !== false ||
    strpos($currentHost, 'crbntyp.com') !== false
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
 * Base Path Configuration
 * Detect if running in a subdirectory (e.g., /belsonic/)
 * Strip /admin from path if present to get application root
 */
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
// Remove /admin from path if present (we want app root, not admin subdirectory)
$scriptPath = preg_replace('#/admin$#', '', $scriptPath);
$basePath = ($scriptPath === '/' || $scriptPath === '\\') ? '' : $scriptPath;
define('BASE_PATH', $basePath);

/**
 * Helper function to create proper URLs with base path
 */
function asset_url($path) {
    // Remove leading slash if present
    $path = ltrim($path, '/');
    return BASE_PATH . '/' . $path;
}

/**
 * Helper function for admin URLs with base path
 */
function admin_url($path = '') {
    // Remove leading slash if present
    $path = ltrim($path, '/');
    return BASE_PATH . '/admin/' . $path;
}

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
 * Returns venue based on session override (for testing) or domain matching from database
 */
function getCurrentVenueId() {
    // Check for test venue override in session
    if (isset($_SESSION['test_venue_id'])) {
        return (int)$_SESSION['test_venue_id'];
    }
    return DEFAULT_VENUE_ID;
}

/**
 * Process support acts HTML to apply superior styling
 * Checks if last <p> contains only a number 1-4, uses that to determine
 * how many acts get the "superior" class. Hides the config <p> from output.
 * Backward compatible: no number = first act only is superior.
 */
function processSupportActs($html) {
    if (empty($html)) {
        return $html;
    }

    // Use DOMDocument to parse and manipulate HTML
    $dom = new DOMDocument();
    // Suppress warnings for malformed HTML, wrap in container for proper parsing
    @$dom->loadHTML('<div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    // Get all <p> elements
    $paragraphs = $dom->getElementsByTagName('p');
    $pCount = $paragraphs->length;

    if ($pCount === 0) {
        return $html;
    }

    // Check if last <p> contains only a number 1-4
    $lastP = $paragraphs->item($pCount - 1);
    $lastContent = trim($lastP->textContent);
    $superiorCount = 1; // Default: first act only
    $hideLastP = false;

    if (preg_match('/^[1-4]$/', $lastContent)) {
        $superiorCount = (int)$lastContent;
        $hideLastP = true;
    }

    // Apply superior class to first N paragraphs
    for ($i = 0; $i < min($superiorCount, $pCount - ($hideLastP ? 1 : 0)); $i++) {
        $p = $paragraphs->item($i);
        $existingClass = $p->getAttribute('class');
        $p->setAttribute('class', trim($existingClass . ' superior'));
    }

    // Hide the config <p> by adding a hidden class
    if ($hideLastP) {
        $existingClass = $lastP->getAttribute('class');
        $lastP->setAttribute('class', trim($existingClass . ' superior-config'));
    }

    // Extract the inner HTML (removing our wrapper div)
    $output = '';
    $container = $dom->getElementsByTagName('div')->item(0);
    foreach ($container->childNodes as $child) {
        $output .= $dom->saveHTML($child);
    }

    return $output;
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
