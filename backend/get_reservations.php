<?php
// backend/get_user_reservations.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Nie ste prihlásený.'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    require_once 'db_connect.php'; // db_connect nastavuje $conn

    $sql = "
        SELECT r.reservation_id, r.total_price, r.reservation_date, r.status,
               r.date_from, r.date_to, r.created_at,
               COALESCE(MAX(r.type_reservation), MAX(b.type_reservation)) AS type_reservation, -- typ rezervácie z boxov (príklad)
               COALESCE(SUM(b.price_per_hour), 0) AS hourly_rate,
               COALESCE(json_agg(b.box_id) FILTER (WHERE b.box_id IS NOT NULL), '[]'::json) AS boxes -- iba ID boxov
        FROM reservations r
        LEFT JOIN reservation_boxes rb ON r.reservation_id = rb.reservation_id
        LEFT JOIN boxes b ON rb.box_id = b.box_id
        WHERE r.users_user_id = :user_id
        GROUP BY r.reservation_id
        ORDER BY r.reservation_date DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'reservations' => $reservations
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Chyba databázy: ' . $e->getMessage()
    ]);
}
?>
