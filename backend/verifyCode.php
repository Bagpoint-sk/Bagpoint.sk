<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');
$code = trim($data['code'] ?? '');

if(empty($email) || empty($code)) {
    echo json_encode(['success' => false, 'message' => "Email alebo kód nebol zadaný!"]);
    exit;
}

$conn = new mysqli("localhost", "root", "", "bagpoint.sk");
if($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => "Chyba pripojenia!"]);
    exit;
}

$statement = $conn->prepare("SELECT id FROM reset_codes WHERE email=? AND code=?");
$statement->bind_param("ss", $email, $code);
$statement->execute();
$result = $statement->get_result();

if($result->num_rows > 0) {
    echo json_encode(['success' => true, 'message' => "Zadaný kód je správny!"]);
    exit;
} else {
    echo json_encode(['success' => false, 'message' => "Nesprávny kód alebo email!"]);
}

$conn->close();

?>

