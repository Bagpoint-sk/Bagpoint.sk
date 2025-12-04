<?php
// --- Nastavenie PHP pre logovanie ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug.log'); // všetky error_log pôjdu sem
error_reporting(E_ALL);

// --- Funkcia na bezpečný JSON výstup ---
function respond($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

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
$date_from = $data["dateFrom"] ?? null;
$date_to = $data["dateTo"] ?? null;

if (!$type || !$userEmail || !$locationId || empty($sizes) || !$date_from || !$date_to) {
    respond(["success" => false, "message" => "Chýbajú povinné vstupy"]);
}

// --- Mapovanie veľkostí ---
$sizeMap = ['Malý (S)' => 'S', 'Stredný (M)' => 'M', 'Veľký (L)' => 'L'];
foreach ($sizes as $key => $size) {
    if (isset($sizeMap[$size])) {
        $sizes[$key] = $sizeMap[$size];
    } else {
        respond(["success" => false, "message" => "Neznáma veľkosť: $size"]);
    }
}

try {
    // --- Začiatok transakcie ---
    $conn->beginTransaction();

    // --- Nájdi používateľa ---
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email=?");
    $stmt->execute([$userEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        respond(["success" => false, "message" => "Používateľ neexistuje"]);
    }
    $user_id = $user["user_id"];

    // --- Vytvorenie rezervácie ---
    $stmt = $conn->prepare("
        INSERT INTO reservations (
            users_user_id, total_price, reservation_date, status, date_from, date_to
        )
        VALUES (?, 0, NOW(), 'pending', ?, ?)
        RETURNING reservation_id
    ");
    $stmt->execute([$user_id, $date_from, $date_to]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    $reservation_id = $reservation['reservation_id'];

    $assignedBoxes = [];
    $totalPrice = 0;

    $new_from = new DateTime($date_from);
    $new_to   = new DateTime($date_to);
    $new_to->setTime(23, 59, 59); // koniec posledného dňa

    // --- Priradenie boxov ---
    foreach ($sizes as $size) {

        // --- Debug: zisti hodnoty ---
        error_log("Hľadám box veľkosti: '$size' na lokácii: '$locationId'");

        $stmt = $conn->prepare("
            SELECT * FROM boxes 
            WHERE UPPER(size) = UPPER(:size) AND location = :location  AND type_reservation = 'daily'
            ORDER BY box_id
        ");
        $stmt->execute([
            ':size' => $size,
            ':location' => $locationId
        ]);
        $boxes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($boxes)) {
            respond([
                "success" => false,
                "message" => "Žiadne boxy nenájdené pre veľkosť '$size' a lokáciu '$locationId'"
            ]);
        }

        $boxAssigned = false;

        // --- Pre každý box skontroluj kolízie ---
        foreach ($boxes as $box) {
            $box_id = $box['box_id'];

            $stmt = $conn->prepare("
                SELECT r.date_from, r.date_to 
                FROM reservation_boxes rb
                JOIN reservations r ON rb.reservation_id = r.reservation_id
                WHERE rb.box_id = :box_id
                 AND r.status = 'pending'
            ");
            $stmt->execute([':box_id' => $box_id]);
            $existingReservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $conflict = false;

            foreach ($existingReservations as $res) {
                $existing_from = new DateTime($res['date_from']);
                $existing_to   = new DateTime($res['date_to']);
                $existing_to->setTime(23, 59, 59);

                // --- Debug log kolízie ---
                error_log("Box {$box['box_name']} existujúca rezervácia od {$existing_from->format('Y-m-d')} do {$existing_to->format('Y-m-d')}");

                if (!($new_to < $existing_from || $new_from > $existing_to)) {
                    $conflict = true;
                    error_log("Konflikt pre box {$box['box_name']}!");
                    break;
                }
            }

            if (!$conflict) {
                $boxAssigned = true;

                // --- Vypočítaj cenu ---
                $interval = $new_from->diff($new_to);
                $days = $interval->days + 1; // zahrni posledný deň
                $totalPrice += $box['price_per_day'] * $days;

                // --- Insert do reservation_boxes ---
                $stmt = $conn->prepare("INSERT INTO reservation_boxes (reservation_id, box_id) VALUES (?, ?)");
                $stmt->execute([$reservation_id, $box_id]);

                // --- Pridaj do výsledného poľa ---
                $assignedBoxes[] = [
                    'box_id' => $box_id,
                    'box_name' => $box['box_name'],
                    'size' => $box['size'],
                    'location' => $box['location']
                ];

                // --- Debug: úspešné priradenie boxu ---
                error_log("Box {$box['box_name']} priradený k rezervácii $reservation_id");

                break; // už sme našli vhodný box pre túto veľkosť
            }
        }

        if (!$boxAssigned) {
            $conn->rollBack();
            respond(["success" => false, "message" => "Nie je dostupný box veľkosti $size v požadovanom termíne"]);
        }
    }

    // --- Aktualizuj celkovú cenu ---
    $stmt = $conn->prepare("UPDATE reservations SET total_price=? WHERE reservation_id=?");
    $stmt->execute([$totalPrice, $reservation_id]);

    $conn->commit();

    respond([
        "success" => true,
        "message" => "Rezervácia vytvorená",
        "reservationId" => $reservation_id,
        "boxes" => $assignedBoxes,
        "totalPrice" => $totalPrice
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    respond([
        "success" => false,
        "message" => "Chyba pri vytváraní rezervácie",
        "error" => $e->getMessage()
    ]);
}
?>
