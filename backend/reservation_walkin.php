<?php
// --- Nastavenie PHP pre logovanie ---
ini_set('display_errors', 1); // zobrazovanie chyb
ini_set('display_startup_errors', 1);
ini_set('log_errors', 0); // vypnute zapisovanie do suborov
error_reporting(E_ALL);

// --- Funkcia na bezpecny JSON vystup ---
function respond($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Zachytavanie neodchytenych chyb a vynimiek ---
set_exception_handler(function($e){
    respond([
        "success" => false,
        "message" => "Vynimka PHP",
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
    respond(["success" => false, "message" => "Databazove pripojenie nie je inicializovane"]);
}

// --- Nacitanie JSON vstupu ---
$input = file_get_contents("php://input");
$data = json_decode($input, true);
if ($data === null) {
    respond(["success" => false, "message" => "Chyba dekodovania JSON: " . json_last_error_msg()]);
}

// --- Validacia vstupov ---
$type = $data["type"] ?? null;
$userEmail = $data["userEmail"] ?? null;
$locationId = $data["locationId"] ?? null;
$sizes = $data["sizes"] ?? [];

if (!$type || !$userEmail || !$locationId || empty($sizes)) {
    respond(["success" => false, "message" => "Chybaju udaje"]);
}

// --- Najdi pouzivatela ---
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email=?");
$stmt->execute([$userEmail]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    respond(["success" => false, "message" => "Pouzivatel neexistuje"]);
}
$user_id = $user["user_id"];

// --- Mapovanie velkosti ---
$sizeMap = ['Malý (S)' => 'S', 'Stredný (M)' => 'M', 'Veľký (L)' => 'L'];
foreach ($sizes as $key => $size) {
    if (isset($sizeMap[$size])) {
        $sizes[$key] = $sizeMap[$size];
    } else {
        respond(["success" => false, "message" => "Neznama velkost: $size"]);
    }
}

// --- Transakcia a rezervacia ---
$conn->beginTransaction();

$dateFrom = date("Y-m-d H:i:s");
$dateTo = null;

$stmt = $conn->prepare("
    INSERT INTO reservations (users_user_id, total_price, reservation_date, status, date_from, date_to, type_reservation)
    VALUES (?,0,NOW(),'pending',?,?,'walkin') RETURNING reservation_id
");
$stmt->execute([$user_id, $dateFrom, $dateTo]);
$reservation_id = $stmt->fetchColumn();
if ($reservation_id === false) throw new Exception("Nepodarilo sa ziskat reservation_id.");

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
        respond(["success" => false, "message" => "Ziadny volny box velkosti $size v lokalite $locationId."]);
    }

    $box_id = $box['box_id'];
    $assignedBoxes[] = $box_id;

    $stmt = $conn->prepare("UPDATE boxes SET status='occupied' WHERE box_id=?");
    $stmt->execute([$box_id]);

    $stmt = $conn->prepare("INSERT INTO reservation_boxes (reservation_id, box_id) VALUES (?,?)");
    $stmt->execute([$reservation_id, $box_id]);
}

$conn->commit();

// --- Odpoved uspechu ---
respond([
    "success" => true,
    "message" => "Rezervacia vytvorena",
    "reservationId" => $reservation_id,
    "boxes" => $assignedBoxes,
    "totalPrice" => null
]);
?>
