<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pimpinan') exit;

$penilai_id = $_SESSION['user_id'];
$pegawai_id = intval($_POST['pegawai_id']);
$periode = $conn->real_escape_string($_POST['periode']);
$nilai = $_POST['nilai'];
$komentar = $_POST['komentar'] ?? null;

foreach ($nilai as $kid => $skor) {
    $kRes = $conn->query("SELECT nama_kriteria FROM kriteria_penilaian WHERE id=$kid");
    $k = $kRes->fetch_assoc();
    $namaK = $k['nama_kriteria'];

    $stmt = $conn->prepare("REPLACE INTO penilaian_batch 
        (pegawai_id, penilai_id, periode, kriteria, skor, komentar)
        VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("iissis", $pegawai_id, $penilai_id, $periode, $namaK, $skor, $komentar);
    $stmt->execute();
}

header("Location: rekap_penilaian.php?pegawai_id=$pegawai_id&period=$periode&success=1");
exit;
