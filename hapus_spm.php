<?php
session_start();
include 'config.php';

// Proteksi login keuangan
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'keuangan') {
    header("Location: login.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    // Hapus data SPM
    $stmt = $conn->prepare("DELETE FROM folders WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: upload.php?msg=deleted");
        exit;
    } else {
        die("❌ Gagal hapus: " . $stmt->error);
    }
    $stmt->close();
} else {
    die("⚠️ ID tidak valid.");
}
