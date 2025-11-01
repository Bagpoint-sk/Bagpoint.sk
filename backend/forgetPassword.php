<?php
// Co potrebujeme spravit? user klikne na zabudnute heslo a zada email a odosle
// My ten email chceme overit s emailom v nasej databaze
header('Content-Type: application/json');
// Musime nacitat udaje ktore sa nam odoslu z fetch 
$data = json_decode(file_get_contents('php://input'), true); // citanie http poziadavky a nacitame to cez  file_get_contents('php://input) a zobrazime to ako php(json_decode) pole = true (bez true by to bola class)  
$email = trim($data['email'] ?? ''); // ziskame hodnotu z pola $data['email'], pouzijeme null coalescing  operator ak email neexistuje a funckiou trim odstranime medzery na zaciatku a konci. Nakoniec ulozime do premennej.
// cize to bude ako $email = 'test@test.com' ; format
if(empty($email)) { // kontrola ci prisiel prazndy email
    echo json_encode(['success' => false, 'message' => "Email je prázdny!"]); // php zobrazime ako js ak prisiel prazdny email
    exit;
}
// Musime overit ci email existtuje v nasej databaze
// Musime sa napojit na databazu
$conn = new mysqli("localhost", "root", "", "bagpoint.sk"); // pripojenie na nasu databazu
if($conn -> connect_error) { // ak sa nepodarilo pripojenie k DB
    echo json_encode(['success' => false, "message" => "Chyba pripojenia!"]);
    exit;
}
// Overime, ci dany email sa nachadza v databaze
$statement = $conn->prepare("SELECT user_id FROM users WHERE email=?"); // priprava SQL dotazu kde ? = placeholder / ochrana
$statement->bind_param("s", $email); // ochrana pred SQL injection
$statement->execute(); // vykona sa , posle sa na mySQL server
$result = $statement->get_result(); // ziskame vysledok
// Musime spravit aj to, ze sa tam nenachadza
if($result->num_rows === 0) {
    echo json_encode(['success' => false, "message" => "Zadaný email sa nenašiel!"]);
    exit;
}
// Ked tam ten email najdeme, vygenerujeme nahodny kod
$reset_code = random_int(100000, 999999);

// ulozime kod do DB --> mozme vytvorit tabulku pren napriklad
$updatePassword = $conn->prepare("INSERT INTO reset_codes(code,email) VALUES (?,?)");
$updatePassword->bind_param("ss", $reset_code, $email);
$updatePassword->execute();    


if ($updatePassword->affected_rows > 0) {
    echo json_encode([
        'success' => true,
        'message' => 'Reset kód bol vygenerovaný.',
        'code' => $reset_code // len tréningovo zobrazíme frontend-u
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Nepodarilo sa uložiť reset kód.']);
}

$conn->close();
?>