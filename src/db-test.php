<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2><pre>";

// Load environment variables
function loadEnv($path) {
    if (!file_exists($path)) {
        die('.env file not found at: ' . $path);
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . "=" . trim(trim($value), '"\''));
        }
    }
}

// Try to load .env
$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath)) {
    $envPath = __DIR__ . '/../../.env';
}

echo "Loading .env from: $envPath\n";
echo "File exists: " . (file_exists($envPath) ? "YES" : "NO") . "\n\n";

if (file_exists($envPath)) {
    loadEnv($envPath);
}

// Detect environment
$currentHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocalhost = (strpos($currentHost, 'localhost') !== false || strpos($currentHost, '127.0.0.1') !== false);

echo "Current Host: $currentHost\n";
echo "Is Localhost: " . ($isLocalhost ? "YES" : "NO") . "\n";
echo "Will use: " . ($isLocalhost ? "LOCAL_DB_*" : "PROD_DB_*") . " credentials\n\n";

// Get credentials
if ($isLocalhost) {
    $dbHost = getenv('LOCAL_DB_HOST');
    $dbName = getenv('LOCAL_DB_NAME');
    $dbUser = getenv('LOCAL_DB_USER');
    $dbPass = getenv('LOCAL_DB_PASS');
} else {
    $dbHost = getenv('PROD_DB_HOST');
    $dbName = getenv('PROD_DB_NAME');
    $dbUser = getenv('PROD_DB_USER');
    $dbPass = getenv('PROD_DB_PASS');
}

echo "=== Environment Variables ===\n";
echo "DB_HOST: " . ($dbHost ?: "NOT SET") . "\n";
echo "DB_NAME: " . ($dbName ?: "NOT SET") . "\n";
echo "DB_USER: " . ($dbUser ?: "NOT SET") . "\n";
echo "DB_PASS: " . ($dbPass ? "***SET*** (length: " . strlen($dbPass) . ")" : "NOT SET") . "\n\n";

// Test connection
echo "=== Connection Test ===\n";
try {
    $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
    echo "DSN: mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4\n\n";

    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "✅ Database connected successfully!\n\n";

    // Test query
    $stmt = $pdo->query("SELECT DATABASE() as db, USER() as user, VERSION() as version");
    $result = $stmt->fetch();

    echo "Connected to database: " . $result['db'] . "\n";
    echo "Connected as user: " . $result['user'] . "\n";
    echo "MySQL version: " . $result['version'] . "\n\n";

    // Check venues table
    echo "=== Venues Table Check ===\n";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM venues");
        $count = $stmt->fetch()['count'];
        echo "✅ Venues table exists with $count records\n";
    } catch (PDOException $e) {
        echo "❌ Venues table error: " . $e->getMessage() . "\n";
    }

} catch (PDOException $e) {
    echo "❌ Connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
}

echo "</pre>";
?>
