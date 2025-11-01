<?php
header('Content-Type: application/json');

// 1. pripojenie k db
$conn = new mysqli("localhost", "root", "", "bagpoint.sk");
if($conn -> connect_error) {
    echo json_encode(["success" => false, "error" => "Nepodarilo sa pripojiť k databáze!"]);
    exit;
}

// 2. ziskanie udajov z POST
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$message = $_POST['message'] ?? '';

// 3. musime overit, ze nie su prazdne
if(!$name ||!$email || !$message) {
    echo json_encode(["success" => false, "error" => "Chýbajúce údaje vo formulári!"]);
    exit;
}

// 4. Ulozime udaje do DB
$statement = $conn->prepare("INSERT INTO messages (name, email, message) VALUES (?, ?, ?)");
$statement->bind_param("sss", $name,$email,$message);

if($statement->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $statement->error]);
}

$statement->close();
$conn->close();

?>