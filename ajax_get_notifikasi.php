<?php
session_start();
include "config.php";

// Proteksi hanya pegawai yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pegawai') {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id']; // ambil dari session, bukan GET

$query = $conn->prepare("
    SELECT n.id, n.pesan, n.status, n.created_at, p.whatsapp
    FROM notifikasi n
    LEFT JOIN users u ON n.user_id = u.id
    LEFT JOIN pegawai p ON p.user_id = u.id
    WHERE n.user_id=? OR n.user_id IS NULL
    ORDER BY n.created_at DESC
");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$notif = $result->fetch_all(MYSQLI_ASSOC);
$query->close();

// Format tanggal
foreach($notif as &$n){
    $n['created_at'] = date("d M Y H:i", strtotime($n['created_at']));
}

header('Content-Type: application/json');
echo json_encode($notif);
