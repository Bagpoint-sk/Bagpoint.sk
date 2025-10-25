<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

// pripojen k databaze
$conn = new mysqli("localhost", "root", "", "bagpoint.sk");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Chyba pripojenia: " . $conn->connect_error]);
    exit;
}

// nacitanie json z dotazu
$input = json_decode(file_get_contents("php://input"), true);
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

// overenie
$stmt = $conn->prepare("SELECT user_id, name, email, password FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
  if (password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];

    echo json_encode([
      "success" => true,
      "user" => [
        "name" => $user['name'],
        "email" => $user['email']
      ]
    ]);
  } else {
    echo json_encode(["success" => false, "message" => "Zlé heslo"]);
  }
} else {
  echo json_encode(["success" => false, "message" => "Používateľ neexistuje"]);
}
?>
