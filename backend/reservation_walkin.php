<?php
// --- Absolútna cesta k logom ---
$debugLog = __DIR__ . '/debug.log';
$errorLog = __DIR__ . '/php_error.log';

// --- Nastavenie PHP pre logovanie ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', $errorLog);
error_reporting(E_ALL);

// --- Funkcia na zápis debugu ---
function debug($message) {
    global $debugLog;
    // Skúšame zápis, ak sa nepodarí, vypíšeme do error_log
    if (file_put_contents($debugLog, "[".date("Y-m-d H:i:s")."] $message\n", FILE_APPEND) === false) {
        error_log("DEBUG LOG ERROR: $message");
    }
}

// --- Zachytávanie neodchytených chýb a výnimiek ---
set_error_handler(function($errno, $errstr, $errfile, $errline){
    $msg = "[ERROR][$errno] $errstr in $errfile on line $errline";
    debug($msg);
    error_log($msg);
});
set_exception_handler(function($e){
    $msg = "[EXCEPTION] ".$e->getMessage()." in ".$e->getFile()." on line ".$e->getLine();
    debug($msg);
    error_log($msg);
});

// --- Funkcia na bezpečný JSON výstup ---
function respond($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

debug("DEBUG: Skript sa spustil");

// --- Pripojenie k DB ---
require_once 'db_connect.php';
if (!isset($conn) || !$conn) {
    $msg = "Databázové pripojenie nie je inicializované";
    debug($msg);
    error_log($msg);
    respond(["success"=>false, "message"=>$msg]);
}

// --- Načítanie a logovanie JSON vstupu ---
$input = file_get_contents("php://input");
debug("DEBUG: Raw input: ".$input);

$data = json_decode($input, true);
if ($data === null) {
    $msg = "Chyba dekódovania JSON: ".json_last_error_msg();
    debug($msg);
    error_log($msg);
    respond(["success"=>false, "message"=>$msg]);
}
debug("DEBUG: Dekódované dáta: ".json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// --- Validácia vstupov ---
$type = $data["type"] ?? null;
$userEmail = $data["userEmail"] ?? null;
$locationId = $data["locationId"] ?? null;
$sizes = $data["sizes"] ?? [];

if (!$type || !$userEmail || !$locationId || empty($sizes)) {
    $msg = "Chýbajú údaje";
    debug($msg);
    error_log($msg);
    respond(["success"=>false, "message"=>$msg]);
}

// --- Nájdi používateľa ---
try {
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email=?");
    $stmt->execute([$userEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    debug("DEBUG: User fetched: ".json_encode($user));

    if (!$user) {
        $msg = "Používateľ neexistuje";
        debug($msg);
        respond(["success"=>false, "message"=>$msg]);
    }
    $user_id = $user["user_id"];
} catch(Exception $e){
    $msg = "Chyba pri načítaní používateľa: ".$e->getMessage();
    debug($msg);
    error_log($msg);
    respond(["success"=>false, "message"=>"Chyba pri načítaní používateľa.", "error"=>$e->getMessage()]);
}

// --- Mapovanie veľkostí ---
$sizeMap = ['Malý (S)'=>'S','Stredný (M)'=>'M','Veľký (L)'=>'L'];
foreach ($sizes as $key => $size){
    if(isset($sizeMap[$size])){
        $sizes[$key] = $sizeMap[$size];
    } else {
        $msg = "Neznáma veľkosť: $size";
        debug($msg);
        error_log($msg);
        respond(["success"=>false, "message"=>$msg]);
    }
}
debug("DEBUG: Velkosti mapovane: ".json_encode($sizes));

// --- Transakcia a rezervácia ---
try {
    $conn->beginTransaction();
    debug("DEBUG: Transaction started");

    $dateFrom = date("Y-m-d H:i:s");
    $dateTo = null;

    $stmt = $conn->prepare("
        INSERT INTO reservations (users_user_id, total_price, reservation_date, status, date_from, date_to)
        VALUES (?,0,NOW(),'pending',?,?) RETURNING reservation_id
    ");
    $stmt->execute([$user_id,$dateFrom,$dateTo]);
    $reservation_id = $stmt->fetchColumn();
    if($reservation_id===false) throw new Exception("Nepodarilo sa získať reservation_id.");
    debug("DEBUG: Reservation created with ID $reservation_id");

    $assignedBoxes=[];
    foreach($sizes as $size){
        $stmt = $conn->prepare("
            SELECT box_id FROM boxes
            WHERE type_reservation=:type_reservation AND status='free' AND size=:size AND location=:location
            LIMIT 1
        ");
        $stmt->execute([':type_reservation'=>$type,':size'=>$size,':location'=>$locationId]);
        $box = $stmt->fetch(PDO::FETCH_ASSOC);
        debug("DEBUG: Box fetched for size $size: ".json_encode($box));

        if(!$box){
            $conn->rollBack();
            $msg="Žiadny voľný box veľkosti $size v lokalite $locationId.";
            debug($msg);
            error_log($msg);
            respond(["success"=>false,"message"=>$msg]);
        }

        $box_id = $box['box_id'];
        $assignedBoxes[] = $box_id;

        $stmt = $conn->prepare("UPDATE boxes SET status='occupied' WHERE box_id=?");
        $stmt->execute([$box_id]);
        debug("DEBUG: Box $box_id nastaveny ako occupied");

        $stmt = $conn->prepare("INSERT INTO reservation_boxes (reservation_id,box_id) VALUES (?,?)");
        $stmt->execute([$reservation_id,$box_id]);
        debug("DEBUG: Box $box_id priradeny k rezervacii $reservation_id");
    }

    $conn->commit();
    debug("DEBUG: Transaction committed");

    respond(["success"=>true,"message"=>"Rezervácia vytvorená","reservationId"=>$reservation_id,"boxes"=>$assignedBoxes,"totalPrice"=>null]);
} catch(Exception $e){
    if($conn->inTransaction()) $conn->rollBack();
    $msg = "Chyba pri vytváraní rezervácie: ".$e->getMessage();
    debug($msg);
    error_log($msg);
    respond(["success"=>false,"message"=>"Chyba pri vytváraní rezervácie","error"=>$e->getMessage()]);
}
?>

