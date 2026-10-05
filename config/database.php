<?php

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$host = $_ENV['DB_HOST'] ?? '';
$port = $_ENV['DB_PORT'] ?? '3306';
$dbname = $_ENV['DB_NAME'] ?? '';
$username = $_ENV['DB_USER'] ?? '';
$password = $_ENV['DB_PASSWORD'] ?? '';

try {

    // Vercel: use CA certificate from environment variable
    if (!empty($_ENV['AIVEN_CA_CERT'])) {

        $caPath = sys_get_temp_dir() . '/aiven-ca.pem';

        file_put_contents(
            $caPath,
            str_replace('\n', "\n", $_ENV['AIVEN_CA_CERT'])
        );

    } else {

        // Local XAMPP development
        $caPath = __DIR__ . '/../ca.pem';

    }

    $dsn = "mysql:"
        . "host={$host};"
        . "port={$port};"
        . "dbname={$dbname};"
        . "charset=utf8mb4;"
        . "sslmode=verify-ca;"
        . "sslrootcert={$caPath}";

    $conn = new PDO(
        $dsn,
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

} catch (PDOException $e) {

    error_log("Database connection failed: " . $e->getMessage());

    die("Database connection failed.");

}