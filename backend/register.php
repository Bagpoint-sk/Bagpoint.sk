<?php
header('Content-Type: application/json');

require_once 'db_connect.php';

$data = json_decode(file_get_contents("php://input"), true);
$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$password = trim($data['password'] ?? '');

if (!$name || !$email || !$password) {
    echo json_encode(["success" => false, "message" => "Vyplňte všetky polia"]);
    exit;
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

// OVERENIE, ČI EMAIL UŽ EXISTUJE
try {
    $checkEmail = $conn->prepare("SELECT user_id FROM users WHERE email= :email LIMIT 1");
    $checkEmail->bindParam(':email', $email, PDO::PARAM_STR);
    $checkEmail->execute();

    if($checkEmail->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(["success" => false, "message" => "Tento email je už registrovaný!"]);
        exit;
    }

$statement = $conn->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
$statement->bindParam(':name', $name, PDO::PARAM_STR);
$statement->bindParam(':email', $email, PDO::PARAM_STR);
$statement->bindParam(':password', $password_hash, PDO::PARAM_STR);
$statement->execute();

echo json_encode(["success" => true]);
} catch (PDOException $error) {
    echo json_encode(["success" => false, "message" => "Chyba: " . $error->getMessage()]);
}
?>
