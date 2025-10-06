<?php
// Debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include "config.php";

// Proteksi role
if(!isset($_SESSION['role']) || $_SESSION['role']!=='kepegawaian'){
    header("Location: login.php");
    exit;
}

// Ambil ID
if(!isset($_GET['id'])){
    header("Location: jabatan.php");
    exit;
}
$id = intval($_GET['id']);

// Ambil data jabatan berdasarkan ID
$stmt = $conn->prepare("SELECT * FROM riwayat_jabatan WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$data){
    echo "Data tidak ditemukan.";
    exit;
}

// Ambil daftar pegawai
$pegawai = $conn->query("SELECT id, nip, nama_lengkap FROM pegawai ORDER BY nama_lengkap ASC")->fetch_all(MYSQLI_ASSOC);

// Proses update
if($_SERVER['REQUEST_METHOD']==='POST'){
    $pegawai_id     = $_POST['pegawai_id'];
    $jabatan        = $_POST['jabatan'];
    $jenis_jabatan  = $_POST['jenis_jabatan'];
    $eselon         = $_POST['eselon'] ?? null;
    $unit_kerja     = $_POST['unit_kerja'];
    $tmt_jabatan    = $_POST['tmt_jabatan'];
    $no_sk          = $_POST['no_sk'];
    $tgl_sk         = $_POST['tgl_sk'];
    $pejabat_penetap= $_POST['pejabat_penetap'];

    $stmt = $conn->prepare("UPDATE riwayat_jabatan 
        SET pegawai_id=?, jabatan=?, jenis_jabatan=?, eselon=?, unit_kerja=?, tmt_jabatan=?, no_sk=?, tgl_sk=?, pejabat_penetap=? 
        WHERE id=?");
    $stmt->bind_param("issssssssi",$pegawai_id,$jabatan,$jenis_jabatan,$eselon,$unit_kerja,$tmt_jabatan,$no_sk,$tgl_sk,$pejabat_penetap,$id);
    $stmt->execute();
    $stmt->close();

    header("Location: jabatan.php?update=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Edit Riwayat Jabatan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
    <h3✏️ Edit Riwayat Jabatan</h3>
    <form method="post">
        <div class="row mb-2">
            <div class="col-md-4">
                <label>Pegawai</label>
                <select name="pegawai_id" class="form-control" required>
                    <?php foreach($pegawai as $pg): ?>
                    <option value="<?= $pg['id'] ?>" <?= $pg['id']==$data['pegawai_id']?'selected':'' ?>>
                        <?= $pg['nip']." - ".$pg['nama_lengkap'] ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label>Nama Jabatan</label>
                <input type="text" name="jabatan" class="form-control" value="<?= htmlspecialchars($data['jabatan']) ?>" required>
            </div>
            <div class="col-md-4">
                <label>Jenis Jabatan</label>
                <select name="jenis_jabatan" class="form-control" required>
                    <option value="Struktural" <?= $data['jenis_jabatan']=='Struktural'?'selected':'' ?>>Struktural</option>
                    <option value="Fungsional" <?= $data['jenis_jabatan']=='Fungsional'?'selected':'' ?>>Fungsional</option>
                    <option value="Pelaksana" <?= $data['jenis_jabatan']=='Pelaksana'?'selected':'' ?>>Pelaksana</option>
                </select>
            </div>
        </div>
        <div class="row mb-2">
            <div class="col-md-3">
                <label>Eselon</label>
                <input type="text" name="eselon" class="form-control" value="<?= htmlspecialchars($data['eselon']) ?>">
            </div>
            <div class="col-md-3">
                <label>Unit Kerja</label>
                <input type="text" name="unit_kerja" class="form-control" value="<?= htmlspecialchars($data['unit_kerja']) ?>" required>
            </div>
            <div class="col-md-3">
                <label>TMT Jabatan</label>
                <input type="date" name="tmt_jabatan" class="form-control" value="<?= $data['tmt_jabatan'] ?>" required>
            </div>
            <div class="col-md-3">
                <label>No SK</label>
                <input type="text" name="no_sk" class="form-control" value="<?= htmlspecialchars($data['no_sk']) ?>" required>
            </div>
        </div>
        <div class="row mb-2">
            <div class="col-md-3">
                <label>Tanggal SK</label>
                <input type="date" name="tgl_sk" class="form-control" value="<?= $data['tgl_sk'] ?>" required>
            </div>
            <div class="col-md-4">
                <label>Pejabat Penetap</label>
                <input type="text" name="pejabat_penetap" class="form-control" value="<?= htmlspecialchars($data['pejabat_penetap']) ?>" required>
            </div>
        </div>
        <button type="submit" class="btn btn-success mt-3">💾 Update</button>
        <a href="jabatan.php" class="btn btn-secondary mt-3">⬅️ Kembali</a>
    </form>
</div>
</body>
</html>
