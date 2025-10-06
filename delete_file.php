<?php
session_start();
include 'config.php';

// cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// validasi parameter
if (!isset($_GET['id']) || !isset($_GET['folder_id'])) {
    die("Akses tidak valid.");
}

$id = intval($_GET['id']);
$folder_id = intval($_GET['folder_id']);

// cari nama file
$stmt = $conn->prepare("SELECT nama_file FROM files WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$file = $result->fetch_assoc();
$stmt->close();

if ($file) {
    $filePath = "uploads/" . $file['nama_file'];
    if (file_exists($filePath)) {
        unlink($filePath); // hapus fisik file
    }

    // hapus di database
    $stmt = $conn->prepare("DELETE FROM files WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

header("Location: list_files.php?folder_id=" . $folder_id);
exit;
