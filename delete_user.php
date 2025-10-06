<?php
session_start();
include "config.php";

if ($_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];

// Jangan hapus akun admin default
if ($id == 1) {
    echo "<script>alert('Akun admin utama tidak bisa dihapus!'); window.location='dashboard.php';</script>";
    exit;
}

$conn->query("DELETE FROM users WHERE id=$id");
header("Location: dashboard.php");
exit;
