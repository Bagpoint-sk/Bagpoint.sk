<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "bagpoint.sk");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Chyba pripojenia: " . $conn->connect_error]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$name = $conn->real_escape_string($data['name'] ?? '');
$email = $conn->real_escape_string($data['email'] ?? '');
$password = $conn->real_escape_string($data['password'] ?? '');

if (!$name || !$email || !$password) {
    echo json_encode(["success" => false, "message" => "Vyplňte všetky polia"]);
    exit;
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

// OVERENIE, ČI EMAIL UŽ EXISTUJE
$checkEmail = $conn->query("SELECT user_id FROM users WHERE email='$email' LIMIT 1");
if ($checkEmail && $checkEmail->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Tento email už je registrovaný"]);
    exit;
}

$sql = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$password_hash')";

if ($conn->query($sql) === TRUE) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Chyba: " . $conn->error]);
}

$conn->close();
exit;
?>
