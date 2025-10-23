<?php
// Pripojenie k databáze
$conn = new mysqli("sql100.infinityfree.com", "if0_40231085", "EwDhlX2gej", "if0_40231085_bagpoint_db");

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Chyba pripojenia: " . $conn->connect_error]);
    exit;
}

// Načítanie dát z JSON tela požiadavky
$data = json_decode(file_get_contents("php://input"), true);

$name = $conn->real_escape_string($data['name'] ?? '');
$email = $conn->real_escape_string($data['email'] ?? '');
$password = $conn->real_escape_string($data['password'] ?? '');

if (!$name || !$email || !$password) {
    echo json_encode(["success" => false, "message" => "Vyplňte všetky polia"]);
    exit;
}

// Heslo zašifruj
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Overenie, či email už existuje
$checkEmail = $conn->query("SELECT user_id FROM users WHERE email='$email' LIMIT 1");
if ($checkEmail && $checkEmail->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Tento email už je registrovaný"]);
    exit;
}

// Vloženie používateľa
$sql = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$password_hash')";

if ($conn->query($sql) === TRUE) {
    echo json_encode(["success" => true, "message" => "Registrácia úspešná"]);
} else {
    echo json_encode(["success" => false, "message" => "Chyba databázy: " . $conn->error]);
}

$conn->close();
?>
