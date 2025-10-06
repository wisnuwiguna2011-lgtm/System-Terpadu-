<?php
session_start();

// Hapus semua session
$_SESSION = [];
session_destroy();

// Arahkan balik ke halaman login
header("Location: index.php");
exit();
