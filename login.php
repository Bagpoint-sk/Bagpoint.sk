<?php
header('Content-Type: application/json');

// pripojenie k DB
$conn = new mysqli("sql100.infinityfree.com", "if0_40231085", "EwDhlX2gej", "if0_40231085_bagpoint_db");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Chyba pripojenia: " . $conn->connect_error]);
    exit;
}

// načítanie dát
$data = $_POST;

// získanie emailu a hesla
$email = $conn->real_escape_string($data['email'] ?? '');
$password = $data['password'] ?? '';

if (!$email || !$password) {
    echo json_encode(["success" => false, "message" => "Vyplňte všetky polia"]);
    exit;
}

// overenie používateľa podľa emailu
$result = $conn->query("SELECT * FROM users WHERE email='$email' LIMIT 1");

if ($result && $result->num_rows === 1) {
    $user = $result->fetch_assoc();
    
    // porovnanie hesla
    if (password_verify($password, $user['password'])) {
        echo json_encode(["success" => true, "message" => "Prihlásenie úspešné"]);
    } else {
        echo json_encode(["success" => false, "message" => "Nesprávne heslo"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Používateľ s týmto emailom neexistuje"]);
}

$conn->close();
?>
