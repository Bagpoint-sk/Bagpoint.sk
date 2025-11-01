<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

// pripojen k databaze
require_once 'db_connect.php';

// nacitanie json z dotazu
$input = json_decode(file_get_contents("php://input"), true);
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

// overenie
$stmt = $conn->prepare("SELECT user_id, name, email, password FROM users WHERE email= :email");
$stmt->bindParam(':email', $email, PDO::PARAM_STR);
$stmt->execute();

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
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
