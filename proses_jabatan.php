<?php
session_start();
include "config.php";

// Tampilkan error saat debug (bisa dimatikan di production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Proteksi role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kepegawaian') {
    header("Location: login.php");
    exit;
}

// Proses input data jabatan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pegawai_id      = $_POST['pegawai_id'] ?? '';
    $jabatan         = $_POST['jabatan'] ?? '';
    $unit_kerja      = $_POST['unit_kerja'] ?? '';
    $tmt_jabatan     = $_POST['tmt_jabatan'] ?? '';
    $sampai          = !empty($_POST['sampai']) ? $_POST['sampai'] : null; // bisa NULL
    $no_sk           = $_POST['no_sk'] ?? '';
    $tgl_sk          = $_POST['tgl_sk'] ?? '';
    $pejabat_penetap = $_POST['pejabat_penetap'] ?? '';
    $keterangan      = $_POST['keterangan'] ?? '';

    // Validasi input wajib
    if ($pegawai_id && $jabatan && $unit_kerja && $tmt_jabatan && $no_sk && $tgl_sk && $pejabat_penetap) {
        
        // Query SQL
        $sql = "INSERT INTO riwayat_jabatan 
            (pegawai_id, jabatan, unit_kerja, tmt_jabatan, sampai, no_sk, tgl_sk, pejabat_penetap, keterangan) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("❌ Prepare failed: " . $conn->error);
        }

        // Binding parameter
        // Gunakan "s" untuk string, "i" untuk integer
        $stmt->bind_param(
            "issssssss", 
            $pegawai_id, 
            $jabatan, 
            $unit_kerja, 
            $tmt_jabatan, 
            $sampai,   // bisa NULL
            $no_sk, 
            $tgl_sk, 
            $pejabat_penetap, 
            $keterangan
        );

        // Eksekusi
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: jabatan.php?success=1");
            exit;
        } else {
            die("❌ Execute failed: " . $stmt->error);
        }
    } else {
        die("❌ Data wajib belum lengkap!");
    }
} else {
    header("Location: jabatan.php");
    exit;
}
