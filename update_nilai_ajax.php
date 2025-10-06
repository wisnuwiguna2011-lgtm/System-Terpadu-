<?php
session_start();
header('Content-Type: application/json');
include "config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pimpinan') {
    echo json_encode(['status'=>'error','msg'=>'Not authorized']); exit;
}

// CSRF check
$csrf = $_POST['csrf'] ?? '';
if (empty($csrf) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
    echo json_encode(['status'=>'error','msg'=>'Token invalid']); exit;
}

$log_id = isset($_POST['log_id']) ? intval($_POST['log_id']) : 0;
$aspek = $_POST['aspek'] ?? '';
$nilai = isset($_POST['nilai']) ? intval($_POST['nilai']) : null;

$allowed = ['disiplin'=>'nilai_disiplin','kualitas'=>'nilai_kualitas','inisiatif'=>'nilai_inisiatif','kerjasama'=>'nilai_kerjasama'];
if (!$log_id || !isset($allowed[$aspek]) || $nilai === null || $nilai < 1 || $nilai > 5) {
    echo json_encode(['status'=>'error','msg'=>'Invalid input']); exit;
}

$kolom = $allowed[$aspek];
$penilai_id = intval($_SESSION['user_id']);
$ip = $_SERVER['REMOTE_ADDR'] ?? null;

// Ambil nilai lama
$stmt = $conn->prepare("SELECT $kolom, nilai_disiplin, nilai_kualitas, nilai_inisiatif, nilai_kerjasama FROM log_harian WHERE id=? LIMIT 1");
$stmt->bind_param("i", $log_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(['status'=>'error','msg'=>'Log not found']); exit;
}
$row = $res->fetch_assoc();
$old = $row[$kolom] !== null ? intval($row[$kolom]) : null;
$stmt->close();

// Update kolom
$stmt2 = $conn->prepare("UPDATE log_harian SET $kolom = ? WHERE id = ?");
$stmt2->bind_param("ii", $nilai, $log_id);
$ok = $stmt2->execute();
$stmt2->close();

if (!$ok) {
    echo json_encode(['status'=>'error','msg'=>'DB update failed']); exit;
}

// Hitung ulang nilai rata (averaging non-null aspek)
$aspek_vals = [];
foreach (['nilai_disiplin','nilai_kualitas','nilai_inisiatif','nilai_kerjasama'] as $c) {
    if ($c === $kolom) $aspek_vals[] = $nilai;
    else $aspek_vals[] = isset($row[$c]) && $row[$c] !== null ? intval($row[$c]) : null;
}
$valid = array_filter($aspek_vals, function($v){ return $v !== null; });
if (count($valid) > 0) $rata = array_sum($valid)/count($valid); else $rata = null;

// Update nilai_rata & penilai_id
$stmt3 = $conn->prepare("UPDATE log_harian SET nilai_rata = ?, penilai_id = ? WHERE id = ?");
if ($rata === null) {
    $stmt3->bind_param("dii", $rata, $penilai_id, $log_id); // will cast null to 0? better use separate query
    // We'll run different query for null
    $stmt3 = $conn->prepare("UPDATE log_harian SET nilai_rata = NULL, penilai_id = ? WHERE id = ?");
    $stmt3->bind_param("ii", $penilai_id, $log_id);
    $stmt3->execute();
} else {
    $stmt3->bind_param("dii", $rata, $penilai_id, $log_id);
    $stmt3->execute();
}
$stmt3->close();

// Insert audit
$stmt4 = $conn->prepare("INSERT INTO penilaian_audit (log_id, penilai_id, aspek, nilai_lama, nilai_baru, ip) VALUES (?,?,?,?,?,?)");
$stmt4->bind_param("iiisss", $log_id, $penilai_id, $aspek, $old, $nilai, $ip);
$stmt4->execute();
$stmt4->close();

echo json_encode(['status'=>'ok','msg'=>'Saved','nilai_rata'=> $rata !== null ? round($rata,2) : 0]);
exit;
