<?php
session_start();
include "config.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kepegawaian') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak']);
    exit;
}

if(isset($_POST['id']) && isset($_POST['tunjangan_kinerja'])){
    $id = intval($_POST['id']);
    $tunjangan = intval($_POST['tunjangan_kinerja']);

    $stmt = $conn->prepare("UPDATE pegawai SET tunjangan_kinerja = ? WHERE id = ?");
    $stmt->bind_param("ii", $tunjangan, $id);

    if($stmt->execute()){
        echo json_encode(['status'=>'success']);
    }else{
        echo json_encode(['status'=>'error','message'=>'Gagal update database']);
    }
    $stmt->close();
}else{
    echo json_encode(['status'=>'error','message'=>'Data tidak lengkap']);
}
?>
