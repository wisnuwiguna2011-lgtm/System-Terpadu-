<?php
session_start();
include 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'keuangan') exit;

$no_spm = trim($_POST['no_spm'] ?? '');
$tahun  = trim($_POST['tahun'] ?? '');

if($no_spm && $tahun){
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM folders WHERE nama_folder=? AND tahun_kegiatan=?");
    $stmt->bind_param("ss", $no_spm, $tahun);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    echo ($res['total'] > 0) ? 'exists' : 'ok';
}
