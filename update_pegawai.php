<?php
session_start();
include "config.php";

// Validasi role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kepegawaian') {
    header("Location: login.php");
    exit;
}

// Validasi ID
$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    die("❌ ID pegawai tidak valid");
}

// Ambil data dari form
$nama_lengkap    = trim($_POST['nama_lengkap']);
$nip             = trim($_POST['nip']);
$jabatan         = trim($_POST['jabatan']);
$unit_kerja      = trim($_POST['unit_kerja']);
$tempat_lahir    = trim($_POST['tempat_lahir']);
$tgl_lahir       = $_POST['tgl_lahir'] ?? null;
$pangkat_gol     = trim($_POST['pangkat_gol']);
$tmt_pangkat     = $_POST['tmt_pangkat'] ?? null;
$tmt_gol         = $_POST['tmt_gol'] ?? null;
$pendidikan      = trim($_POST['pendidikan']);
$status_keluarga = trim($_POST['status_keluarga']);
$no_whatsapp     = trim($_POST['no_whatsapp']);

// Upload foto
$foto_name = $_POST['foto_lama'] ?? null;
$upload_dir = __DIR__ . "/uploads/";

if (!empty($_FILES['foto']['name'])) {
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $foto_name = time() . "_" . preg_replace('/[^a-zA-Z0-9-_\.]/', '', basename($_FILES['foto']['name']));
    move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $foto_name);
}

// Query UPDATE
$sql = "UPDATE pegawai 
        SET nama_lengkap=?, nip=?, jabatan=?, unit_kerja=?, tempat_lahir=?, tgl_lahir=?, 
            pangkat_gol=?, tmt_pangkat=?, tmt_gol=?, pendidikan=?, status_keluarga=?, 
            no_whatsapp=?, foto=? 
        WHERE id=?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("❌ Prepare gagal: " . $conn->error);
}

// Bind param (13 string + 1 int)
if (!$stmt->bind_param(
    "sssssssssssssi",
    $nama_lengkap, $nip, $jabatan, $unit_kerja, $tempat_lahir, $tgl_lahir,
    $pangkat_gol, $tmt_pangkat, $tmt_gol, $pendidikan, $status_keluarga,
    $no_whatsapp, $foto_name, $id
)) {
    die("❌ Bind param gagal: " . $stmt->error);
}

// Eksekusi query
if ($stmt->execute()) {
    header("Location: daftar_pegawai.php?success=1");
    exit;
} else {
    die("❌ Gagal update: " . $stmt->error);
}
?>
