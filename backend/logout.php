<?php
require_once __DIR__ . '/sessionConfig.php';
header("Content-Type: application/json; charset=UTF-8");
session_unset();     // vymaze vsetky session premenne
session_destroy();  // zmaze session
echo json_encode(["success" => true]);
?>