<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

if(isset($_SESSION['user_email'])) {
    echo json_encode([
        "loggedIn" => true,
        "user" => [
            "email" => $_SESSION['user_email'],
            "name" => $_SESSION['user_name']
        ]
        ]);        
} else {
    echo json_encode(["loggedIn" => false]);
}
