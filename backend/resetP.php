<?php
header('Content-Type: application/json');

require_once 'db_connect.php';

$data = json_decode(file_get_contents("php://input"), true);
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => "Vyplňte všetky polia!"]);
    exit;
}

// validacia hesla
if (strlen($password) < 8 || strlen($password) > 30) {
    echo json_encode(['success' => false, 'message' => "Heslo musí obsahovať 8 - 30 znakov!"]);
    exit;
} elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $password)) {
    echo json_encode(['success' => false, 'message' => "Heslo musí obsahovať veľké, malé písmeno a číslicu!"]);
    exit;
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

try {

    $checkMail = $conn->prepare("SELECT user_id FROM users WHERE email= :email");
    $checkMail->bindParam(':email', $email, PDO::PARAM_STR);
    $checkMail->execute();

    $user = $checkMail->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        echo json_encode(['success' => false, 'message' => "Používateľ s týmto emailom neexistuje!"]);
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
    } 
} catch (PDOException $e) {
    error_log("Password reset error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => "Chyba pri zmene hesla!"]);
}

?>