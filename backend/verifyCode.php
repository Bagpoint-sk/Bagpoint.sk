<?php

header('Content-Type: application/json');

require_once 'db_connect.php'; 

$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');
$code = trim($data['code'] ?? '');

if (empty($email) || empty($code)) {
    echo json_encode(['success' => false, 'message' => "Email alebo kód nebol zadaný!"]);
    exit;
}

try {
    // Overenie, ci uz existuje
    $stmt = $conn->prepare("SELECT id FROM reset_codes WHERE email = :email AND code = :code LIMIT 1");
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':code', $code, PDO::PARAM_STR);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo json_encode(['success' => true, 'message' => "Zadaný kód je správny!"]);
    } else {
        echo json_encode(['success' => false, 'message' => "Nesprávny kód alebo email!"]);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => "Chyba databázy: " . $e->getMessage()]);
}
?>
