<?php

header('Content-Type: application/json');

require_once 'db_connect.php'; 

//  nacitanie dat
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

// 2. overenie
if (!$name || !$email || !$message) {
    echo json_encode(["success" => false, "error" => "Chýbajúce údaje vo formulári!"]);
    exit;
}

try {
    // 3. ulozenie do databazy
    $stmt = $conn->prepare("INSERT INTO messages (name, email, message) VALUES (:name, :email, :message)");
    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':message', $message, PDO::PARAM_STR);
    $stmt->execute();

    echo json_encode(["success" => true]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Chyba: " . $e->getMessage()]);
}
?>
