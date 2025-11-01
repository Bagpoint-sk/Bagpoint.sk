<?php
session_set_cookie_params([
  'path' => '/',
  'httponly' => true,
  'samesite' => 'None',
  'secure' => false 
]); // nadstavenie cookie parametrov - univerzalne
session_start();
header("Content-Type: application/json; charset=UTF-8");

// 1. Overenie loginu
if (!isset($_SESSION['user_email'])) {
    echo json_encode(['success' => false, "message" => "Nie si prihlásený!"]);
    exit;
}

// 2. nacitanie hesla
$input = json_decode(file_get_contents("php://input"), true);
$password = $input['password'] ?? '';

if (!$password) {
    echo json_encode(["success" => false, "message" => "Vyplň pole s heslom!"]);
    exit;
}

// 3. Pripojenie k db
$conn = new mysqli("localhost", "root", "", "bagpoint.sk");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Chyba pripojenia!"]);
    exit;
}

$email = $_SESSION['user_email'];

// 4. Overenie hesla usra
$statement = $conn->prepare("SELECT password FROM users WHERE email=?");
$statement->bind_param("s", $email);
$statement->execute();
$result = $statement->get_result();

if ($user = $result->fetch_assoc()) {
    // kontrola hesla
    if (!password_verify($password, $user['password'])) {
        echo json_encode(["success" => false, "message" => "Zadané heslo je nesprávne!"]);
        exit;
    }

    // 5. Vymazanie usera
    $delete = $conn->prepare("DELETE FROM users WHERE email=?");
    $delete->bind_param("s", $email);
    $delete->execute();

    // 6. Zrusenie session
    session_unset();
    session_destroy();

    echo json_encode(["success" => true, "message" => "Účet bol úspešne vymazaný."]);
} else {
    echo json_encode(["success" => false, "message" => "Používateľ neexistuje."]);
}

$conn->close();
?>
