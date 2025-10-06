<?php
session_start();
include "config.php";

// Proteksi role
if(!isset($_SESSION['role']) || $_SESSION['role']!=='kepegawaian'){
    header("Location: login.php");
    exit;
}

if(!isset($_GET['id'])){
    header("Location: jabatan.php");
    exit;
}
$id = intval($_GET['id']);

$stmt = $conn->prepare("DELETE FROM riwayat_jabatan WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$stmt->close();

header("Location: jabatan.php?hapus=1");
exit;
