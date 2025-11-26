<?php

header('Content-Type: application/json');

require_once 'db_connect.php'; 

//  nacitanie dat
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

// 2. overenie
if (!$name || !$email || !$message) {
    echo json_encode(["success" => false, "message" => "Vyplňte všetky polia!"]);
    exit;
}

$errors = [];
// validacia mena
if (strlen($name) < 2 || strlen($name) > 30) {
    $errors[] = "Meno musí obsahovať 2 - 30 znakov!";
} elseif (!preg_match('/^[\p{L}\s]+$/u', $name)) {
    $errors[] = "Meno môže obsahovať iba písmená!";
}
// validacia emailu
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Neplatná emailová adresa!";
} elseif (strlen($email) > 100) {
    $errors[] = "Email je príliš dlhý!";
}
// validacia spravy
if(strlen($message) < 10 || strlen($message) > 5000) {
    $errors[] = "Správa musí obsahovať 10 - 5000 znakov!";
}
// vratenie chybovych hlaseni
if (!empty($errors)) {
    echo json_encode(["success" => false, "message" => implode(". ", $errors)]);
    exit;
}

try {
    // ulozenie do databazy
    $stmt = $conn->prepare("INSERT INTO messages (name, email, message) VALUES (:name, :email, :message)");
    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':message', $message, PDO::PARAM_STR);
    $stmt->execute();

    echo json_encode(["success" => true]);
} catch (PDOException $e) {
    error_log("Contact form error: " . $e->getMessage());
    echo json_encode(["success" => false, "error" => "Chyba pri odoslaní"]);
}
?>
