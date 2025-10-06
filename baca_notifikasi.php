<?php
session_start();
include "config.php";

// Proteksi hanya untuk role pegawai
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pegawai') {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id'])) {
    $notif_id = intval($_GET['id']);
    $user_id  = $_SESSION['user_id'];

    // Update status notifikasi
    $stmt = $conn->prepare("UPDATE notifikasi SET status='dibaca' WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $notif_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

// Balik ke dashboard pegawai
header("Location: dashboard_pegawai.php");
exit;
