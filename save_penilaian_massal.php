<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pimpinan') exit;

$penilai_id = $_SESSION['user_id'];
$periode = $_POST['periode'] ?? date('Y-m');
$nilai = $_POST['nilai'] ?? [];
$komentar = $_POST['komentar'] ?? [];

// Simpan semua pegawai
foreach ($nilai as $pegawai_id => $nilaiKriteria) {
    foreach ($nilaiKriteria as $kid => $skor) {
        if ($skor) {
            $kRes = $conn->query("SELECT nama_kriteria FROM kriteria_penilaian WHERE id=$kid");
            $k = $kRes->fetch_assoc();
            $namaK = $k['nama_kriteria'];

            $stmt = $conn->prepare("REPLACE INTO penilaian_batch 
                (pegawai_id, penilai_id, periode, kriteria, skor, komentar)
                VALUES (?,?,?,?,?,?)");
            $komen = $komentar[$pegawai_id] ?? null;
            $stmt->bind_param("iissis", $pegawai_id, $penilai_id, $periode, $namaK, $skor, $komen);
            $stmt->execute();
        }
    }
}

header("Location: rekap_bulanan.php?periode=$periode&success=1");
exit;
