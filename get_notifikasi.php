<?php
session_start();
include "config.php";

header("Content-Type: application/json");

// Pastikan user login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["jml_baru"=>0, "list"=>[]]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil jumlah notifikasi baru
$stmt = $conn->prepare("SELECT COUNT(*) as jml_baru FROM notifikasi WHERE (user_id IS NULL OR user_id=?) AND status='baru'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$jml_baru = $stmt->get_result()->fetch_assoc()['jml_baru'] ?? 0;
$stmt->close();

// Ambil 5 notifikasi terakhir
$stmt = $conn->prepare("SELECT pesan, status, created_at FROM notifikasi WHERE (user_id IS NULL OR user_id=?) ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$list = [];
while($row = $res->fetch_assoc()) {
    $list[] = [
        "pesan" => htmlspecialchars($row['pesan']),
        "status" => $row['status'],
        "waktu" => date("d-m-Y H:i", strtotime($row['created_at']))
    ];
}
$stmt->close();

echo json_encode(["jml_baru"=>$jml_baru, "list"=>$list]);
