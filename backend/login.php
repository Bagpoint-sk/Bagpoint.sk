<?php
require_once __DIR__ . '/sessionConfig.php';

header("Content-Type: application/json; charset=UTF-8");

// pripojen k databaze
require_once 'db_connect.php';

// nacitanie json z dotazu
$input = json_decode(file_get_contents("php://input"), true);
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

if (!$email || !$password) {
  echo json_encode(["success" => false, "message" => "Vyplňte všetky polia!"]);
  exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  echo json_encode(["success" => false, "message" => "Neplatný email!"]);
  exit;
}

// overenie
try {
  $stmt = $conn->prepare("SELECT user_id, name, email, password FROM users WHERE email= :email");
  $stmt->bindParam(':email', $email, PDO::PARAM_STR);
  $stmt->execute();
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($user) {
    if (password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        
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
        echo json_encode(["success" => false, "message" => "Nesprávny email alebo heslo!"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Nesprávny email alebo heslo!"]);
}
} catch (PDOException $error) {
  error_log("Login error: " . $error->getMessage());
  echo json_encode(["success" => false, "message" => "Chyba pri prihlásení"]);
}
