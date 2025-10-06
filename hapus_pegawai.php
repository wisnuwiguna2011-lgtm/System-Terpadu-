<?php
session_start();
include "config.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kepegawaian') {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: daftar_pegawai.php");
    exit;
}

$id = intval($_GET['id']);

// Hapus user dan pegawai
$pegawai = $conn->query("SELECT user_id FROM pegawai WHERE id=$id")->fetch_assoc();
if($pegawai){
    $conn->query("DELETE FROM pegawai WHERE id=$id");
    $conn->query("DELETE FROM users WHERE id=" . intval($pegawai['user_id']));
}

header("Location: daftar_pegawai.php");
exit;
