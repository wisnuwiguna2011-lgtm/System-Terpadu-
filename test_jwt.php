<?php
// Tampilkan error supaya mudah debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include manual JWT library
require_once __DIR__ . '/libs/php-jwt/src/JWT.php';
require_once __DIR__ . '/libs/php-jwt/src/Key.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Cek apakah class JWT bisa dipakai
echo "JWT class is available!";

// Contoh membuat token sederhana
$payload = [
    "user_id" => 123,
    "role" => "admin",
    "iat" => time(),
    "exp" => time() + 3600
];

$secret = "RahasiaSuperAman123!";

$jwt = JWT::encode($payload, $secret, 'HS256');

echo "<br>JWT Token: " . $jwt;

// Decode token
$decoded = JWT::decode($jwt, new Key($secret, 'HS256'));
echo "<pre>";
print_r($decoded);
echo "</pre>";
