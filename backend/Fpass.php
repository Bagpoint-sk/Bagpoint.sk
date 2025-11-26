<?php
// Co potrebujeme spravit? user klikne na zabudnute heslo a zada email a odosle
// My ten email chceme overit s emailom v nasej databaze
header('Content-Type: application/json');

// Musime nacitat udaje ktore sa nam odoslu z fetch 
$data = json_decode(file_get_contents('php://input'), true); // citanie http poziadavky a nacitame to cez file_get_contents('php://input') a zobrazime to ako php (json_decode) pole = true (bez true by to bola class)  

$email = trim($data['email'] ?? ''); // ziskame hodnotu z pola $data['email'], pouzijeme null coalescing operator ak email neexistuje a funkciou trim odstranime medzery na zaciatku a konci. Nakoniec ulozime do premennej.
// cize to bude ako $email = 'test@test.com' ; format

if (empty($email)) { // kontrola ci prisiel prazdny email
    echo json_encode(['success' => false, 'message' => "Email je prázdny!"]); // php zobrazime ako js ak prisiel prazdny email
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => "Neplatný email!"]);
    exit;
}

// Musime overit ci email existuje v nasej databaze
// Musime sa napojit na databazu
require_once 'db_connect.php'; // pripojenie cez PDO 

try {
// Overime, ci dany email sa nachadza v databaze
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = :email"); // priprava SQL dotazu kde :email = placeholder / ochrana
$stmt->bindParam(':email', $email, PDO::PARAM_STR); // ochrana pred SQL injection
$stmt->execute(); // vykona sa , posle sa na PostgreSQL server
$result = $stmt->fetch(PDO::FETCH_ASSOC); // ziskame vysledok

// Musime spravit aj to, ze sa tam nenachadza
if (!$result) {
    echo json_encode(['success' => false, "message" => "Zadaný email sa nenašiel!"]);
    exit;
}

// Ked tam ten email najdeme, vygenerujeme nahodny kod
$reset_code = random_int(100000, 999999);

// ulozime kod do DB --> mozme vytvorit tabulku pren napriklad
$insert = $conn->prepare("INSERT INTO reset_codes (code, email) VALUES (:code, :email)");
$insert->bindParam(':code', $reset_code, PDO::PARAM_STR);
$insert->bindParam(':email', $email, PDO::PARAM_STR);
$insert->execute();

// Kontrola, ci sa zapis podaril
if ($insert->rowCount() > 0) {
    echo json_encode([
        'success' => true,
        'message' => 'Reset kód bol vygenerovaný.',
        'code' => $reset_code // ukazka
    ]);

}
} catch (PDOException $e) {
    error_log("Reset code error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Chyba pri generovaní kódu']);
}

?>