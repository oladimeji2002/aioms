<?php
// require_once __DIR__ . '/../vendor/autoload.php';

// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
// $dotenv->load();


// $host = "localhost";
// $dbname = "aioims";
// $username = "root";
// $password = "";

// try {
//     $conn = new PDO(
//         "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
//         $username,
//         $password
//     );

//     $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//     $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// } catch (PDOException $e) {
//     die("Database connection failed: " . $e->getMessage());
// }
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$host = $_ENV['DB_HOST'] ?? '';
$port = $_ENV['DB_PORT'] ?? '3306';
$dbname = $_ENV['DB_NAME'] ?? '';
$username = $_ENV['DB_USER'] ?? '';
$password = $_ENV['DB_PASSWORD'] ?? '';

$caPath = __DIR__ . '/../ca.pem';

try {
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