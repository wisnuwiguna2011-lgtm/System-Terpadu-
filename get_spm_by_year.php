<?php
session_start();
include 'config.php';

// Ambil parameter tahun
$year = $_GET['year'] ?? '';
$result = [];

if ($year) {
    // Ambil daftar folder berdasarkan tahun
    $stmt = $conn->prepare("SELECT id, nama_folder FROM folders WHERE tahun_kegiatan=? ORDER BY nama_folder ASC");
    $stmt->bind_param("s", $year);
    $stmt->execute();
    $res = $stmt->get_result();
    
    while ($row = $res->fetch_assoc()) {
        $result[] = $row;
    }
    $stmt->close();
}

// Kembalikan data dalam bentuk JSON
header('Content-Type: application/json');
echo json_encode($result);
