<?php
$host = getenv("DB_HOST");
$dbname = getenv("DB_NAME");
$port = getenv("DB_PORT");
$user = getenv("DB_USER");
$password = getenv("DB_PASS"); // 👈 Render používa DB_PASS, nie DB_PASSWORD

$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

try {
    $conn = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $error) {
    header('Content-Type: application/json');
    echo json_encode([
        "success" => false,
        "message" => "Pripojenie zlyhalo! " . $error->getMessage()
    ]);
    exit;
}
?>