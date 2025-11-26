<?php
ini_set('session.use_strict_mode', 1); // validacia session ID (nemoze byt vytvorene nahodne)
// Funguje len na HTTPS (Render)
session_set_cookie_params([
    'path' => '/',
    'httponly' => true,
    'samesite' => 'None', // musí byť None, aby fungovalo s fetch() + credentials: 'include'
    'secure' => true      // https = true, http = false
]);

session_start();