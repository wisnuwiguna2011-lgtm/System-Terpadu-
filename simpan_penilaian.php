<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pimpinan') {
    header("Location: login.php");
    exit;
}

$pegawai_id = $_POST['pegawai_id'] ?? null;
$tanggal    = $_POST['tanggal'] ?? null;
$disiplin   = $_POST['disiplin'] ?? 0;
$kuantitas  = $_POST['kuantitas'] ?? 0;
$kualitas   = $_POST['kualitas'] ?? 0;
$inovasi    = $_POST['inovasi'] ?? 0;

if ($pegawai_id && $tanggal) {
    // cek apakah sudah ada
    $cek = $conn->prepare("SELECT id FROM penilaian_harian WHERE pegawai_id=? AND tanggal=?");
    $cek->bind_param("is", $pegawai_id, $tanggal);
    $cek->execute();
    $res = $cek->get_result();

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $id = $row['id'];
        $upd = $conn->prepare("UPDATE penilaian_harian 
                               SET disiplin=?, kuantitas=?, kualitas=?, inovasi=? 
                               WHERE id=?");
        $upd->bind_param("iiiii", $disiplin, $kuantitas, $kualitas, $inovasi, $id);
        $upd->execute();
    } else {
        $ins = $conn->prepare("INSERT INTO penilaian_harian 
            (pegawai_id, tanggal, disiplin, kuantitas, kualitas, inovasi) 
            VALUES (?,?,?,?,?,?)");
        $ins->bind_param("isiiii", $pegawai_id, $tanggal, $disiplin, $kuantitas, $kualitas, $inovasi);
        $ins->execute();
    }
}

header("Location: penilaian_pegawai.php");
exit;
