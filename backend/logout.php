<?php
require_once __DIR__ . '/sessionConfig.php';
session_destroy();
header("Content-Type: application/json; charset=UTF-8");
echo json_encode(["success" => true]);
?>