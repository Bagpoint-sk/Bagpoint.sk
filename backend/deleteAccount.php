<?php
require_once __DIR__ . '/sessionConfig.php';
header("Content-Type: application/json; charset=UTF-8");

// 1) Overenie loginu
if (!isset($_SESSION['user_email'])) {
  echo json_encode(['success' => false, 'message' => 'Nie si prihlásený!']);
  exit;
}

// 2) Nacitanie hesla
$input = json_decode(file_get_contents("php://input"), true) ?: [];
$userPassword = $input['userPassword'] ?? '';

if (!$userPassword) {
  echo json_encode(['success' => false, 'message' => 'Vyplň pole s heslom!']);
  exit;
}

// 3) DB
require_once __DIR__ . '/db_connect.php';

$email = $_SESSION['user_email'];

try {
  $conn->beginTransaction();

  // 4) Nacitaj user_id + hash hesla
  $stmt = $conn->prepare("SELECT user_id, password FROM users WHERE email = :email");
  $stmt->execute([':email' => $email]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$user) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'Používateľ neexistuje.']);
    exit;
  }

  if (!password_verify($userPassword, $user['password'])) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'Zadané heslo je nesprávne!']);
    exit;
  }

  $uid = (int)$user['user_id'];

  // 5) Uvolni boxy (volitelne, ale prakticke)
  $stmt = $conn->prepare("
    UPDATE boxes
    SET status = 'free'
    WHERE box_id IN (
      SELECT rb.box_id
      FROM reservation_boxes rb
      JOIN reservations r ON r.reservation_id = rb.reservation_id
      WHERE r.users_user_id = :uid
    )
  ");
  $stmt->execute([':uid' => $uid]);

  // 6) Zmaz prepojenia reservation_boxes -> reservations
  $stmt = $conn->prepare("
    DELETE FROM reservation_boxes
    WHERE reservation_id IN (
      SELECT reservation_id FROM reservations WHERE users_user_id = :uid
    )
  ");
  $stmt->execute([':uid' => $uid]);

  // 7) Zmaz rezervacie usera
  $stmt = $conn->prepare("DELETE FROM reservations WHERE users_user_id = :uid");
  $stmt->execute([':uid' => $uid]);

  // 8) Zmaz usera
  $stmt = $conn->prepare("DELETE FROM users WHERE user_id = :uid");
  $stmt->execute([':uid' => $uid]);

  $conn->commit();

  session_unset();
  session_destroy();

  echo json_encode(['success' => true, 'message' => 'Účet bol úspešne vymazaný.']);
} catch (Throwable $e) {
  if ($conn->inTransaction()) $conn->rollBack();
  error_log("Delete account error: " . $e->getMessage());
  echo json_encode(['success' => false, 'message' => 'Chyba pri mazaní účtu!']);
}
