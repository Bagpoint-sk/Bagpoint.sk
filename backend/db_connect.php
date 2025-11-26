<?php
$host = getenv("DB_HOST"); // nacitanie z enviromentalnych premennych = bezpecnejsie
$dbname = getenv("DB_NAME");
$port = getenv("DB_PORT");
$user = getenv("DB_USER");
$password = getenv("DB_PASS");

$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

try {
    $conn = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $error) {
    error_log("Database connection error: " . $error->getMessage()); // skrytie detailov chyby
    header('Content-Type: application/json');
    echo json_encode([
        "success" => false,
        "message" => "Pripojenie k databáze zlyhalo!"
    ]);
    exit;
}
