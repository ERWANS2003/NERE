<?php

/**
 * Deployment Health Check Script for Vercel
 * This script verifies that all components are working correctly
 * Access this at: https://your-app.vercel.app/deployment-check.php
 */

header('Content-Type: text/plain; charset=utf-8');

echo "🔍 VERCEL DEPLOYMENT HEALTH CHECK\n";
echo "==================================\n\n";

$status = [];

// 1. PHP Version Check
echo "1. PHP Environment:\n";
echo "   PHP Version: " . PHP_VERSION . "\n";
echo "   SAPI: " . php_sapi_name() . "\n";
$status['php'] = version_compare(PHP_VERSION, '8.3.0', '>=');
echo "   Status: " . ($status['php'] ? "✅ OK" : "❌ FAIL") . "\n\n";

// 2. Required Extensions
echo "2. PHP Extensions:\n";
$required_extensions = ['pdo', 'pdo_pgsql', 'openssl', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath'];
$status['extensions'] = true;

foreach ($required_extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo "   $ext: " . ($loaded ? "✅" : "❌") . "\n";
    if (!$loaded) $status['extensions'] = false;
}
echo "\n";

// 3. Environment Variables
echo "3. Environment Configuration:\n";
$required_env = ['APP_KEY', 'DB_HOST', 'DB_USERNAME', 'DB_PASSWORD', 'DB_DATABASE'];
$status['env'] = true;

foreach ($required_env as $var) {
    $value = getenv($var) ?: $_ENV[$var] ?? null;
    $exists = !empty($value);
    echo "   $var: " . ($exists ? "✅ Set" : "❌ Missing") . "\n";
    if (!$exists) $status['env'] = false;
}
echo "\n";

// 4. Directory Permissions
echo "4. Directory Access:\n";
$directories = ['/tmp', '/tmp/sessions', '/tmp/cache'];
$status['directories'] = true;

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $writable = is_writable($dir);
    echo "   $dir: " . ($writable ? "✅ Writable" : "❌ Not writable") . "\n";
    if (!$writable) $status['directories'] = false;
}
echo "\n";

// 5. Database Connection Test
echo "5. Database Connection:\n";
$status['database'] = false;

try {
    $host = getenv('DB_HOST');
    $port = getenv('DB_PORT') ?: '5432';
    $database = getenv('DB_DATABASE');
    $username = getenv('DB_USERNAME');
    $password = getenv('DB_PASSWORD');
    
    if ($host && $username && $password && $database) {
        $dsn = "pgsql:host=$host;port=$port;dbname=$database;sslmode=require";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10,
        ]);
        
        // Test basic query
        $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public'");
        $table_count = $stmt->fetchColumn();
        
        echo "   Connection: ✅ Success\n";
        echo "   Tables: $table_count found\n";
        $status['database'] = true;
    } else {
        echo "   Connection: ❌ Missing database credentials\n";
    }
} catch (PDOException $e) {
    echo "   Connection: ❌ Failed - " . $e->getMessage() . "\n";
}
echo "\n";

// 6. Laravel Bootstrap Test
echo "6. Laravel Framework:\n";
$status['laravel'] = false;

try {
    // Check if Laravel files exist
    $bootstrap_exists = file_exists(__DIR__ . '/bootstrap/app.php');
    $vendor_exists = file_exists(__DIR__ . '/vendor/autoload.php');
    
    echo "   Bootstrap file: " . ($bootstrap_exists ? "✅ Found" : "❌ Missing") . "\n";
    echo "   Vendor autoload: " . ($vendor_exists ? "✅ Found" : "❌ Missing") . "\n";
    
    if ($bootstrap_exists && $vendor_exists) {
        require_once __DIR__ . '/vendor/autoload.php';
        $app = require_once __DIR__ . '/bootstrap/app.php';
        echo "   Laravel app: ✅ Loaded successfully\n";
        $status['laravel'] = true;
    }
} catch (Exception $e) {
    echo "   Laravel app: ❌ Failed to load - " . $e->getMessage() . "\n";
}
echo "\n";

// Overall Status
echo "🏁 OVERALL STATUS:\n";
echo "==================\n";
$overall = array_reduce($status, function($carry, $item) {
    return $carry && $item;
}, true);

echo "Overall Health: " . ($overall ? "✅ HEALTHY" : "❌ ISSUES DETECTED") . "\n\n";

if (!$overall) {
    echo "❗ Issues found. Please check:\n";
    echo "1. Vercel environment variables are set correctly\n";
    echo "2. Database connection credentials are valid\n";
    echo "3. All required PHP extensions are available\n";
    echo "4. Laravel files were deployed properly\n\n";
}

echo "📊 Component Status:\n";
foreach ($status as $component => $ok) {
    echo "   " . ucfirst($component) . ": " . ($ok ? "✅ OK" : "❌ FAIL") . "\n";
}

echo "\n🕐 Check completed at: " . date('Y-m-d H:i:s T') . "\n";
echo "🌐 Server: " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\n";
echo "📍 Region: " . ($_SERVER['VERCEL_REGION'] ?? 'unknown') . "\n";