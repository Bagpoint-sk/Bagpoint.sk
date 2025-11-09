<?php

header('Content-Type: application/json');

require_once 'db_connect.php';

$data = json_decode(file_get_contents("php://input"), true);
$email = trim($data['email'] ?? '');
$password = trim($data['password'] ?? '');

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => "Vyplňte všetky polia!"]);
    exit;
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

try {

    $checkMail = $conn->prepare("SELECT user_id FROM users WHERE email= :email");
    $checkMail->bindParam(':email', $email, PDO::PARAM_STR);
    $checkMail->execute();

    $user = $checkMail->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        echo json_encode(['success' => false, 'message' => "Používateľ s týmto emailom neexistuje."]);
        exit;
    }

    $update = $conn->prepare("UPDATE users SET password = :password WHERE email = :email");
    $update->bindParam(':password', $password_hash, PDO::PARAM_STR);
    $update->bindParam(':email', $email, PDO::PARAM_STR);

    if ($update->execute()) {
        // zmazanie kodov
        $deleteOld = $conn->prepare("DELETE FROM reset_codes WHERE email = :email");
        $deleteOld->bindParam(':email', $email, PDO::PARAM_STR);
        $deleteOld->execute();

        echo json_encode(['success' => true, 'message' => "Heslo bolo úspešne zmenené!"]);
    } else {
        echo json_encode(['success' => false, 'message' => "Chyba pri ukladaní hesla!"]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => "Chyba databázy: " . $e->getMessage()]);
}

?>