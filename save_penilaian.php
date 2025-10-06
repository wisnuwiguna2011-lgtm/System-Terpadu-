<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pimpinan') {
    http_response_code(403);
    exit("Unauthorized");
}

$penilai_id = $_SESSION['user_id'];
$log_id     = intval($_POST['log_id'] ?? 0);
$kid        = intval($_POST['kid'] ?? 0);
$skor       = $_POST['skor'] ?? null;
$komen      = $_POST['komentar'] ?? null;

// Ambil nama kriteria
$stmtK = $conn->prepare("SELECT nama_kriteria FROM kriteria_penilaian WHERE id=?");
$stmtK->bind_param("i", $kid);
$stmtK->execute();
$res = $stmtK->get_result();
$kr  = $res->fetch_assoc();
$stmtK->close();
if (!$kr) exit("Invalid kriteria");

$kriteria = $kr['nama_kriteria'];

// Hapus nilai lama
$stmtDel = $conn->prepare("DELETE FROM penilaian WHERE log_id=? AND penilai_id=? AND kriteria=?");
$stmtDel->bind_param("iis", $log_id, $penilai_id, $kriteria);
$stmtDel->execute();
$stmtDel->close();

// Simpan baru
if ($skor || $komen) {
    $stmt = $conn->prepare("INSERT INTO penilaian (log_id, penilai_id, kriteria, skor, komentar) VALUES (?,?,?,?,?)");
    $stmt->bind_param("iiiss", $log_id, $penilai_id, $kriteria, $skor, $komen);
    $stmt->execute();
    $stmt->close();
}

echo "OK";
