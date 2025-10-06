<?php
session_start();
include 'config.php';

// Proteksi login BMN
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'bmn') {
    header("Location: login.php");
    exit;
}

// Ambil ID dokumen
$id = intval($_GET['id'] ?? 0);
if($id <= 0){
    die("ID dokumen tidak valid.");
}

// Ambil nama file untuk dihapus
$stmt = $conn->prepare("SELECT file FROM dokumen_bmn WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows === 0){
    die("Dokumen tidak ditemukan.");
}
$row = $result->fetch_assoc();
$filename = $row['file'];

// Hapus data dari database
$stmt_del = $conn->prepare("DELETE FROM dokumen_bmn WHERE id=?");
$stmt_del->bind_param("i", $id);
if($stmt_del->execute()){
    // Hapus file fisik jika ada
    $filepath = "uploads/bmn/".$filename;
    if(file_exists($filepath)){
        unlink($filepath);
    }
    header("Location: upload_bmn.php?msg=Dokumen berhasil dihapus");
    exit;
} else {
    die("Gagal menghapus dokumen: ".$stmt_del->error);
}
?>
