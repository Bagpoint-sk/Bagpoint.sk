<?php
session_set_cookie_params([
  'path' => '/',
  'httponly' => true,
  'samesite' => 'None',
  'secure' => false // na localhoste false, na HTTPS hostingu true
]);
session_start();
header("Content-Type: application/json; charset=UTF-8");
// 1. musime skontrolovat, ci je user prihlaseny
if(!isset($_SESSION['user_email'])) {
    echo json_encode(['success' => false, "message" => "Nie si prihlásený!"]);
    exit;
}
// 2. ulozime vycucane data cez fetch do premennyyh
$input = json_decode(file_get_contents("php://input"), true); // true = asociativne pole -> lepsia praca s nim
$oldPassword = $input['oldPassword'] ?? '';
$newPassword = $input['newPassword'] ?? '';
// 3. skontrolujeme, ci boli vyplnene polia vo formulari
if(!$oldPassword || !$newPassword) {
    echo json_encode(["success" => false, "message" => "Vyplň všetky polia!"]); // json_encode = prevod PHP na JSON retazec, json_decode opacne na PHP pole napr.
    exit;
}
// 4. pripojime sa k DB
$conn = new mysqli("localhost", "root", "", "bagpoint.sk");
if($conn -> connect_error) {
    echo json_encode(["success" => false, "message" => "Chyba pripojenia!"]);
    exit;
}
// 5. vytiahneme ulozeny email v session a ulozime ho do premennej aby sme s nim mohli dalej pracovat
$email = $_SESSION['user_email'];
// 6. nacitanie stareho hesla
$statement =  $conn->prepare("SELECT password FROM users WHERE email=?"); // ? placeholder, nahradi sa
$statement-> bind_param("s", $email); // nahradime placeholder vytiahnutym emailom "s" == string (bind_param = musime definovat typ = menej chyb)
$statement->execute(); // poslanie na databazu
$result = $statement->get_result(); // vysledok
// 7. overenie stareho hesla
if($user = $result->fetch_assoc()) { // vytiahneme heslo z SQL dotazu co sme robili vyssie a ulozime ho do $user
    if(!password_verify($oldPassword, $user['password'])) {
        echo json_encode(["success" => false, "message" => "Staré heslo je nesprávne!"]);
        exit;
    }
// 8. zmena no nove heslo
$hnPassword = password_hash($newPassword, PASSWORD_DEFAULT); // zahashovanie hesla
$update = $conn->prepare("UPDATE users SET password=? WHERE email=?"); // SQL dotaz 
$update->bind_param("ss", $hnPassword, $email); // nemusi byt ale lepsie ked je, menej chyb
$update->execute(); // spustenie noveho dotazu = ulozi nove heslo do password

echo json_encode(["success" => true, "message" => "Heslo bolo úspešne zmenené!"]);
session_unset();
session_destroy();
} else {
    echo json_encode(["success" => false, "message" => "Používateľ neexistuje."]);
}


$conn->close();
?>