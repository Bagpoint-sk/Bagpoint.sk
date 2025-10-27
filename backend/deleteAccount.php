<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
// 1. musime skontrolovat, ci je user prihlaseny
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, "message" => "Nie si prihlásený!"]);
    exit;
}
// 2. ulozime vycucane data cez fetch do premennyyh
$input = json_decode(file_get_contents("php://input"), true); // true = asociativne pole -> lepsia praca s nim
$password = $input['password'] ?? '';
// 3. skontrolujeme, ci bolo vyplnene pole "heslo"
if (!$password) {
    echo json_encode(["success" => false, "message" => "Vyplň pole!"]); // json_encode = prevod PHP na JSON retazec, json_decode opacne na PHP pole napr.
    exit;
}
// 4. pripojime sa k DB
$conn = new mysqli("localhost", "root", "", "bagpoint.sk");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Chyba pripojenia!"]);
    exit;
}
// 5. vytiahneme ulozeny email v session a ulozime ho do premennej aby sme s nim mohli dalej pracovat
$email = $_SESSION['email'];
// 6. nacitanie  hesla
$statement =  $conn->prepare("SELECT password FROM users WHERE email=?"); // ? placeholder, nahradi sa
$statement->bind_param("s", $email); // nahradime placeholder vytiahnutym emailom "s" == string (bind_param = musime definovat typ = menej chyb)
$statement->execute(); // poslanie na databazu
$result = $statement->get_result(); // vysledok
// 7. overenie hesla hesla
if ($user = $result->fetch_assoc()) { // vytiahneme heslo z SQL dotazu co sme robili vyssie a ulozime ho do $user
    if (!password_verify($password, $user['password'])) {
        echo json_encode(["success" => false, "message" => "Staré heslo je nesprávne!"]);
        exit;
    }
}
// 9. vymazanie udajov z databazy
$delete = $conn->prepare("DELETE FROM users WHERE email=?"); // SQL dotaz 
$delete->bind_param("s", $email);
$delete->execute();

echo json_encode(["success" => true, "message" => "Účet bol vymazaný!"]);

$conn->close();

?>