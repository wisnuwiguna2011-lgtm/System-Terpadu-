<?php
$host     = "localhost";      // biasanya localhost
$username = "urhg326v_admin";  // ganti dengan user DB kamu
$password = "StrongPass#2025";    // ganti dengan password DB kamu
$database = "urhg326v_dbarsip"; // nama database kamu

$mysqli = new mysqli($host, $username, $password, $database);

if ($mysqli->connect_error) {
    die("Koneksi gagal: " . $mysqli->connect_error);
}
?>
