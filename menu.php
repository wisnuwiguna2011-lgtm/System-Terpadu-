<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Menu Utama</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="menu-box">
    <h2>Pilih Menu</h2>
    <ul>
        <li><a href="keuangan.php">Keuangan</a></li>
        <li><a href="kepegawaian.php">Kepegawaian</a></li>
        <li><a href="arsiparis.php">Arsiparis</a></li>
    </ul>
    <a href="logout.php">Logout</a>
</div>
</body>
</html>
