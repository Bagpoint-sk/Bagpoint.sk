<?php
// Funguje len na HTTPS (Render)
session_set_cookie_params([
    'path' => '/',
    'httponly' => true,
    'samesite' => 'None', // musí byť None, aby fungovalo s fetch() + credentials: 'include'
    'secure' => true      // https = true, http = false
]);

session_start();