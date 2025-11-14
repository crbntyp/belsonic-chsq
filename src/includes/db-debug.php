<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Debug (from includes/)</h2><pre>";

// This mimics the EXACT logic from config.php
function loadEnv($path) {
    if (!file_exists($path)) {
        die('.env file not found at: ' . $path);
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $value = trim($value, '"\'');
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// EXACT same path logic as config.php
$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath)) {
    $envPath = __DIR__ . '/../../.env';
}

echo "__DIR__: " . __DIR__ . "\n";
echo "Trying first: " . __DIR__ . '/../.env' . " - " . (file_exists(__DIR__ . '/../.env') ? "EXISTS" : "NOT FOUND") . "\n";
echo "Trying second: " . __DIR__ . '/../../.env' . " - " . (file_exists(__DIR__ . '/../../.env') ? "EXISTS" : "NOT FOUND") . "\n";
echo "Loading .env from: $envPath\n";
echo "File exists: " . (file_exists($envPath) ? "YES" : "NO") . "\n\n";

loadEnv($envPath);

// Show first few lines of .env (without passwords)
echo "=== First 5 lines of .env file ===\n";
$lines = file($envPath, FILE_IGNORE_NEW_LINES);
for ($i = 0; $i < min(5, count($lines)); $i++) {
    $line = $lines[$i];
    if (strpos($line, 'PASS') !== false) {
        echo substr($line, 0, strpos($line, '=') + 1) . "***\n";
    } else {
        echo $line . "\n";
    }
}
echo "\n";

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

echo "=== Loaded Credentials ===\n";
echo "DB_HOST: " . ($dbHost ?: "NOT SET") . "\n";
echo "DB_NAME: " . ($dbName ?: "NOT SET") . "\n";
echo "DB_USER: " . ($dbUser ?: "NOT SET") . "\n";
echo "DB_PASS: " . ($dbPass ? "***SET*** (length: " . strlen($dbPass) . ")" : "NOT SET") . "\n\n";

if (!$dbHost || !$dbName || !$dbUser || !$dbPass) {
    echo "❌ Missing credentials! Cannot proceed.\n";
    echo "</pre>";
    exit;
}

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
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM venues");
    $count = $stmt->fetch()['count'];
    echo "✅ Venues table exists with $count records\n";

} catch (PDOException $e) {
    echo "❌ Connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
}

echo "</pre>";
?>
