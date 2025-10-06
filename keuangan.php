<?php
session_start();
include "config.php";

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'keuangan') {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Keuangan</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="card">
    <h2>Selamat datang, <?= $_SESSION['username']; ?> (Keuangan)</h2>
    <p>Ini adalah halaman khusus role <b>Keuangan</b>.</p>
    <a href="logout.php">Logout</a>
</div>
</body>
</html>
