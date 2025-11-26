<?php
require_once __DIR__ . '/sessionConfig.php';
header('Content-Type: application/json');

require_once 'db_connect.php';

$data = json_decode(file_get_contents("php://input"), true);
$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (!$name || !$email || !$password) {
    echo json_encode(["success" => false, "message" => "Vyplňte všetky polia!"]);
    exit;
}

$errors = [];
// validacia mena
if (strlen($name) < 2 || strlen($name) > 30) {
    $errors[] = "Meno musí obsahovať 2 - 30 znakov!";
} elseif (!preg_match('/^[\p{L}\s]+$/u', $name)) {
    $errors[] = "Meno môže obsahovať iba písmená!";
}
// validacia emailu
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Neplatná emailová adresa!";
} elseif (strlen($email) > 100) {
    $errors[] = "Email je príliš dlhý!";
}
// validacia hesla
if (strlen($password) < 8 || strlen($password) > 30) {
    $errors[] = "Heslo musí obsahovať 8 - 30 znakov!";
} elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $password)) {
    $errors[] = "Heslo musí obsahovať veľké, malé písmeno a číslicu!";
}
// vratenie chybovych hlaseni
if (!empty($errors)) {
    echo json_encode(["success" => false, "message" => implode(". ", $errors)]);
    exit;
}


$password_hash = password_hash($password, PASSWORD_DEFAULT);

// overenie, ci zadany email uz existuje
try {
    $checkEmail = $conn->prepare("SELECT user_id FROM users WHERE email= :email LIMIT 1");
    $checkEmail->bindParam(':email', $email, PDO::PARAM_STR);
    $checkEmail->execute();

    if ($checkEmail->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(["success" => false, "message" => "Tento email je už registrovaný!"]);
        exit;
    }

    $statement = $conn->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
    $statement->bindParam(':name', $name, PDO::PARAM_STR);
    $statement->bindParam(':email', $email, PDO::PARAM_STR);
    $statement->bindParam(':password', $password_hash, PDO::PARAM_STR);
    $statement->execute();

    echo json_encode(["success" => true]);
} catch (PDOException $error) {
    error_log("Registration error: " . $error->getMessage());
    echo json_encode(["success" => false, "message" => "Chyba pri registrácii"]);
}
