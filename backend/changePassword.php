<?php
require_once __DIR__ . '/sessionConfig.php';

header("Content-Type: application/json; charset=UTF-8");

// 1. musime skontrolovat, ci je user prihlaseny
if (!isset($_SESSION['user_email'])) {
    echo json_encode(['success' => false, "message" => "Nie si prihlásený!"]);
    exit;
}

// 2. ulozime vycucane data cez fetch do premennyyh
$input = json_decode(file_get_contents("php://input"), true); // true = asociativne pole -> lepsia praca s nim
$oldPassword = $input['oldPassword'] ?? '';
$newPassword = $input['newPassword'] ?? '';

// 3. skontrolujeme, ci boli vyplnene polia vo formulari
if (!$oldPassword || !$newPassword) {
    echo json_encode(["success" => false, "message" => "Vyplň všetky polia!"]); // json_encode = prevod PHP na JSON retazec, json_decode opacne na PHP pole napr.
    exit;
}

// 4. pripojime sa k DB
require_once 'db_connect.php'; 

// 5. vytiahneme ulozeny email v session a ulozime ho do premennej aby sme s nim mohli dalej pracovat
$email = $_SESSION['user_email'];

try {
    // 6. nacitanie stareho hesla
    $stmt = $conn->prepare("SELECT password FROM users WHERE email = :email"); // :email placeholder, nahradi sa
    $stmt->bindParam(':email', $email, PDO::PARAM_STR); // nahradime placeholder vytiahnutym emailom
    $stmt->execute(); // poslanie na databazu
    $user = $stmt->fetch(PDO::FETCH_ASSOC); // vysledok ako asociativne pole

    // 7. overenie stareho hesla
    if ($user) { // vytiahneme heslo z SQL dotazu co sme robili vyssie a ulozime ho do $user
        if (!password_verify($oldPassword, $user['password'])) {
            echo json_encode(["success" => false, "message" => "Staré heslo je nesprávne!"]);
            exit;
        }

        // 8. zmena no nove heslo
        $hnPassword = password_hash($newPassword, PASSWORD_DEFAULT); // zahashovanie hesla
        $update = $conn->prepare("UPDATE users SET password = :password WHERE email = :email"); // SQL dotaz 
        $update->bindParam(':password', $hnPassword, PDO::PARAM_STR);
        $update->bindParam(':email', $email, PDO::PARAM_STR);
        $update->execute(); // spustenie noveho dotazu = ulozi nove heslo do password

        echo json_encode(["success" => true, "message" => "Heslo bolo úspešne zmenené!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Používateľ neexistuje."]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Chyba databázy: " . $e->getMessage()]);
}

?>