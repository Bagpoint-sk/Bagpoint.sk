<?php
require_once __DIR__ . '/sessionConfig.php';
error_log("🧩 DELETE SESSION: " . print_r($_SESSION, true));
error_log("🧩 DELETE COOKIE: " . print_r($_COOKIE, true));
header("Content-Type: application/json; charset=UTF-8");

// 1. Overenie loginu
if (!isset($_SESSION['user_email'])) {
    echo json_encode(['success' => false, "message" => "Nie si prihlásený!"]);
    exit;
}

// 2. nacitanie hesla
$input = json_decode(file_get_contents("php://input"), true);
$password = $input['userPassword'] ?? '';

if (!$password) {
    echo json_encode(["success" => false, "message" => "Vyplň pole s heslom!"]);
    exit;
}

// 3. Pripojenie k db
require_once 'db_connect.php';

$email = $_SESSION['user_email'];

try {
    // 4. Overenie hesla usera
    $stmt = $conn->prepare("SELECT password FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log("🧩 DEBUG DELETE - Email zo session: " . $email);
    error_log("🧩 DEBUG DELETE - Heslo z formulára: " . $password);
    error_log("🧩 DEBUG DELETE - Hash z DB: " . ($user['password'] ?? 'N/A'));

    if ($user) {
        // kontrola hesla
        if (!password_verify($password, $user['password'])) {
            echo json_encode(["success" => false, "message" => "Zadané heslo je nesprávne!"]);
            exit;
        }

        // 5. Vymazanie usera
        $delete = $conn->prepare("DELETE FROM users WHERE email = :email");
        $delete->bindParam(':email', $email, PDO::PARAM_STR);
        $delete->execute();

        // 6. Zrusenie session
        session_unset();
        session_destroy();

        echo json_encode(["success" => true, "message" => "Účet bol úspešne vymazaný."]);
    } else {
        echo json_encode(["success" => false, "message" => "Používateľ neexistuje."]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Chyba databázy: " . $e->getMessage()]);
}

?>