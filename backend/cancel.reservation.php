<?php
session_start();
require_once 'db_connect.php'; // PDO pripojenie
header("Content-Type: application/json");

$user_id = $_SESSION['user_id'] ?? null;
$reservation_id = $_POST['id'] ?? null;

if (!$user_id || !$reservation_id) {
    echo json_encode(["success" => false, "message" => "Neplatné údaje"]);
    exit;
}

try {
    // --- Načítať rezerváciu ---
    $stmt = $conn->prepare("SELECT * FROM reservations WHERE reservation_id = :rid AND users_user_id = :uid");
    $stmt->execute([':rid' => $reservation_id, ':uid' => $user_id]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        echo json_encode(["success" => false, "message" => "Rezervácia neexistuje"]);
        exit;
    }

    // --- Skontrolovať, či už rezervácia nie je ukončená ---
    if ($reservation['status'] === 'completed') {
        echo json_encode(["success" => false, "message" => "Rezervácia už je ukončená"]);
        exit;
    }

    // --- Načítať všetky boxy rezervácie ---
    $stmt = $conn->prepare("
        SELECT b.box_id, b.price_per_hour, b.type_reservation
        FROM boxes b
        JOIN reservation_boxes rb ON b.box_id = rb.box_id
        WHERE rb.reservation_id = :rid
    ");
    $stmt->execute([':rid' => $reservation_id]);
    $boxes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $status = 'completed';
    $total_price = $reservation['total_price'];
    $date_to = $reservation['date_to'];

    // --- Ak walkin, dopočítať cenu a nastaviť date_to ---
    if (!empty($boxes) && $boxes[0]['type_reservation'] === 'walkin') {
        $date_to = date('Y-m-d H:i:s');
        $start = new DateTime($reservation['date_from']);
        $end = new DateTime($date_to);
        $hours = ceil(($end->getTimestamp() - $start->getTimestamp()) / 3600);
        $total_price = $hours * $boxes[0]['price_per_hour'];
    }

    // --- Aktualizácia rezervácie ---
    $stmt = $conn->prepare("
        UPDATE reservations 
        SET status = :status, total_price = :total_price, date_to = :date_to
        WHERE reservation_id = :rid
    ");
    $stmt->execute([
        ':status' => $status,
        ':total_price' => $total_price,
        ':date_to' => $date_to,
        ':rid' => $reservation_id
    ]);

    // --- Nastavenie všetkých boxov na free ---
    $stmt = $conn->prepare("
        UPDATE boxes 
        SET status = 'free' 
        WHERE box_id IN (SELECT box_id FROM reservation_boxes WHERE reservation_id = :rid)
    ");
    $stmt->execute([':rid' => $reservation_id]);

    echo json_encode(["success" => true, "message" => "Rezervácia bola ukončená a boxy sú voľné"]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Chyba: " . $e->getMessage()]);
}
