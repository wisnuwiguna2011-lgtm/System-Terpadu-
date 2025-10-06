<?php
// ---- Session Setup ----
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        // Jangan pakai 'domain' supaya PHP otomatis pakai domain aktif (hindari redirect loop)
        'secure' => true,    // wajib HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// ---- Database Connection ----
$host = "localhost";
$user = "urhg326v_admin";   // user DB dari cPanel
$pass = "StrongPass#2025";  // password DB
$db   = "urhg326v_dbarsip"; // nama database

$conn = new mysqli($host, $user, $pass, $db);

// ---- Error Handling ----
if ($conn->connect_error) {
    if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            "status" => "error",
            "message" => "Database connection failed"
        ]);
    } else {
        die("Koneksi database gagal: " . $conn->connect_error);
    }
    exit;
}

// ---- JWT Secret ----
// Ganti dengan string panjang & random (misalnya hasil `openssl rand -base64 64`)
$JWT_SECRET = "RahasiaSuperAman123!";
?>
