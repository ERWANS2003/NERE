<?php

/**
 * Quick database connection test for Neon PostgreSQL
 * Run this locally to verify your database credentials before deploying to Vercel
 */

// Load environment variables (create a .env.local file with your Neon credentials)
if (file_exists('.env.local')) {
    $lines = file('.env.local', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '5432';
$database = $_ENV['DB_DATABASE'] ?? 'neondb';
$username = $_ENV['DB_USERNAME'] ?? '';
$password = $_ENV['DB_PASSWORD'] ?? '';

echo "Testing PostgreSQL connection to Neon...\n";
echo "Host: $host\n";
echo "Database: $database\n";
echo "Username: $username\n\n";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$database;sslmode=require";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 30,
    ]);
    
    echo "✅ Connection successful!\n";
    
    // Test basic query
    $stmt = $pdo->query("SELECT version()");
    $version = $stmt->fetchColumn();
    echo "✅ PostgreSQL version: $version\n";
    
    // Test table creation permissions
    $pdo->exec("CREATE TABLE IF NOT EXISTS connection_test (id SERIAL PRIMARY KEY, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    echo "✅ Table creation permissions: OK\n";
    
    // Clean up test table
    $pdo->exec("DROP TABLE IF EXISTS connection_test");
    echo "✅ Database connection fully validated!\n\n";
    
    echo "🚀 Your database is ready for Vercel deployment.\n";
    echo "Copy these credentials to Vercel environment variables:\n\n";
    echo "DB_CONNECTION=pgsql\n";
    echo "DB_HOST=$host\n";
    echo "DB_PORT=$port\n";
    echo "DB_DATABASE=$database\n";
    echo "DB_USERNAME=$username\n";
    echo "DB_PASSWORD=$password\n";
    echo "DB_SSLMODE=require\n";
    
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Verify your Neon database is active\n";
    echo "2. Check that credentials are correct\n";
    echo "3. Ensure your IP is not blocked (Neon allows all by default)\n";
    echo "4. Try using the pooled connection string from Neon dashboard\n";
}