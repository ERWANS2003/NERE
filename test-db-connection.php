<?php

/**
 * Quick database connection test.
 *
 * Reads the real .env used by the application and verifies that the credentials
 * and permissions are valid, so a failure can be ruled out before blaming the app.
 *
 * Usage: php test-db-connection.php
 */

// Load the application's .env so this tests the same values Laravel will use.
$envFile = __DIR__ . DIRECTORY_SEPARATOR . '.env';

if (! file_exists($envFile)) {
    exit(".env introuvable. Lancez `cp .env.example .env` d'abord.\n");
}

foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);

    if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
        continue;
    }

    [$name, $value] = explode('=', $line, 2);
    $_ENV[trim($name)] = trim(trim($value), "\"'");
}

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$port = $_ENV['DB_PORT'] ?? '5432';
$database = $_ENV['DB_DATABASE'] ?? 'nere_mining_itsm';
$username = $_ENV['DB_USERNAME'] ?? 'postgres';
$password = $_ENV['DB_PASSWORD'] ?? '';
// Matches the `prefer` default in config/database.php: local Postgres has no TLS.
$sslmode = $_ENV['DB_SSLMODE'] ?? 'prefer';

echo "Test de connexion PostgreSQL\n";
echo "Host:     $host\n";
echo "Port:     $port\n";
echo "Database: $database\n";
echo "User:     $username\n";
echo "SSL mode: $sslmode\n\n";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$database;sslmode=$sslmode";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10,
    ]);

    echo "Connexion etablie.\n";
    echo 'Version: ' . $pdo->query('SELECT version()')->fetchColumn() . "\n\n";

    $tables = $pdo->query(
        "SELECT count(*) FROM information_schema.tables WHERE table_schema = 'public'"
    )->fetchColumn();
    echo "Tables dans le schema public: $tables\n";

    $migrations = $pdo->query(
        "SELECT count(*) FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'migrations'"
    )->fetchColumn();

    if ($migrations) {
        $applied = $pdo->query('SELECT count(*) FROM migrations')->fetchColumn();
        $pending = $tables - $applied;
        echo "Migrations appliquees: $applied\n";
        echo $pending > 0
            ? "Migrations en attente: $pending (lancez `php artisan migrate`)\n"
            : "Schema a jour.\n";
    } else {
        echo "Table `migrations` absente : lancez `php artisan migrate`.\n";
    }

    echo "\nConnexion validee.\n";
} catch (PDOException $e) {
    echo 'Echec de la connexion: ' . $e->getMessage() . "\n\n";
    echo "Depannage:\n";
    echo "1. verifier que le service PostgreSQL demarre (port $port)\n";
    echo "2. verifier DB_HOST / DB_PORT / DB_DATABASE dans .env\n";
    echo "3. verifier que la base '$database' existe\n";
    echo "4. verifier les identifiants de $username\n";
    exit(1);
}
