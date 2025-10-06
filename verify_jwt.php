<?php
require 'config.php';
require 'vendor/autoload.php'; // library firebase/php-jwt

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function verify_jwt() {
    global $JWT_SECRET;

    // Ambil header Authorization
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(["error" => "Token missing"]);
        exit();
    }

    // Format: Bearer <token>
    $authHeader = $headers['Authorization'];
    if (strpos($authHeader, 'Bearer ') !== 0) {
        http_response_code(401);
        echo json_encode(["error" => "Invalid token format"]);
        exit();
    }

    $token = substr($authHeader, 7); // ambil token setelah "Bearer "

    try {
        // Decode JWT
        $decoded = JWT::decode($token, new Key($JWT_SECRET, 'HS256'));
        return $decoded; // hasil payload (object)
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(["error" => "Token invalid: " . $e->getMessage()]);
        exit();
    }
}
