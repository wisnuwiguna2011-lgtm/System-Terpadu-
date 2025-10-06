<?php
session_start();
include "config.php";
include "sidebar_kepegawaian.php"; // Sidebar admin

// Proteksi khusus role kepegawaian
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kepegawaian') {
    header("Location: login.php");
    exit;
}

// Ambil daftar pegawai
$sql = "SELECT id, nip, nama_lengkap FROM pegawai ORDER BY nama_lengkap ASC";
$result = $conn->query($sql);
$pegawaiList = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Filter input
$filter_pegawai = $_GET['pegawai_id'] ?? '';
$filter_bulan  = $_GET['bulan'] ?? ''; // Format: YYYY-MM

// Ambil data log harian
$sql2 = "SELECT lh.*, p.nama_lengkap, p.nip 
         FROM log_harian lh 
         JOIN pegawai p ON lh.pegawai_id = p.id 
         WHERE 1=1";

$params = [];
$types = "";

// Filter pegawai
if ($filter_pegawai && $filter_pegawai !== 'all') {
    $sql2 .= " AND lh.pegawai_id = ?";
    $params[] = $filter_pegawai;
    $types .= "i";
}

// Filter bulan
if ($filter_bulan) {
    list($year, $month) = explode("-", $filter_bulan);
    $sql2 .= " AND YEAR(lh.tanggal) = ? AND MONTH(lh.tanggal) = ?";
    $params[] = $year;
    $params[] = $month;
    $types .= "ii";
}

$sql2 .= " ORDER BY lh.tanggal DESC, lh.id DESC";

$stmt = $conn->prepare($sql2);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result2 = $stmt->get_result();
$logList = $result2->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Export Excel
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=log_harian_pegawai.xls");
    echo "<table border='1'>";
    echo "<tr>
            <th>No</th>
            <th>NIP</th>
            <th>Nama Pegawai</th>
            <th>Tanggal</th>
            <th>Aktivitas</th>
            <th>Deskripsi</th>
            <th>SKP</th>
            <th>Output</th>
            <th>Bukti</th>
          </tr>";
    foreach($logList as $i => $log){
        echo "<tr>";
        echo "<td>".($i+1)."</td>";
        echo "<td>".htmlspecialchars($log['nip'])."</td>";
        echo "<td>".htmlspecialchars($log['nama_lengkap'])."</td>";
        echo "<td>".date("d-m-Y", strtotime($log['tanggal']))."</td>";
        echo "<td>".htmlspecialchars($log['nama_aktivitas'])."</td>";
        echo "<td>".htmlspecialchars($log['deskripsi'])."</td>";
        echo "<td>".htmlspecialchars($log['tautan_skp'] ?: "-")."</td>";
        echo "<td>".htmlspecialchars($log['output'] ?: "-")."</td>";
        echo "<td>".($log['file_bukti'] ? "Lihat" : "-")."</td>";
        echo "</tr>";
    }
    echo "</table>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>📋 Hasil Log Harian Pegawai</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.6.2/dist/select2-bootstrap4.min.css" rel="stylesheet">
<style>
.content { margin-left:250px; padding:20px; }
.select2-container--bootstrap-5 .select2-selection { height: calc(1.5em + 0.75rem + 2px); padding:0.375rem 0.75rem; }
</style>
</head>
<body class="bg-light">

<div class="content">
<h3 class="mb-4">📋 Hasil Log Harian Pegawai</h3>

<!-- Filter -->
<div class="card mb-4 shadow-sm">
  <div class="card-header bg-primary text-white">🔍 Filter Log Harian</div>
  <div class="card-body">
    <form method="GET" class="row g-3">
      <div class="col-md-5">
        <label class="form-label">Pegawai</label>
        <select name="pegawai_id" class="form-select select2">
          <option value="all">-- Semua Pegawai --</option>
          <?php foreach($pegawaiList as $pg): ?>
            <option value="<?= $pg['id'] ?>" <?= $filter_pegawai == $pg['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($pg['nama_lengkap'])." (".$pg['nip'].")" ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Bulan</label>
        <input type="month" name="bulan" class="form-control" value="<?= $filter_bulan ?>">
      </div>
      <div class="col-md-3 d-flex align-items-end gap-2">
        <button type="submit" class="btn btn-success w-100"><i class="bi bi-filter-circle"></i> Filter</button>
        <a href="?<?= http_build_query(['pegawai_id'=>$filter_pegawai,'bulan'=>$filter_bulan,'export'=>'excel']) ?>" class="btn btn-outline-primary w-100">
          <i class="bi bi-download"></i> Export Excel
        </a>
      </div>
    </form>
  </div>
</div>

<!-- Tabel Log Harian -->
<div class="card shadow-sm">
  <div class="card-header bg-dark text-white">📑 Daftar Log Harian</div>
  <div class="card-body table-responsive">
    <table class="table table-bordered table-striped table-hover">
      <thead class="table-dark text-center">
        <tr>
          <th>No</th>
          <th>NIP</th>
          <th>Nama Pegawai</th>
          <th>Tanggal</th>
          <th>Aktivitas</th>
          <th>Deskripsi</th>
          <th>SKP</th>
          <th>Output</th>
          <th>Bukti</th>
        </tr>
      </thead>
      <tbody>
        <?php if(count($logList) > 0): ?>
          <?php foreach($logList as $i => $log): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><?= htmlspecialchars($log['nip']) ?></td>
            <td><?= htmlspecialchars($log['nama_lengkap']) ?></td>
            <td><?= date("d-m-Y", strtotime($log['tanggal'])) ?></td>
            <td><?= htmlspecialchars($log['nama_aktivitas']) ?></td>
            <td><?= nl2br(htmlspecialchars($log['deskripsi'])) ?></td>
            <td><a href="<?= htmlspecialchars($log['tautan_skp']) ?>" target="_blank"><?= $log['tautan_skp'] ?: "-" ?></a></td>
            <td><?= htmlspecialchars($log['output']) ?: "-" ?></td>
            <td>
              <?php if ($log['file_bukti']): ?>
                <a href="uploads/logs/<?= htmlspecialchars($log['file_bukti']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                  <i class="bi bi-paperclip"></i> Lihat
                </a>
              <?php else: ?>-
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="9" class="text-center">Belum ada log harian</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
  $('.select2').select2({ theme: "bootstrap-5", placeholder: "-- Pilih Pegawai --" });
});
</script>
</body>
</html>
