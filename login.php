<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "bagpoint.sk");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Chyba pripojenia: " . $conn->connect_error]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$email = $conn->real_escape_string($data['email'] ?? '');
$password = $conn->real_escape_string($data['password'] ?? '');

if (!$email || !$password) {
    echo json_encode(["success" => false, "message" => "Vyplňte všetky polia"]);
    exit;
}

// OVERENIE, ČI EMAIL UŽ EXISTUJE
$result = $conn->query("SELECT user_id, name, password FROM users WHERE email='$email' LIMIT 1");

if ($result && $result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if (password_verify($password, $user['password'])) {
        echo json_encode([
            "success" => true,
            "message" => "Prihlásenie úspešné",
            "user" => [
                "id" => $user['user_id'],
                "name" => $user['name'],
                "email" => $email
            ]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Nesprávne heslo"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Používateľ neexistuje"]);
}



$conn->close();
exit;
