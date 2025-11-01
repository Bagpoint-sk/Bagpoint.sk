<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "bagpoint.sk");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => "Chyba pripojenia!"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$email = trim($data['email'] ?? '');
$password = trim($data['password'] ?? '');

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => "Vyplňte všetky polia!"]);
    exit;
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

$checkMail = $conn->prepare("SELECT user_id FROM users WHERE email=?");
$checkMail->bind_param("s", $email);
$checkMail->execute();
$result = $checkMail->get_result();

$update = $conn->prepare("UPDATE users SET password=? WHERE email=?");
$update->bind_param("ss", $password_hash, $email);

if ($update->execute()) {
    $deleteOld = $conn->prepare("DELETE from reset_codes WHERE email=?");
    $deleteOld->bind_param("s", $email);
    $deleteOld->execute();

    echo json_encode(['success' => true, 'message' => "Heslo bolo úspešne zmenené!"]);
} else {
    echo json_encode(['success' => false, 'message' => "Chyba pri ukladaní hesla!"]);
}

$conn->close();

?>