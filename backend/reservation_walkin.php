<?php
// --- Nastavenie PHP pre logovanie ---
ini_set('display_errors', 1); // zobrazovanie chýb
ini_set('display_startup_errors', 1);
ini_set('log_errors', 0); // vypnuté zapisovanie do súborov
error_reporting(E_ALL);

// --- Funkcia na bezpečný JSON výstup ---
function respond($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Zachytávanie neodchytených chýb a výnimiek ---
set_exception_handler(function($e){
    respond([
        "success" => false,
        "message" => "Výnimka PHP",
        "error" => $e->getMessage()
    ]);
});
set_error_handler(function($errno, $errstr, $errfile, $errline){
    respond([
        "success" => false,
        "message" => "Chyba PHP",
        "error" => "$errstr v $errfile na riadku $errline"
    ]);
});

// --- Pripojenie k DB ---
require_once 'db_connect.php';
if (!isset($conn) || !$conn) {
    respond(["success" => false, "message" => "Databázové pripojenie nie je inicializované"]);
}

// --- Načítanie JSON vstupu ---
$input = file_get_contents("php://input");
$data = json_decode($input, true);
if ($data === null) {
    respond(["success" => false, "message" => "Chyba dekódovania JSON: " . json_last_error_msg()]);
}

// --- Validácia vstupov ---
$type = $data["type"] ?? null;
$userEmail = $data["userEmail"] ?? null;
$locationId = $data["locationId"] ?? null;
$sizes = $data["sizes"] ?? [];

if (!$type || !$userEmail || !$locationId || empty($sizes)) {
    respond(["success" => false, "message" => "Chýbajú údaje"]);
}

// --- Nájdi používateľa ---
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email=?");
$stmt->execute([$userEmail]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    respond(["success" => false, "message" => "Používateľ neexistuje"]);
}
$user_id = $user["user_id"];

// --- Mapovanie veľkostí ---
$sizeMap = ['Malý (S)' => 'S', 'Stredný (M)' => 'M', 'Veľký (L)' => 'L'];
foreach ($sizes as $key => $size) {
    if (isset($sizeMap[$size])) {
        $sizes[$key] = $sizeMap[$size];
    } else {
        respond(["success" => false, "message" => "Neznáma veľkosť: $size"]);
    }
}

// --- Transakcia a rezervácia ---
$conn->beginTransaction();

$dateFrom = date("Y-m-d H:i:s");
$dateTo = null;

 $stmt = $conn->prepare("
    INSERT INTO reservations (users_user_id, total_price, reservation_date, status, date_from, date_to, type_reservation)
    VALUES (?,0,NOW(),'pending',?,?,'walkin') RETURNING reservation_id
");
$stmt->execute([$user_id, $dateFrom, $dateTo]);
$reservation_id = $stmt->fetchColumn();
if ($reservation_id === false) throw new Exception("Nepodarilo sa získať reservation_id.");

$assignedBoxes = [];
foreach ($sizes as $size) {
    $stmt = $conn->prepare("
        SELECT box_id FROM boxes
        WHERE type_reservation=:type_reservation AND status='free' AND size=:size AND location=:location
        LIMIT 1
    ");
    $stmt->execute([':type_reservation' => $type, ':size' => $size, ':location' => $locationId]);
    $box = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$box) {
        $conn->rollBack();
        respond(["success" => false, "message" => "Žiadny voľný box veľkosti $size v lokalite $locationId."]);
    }

    $box_id = $box['box_id'];
    $assignedBoxes[] = $box_id;

    $stmt = $conn->prepare("UPDATE boxes SET status='occupied' WHERE box_id=?");
    $stmt->execute([$box_id]);

    $stmt = $conn->prepare("INSERT INTO reservation_boxes (reservation_id, box_id) VALUES (?,?)");
    $stmt->execute([$reservation_id, $box_id]);
}

$conn->commit();

// --- Odpoveď úspechu ---
respond([
    "success" => true,
    "message" => "Rezervácia vytvorená",
    "reservationId" => $reservation_id,
    "boxes" => $assignedBoxes,
    "totalPrice" => null
]);
?>

