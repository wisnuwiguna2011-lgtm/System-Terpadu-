<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config.php';

// Cegah double session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🚫 Hanya admin boleh akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<div class='alert alert-danger'>Akses ditolak!</div>";
    exit;
}

// Gunakan koneksi dari config.php
$db = $conn;

// Fungsi aman untuk hitung jumlah data
function getCount($db, $table){
    $q = $db->query("SHOW TABLES LIKE '$table'");
    if(!$q || $q->num_rows==0){ return 0; } // tabel belum ada
    $res = $db->query("SELECT COUNT(*) as jml FROM $table");
    if(!$res){ return 0; }
    $row = $res->fetch_assoc();
    return $row['jml'] ?? 0;
}

// Ambil statistik
$totalUsers     = getCount($db, "users");
$totalDocs      = getCount($db, "documents");
$totalFolders   = getCount($db, "folders");
$totalTransfers = getCount($db, "transfers");

// Data grafik dokumen bulanan (Line)
$chartLabels = [];
$chartValues = [];
$res = $db->query("
  SELECT DATE_FORMAT(created_at, '%Y-%m') as bulan, COUNT(*) as jml
  FROM documents
  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
  GROUP BY DATE_FORMAT(created_at, '%Y-%m')
  ORDER BY bulan ASC
");
if($res){
  while($row = $res->fetch_assoc()){
    $chartLabels[] = $row['bulan'];
    $chartValues[] = $row['jml'];
  }
}

// Data grafik pie: distribusi user per role
$roleLabels = [];
$roleValues = [];
$resRole = $db->query("SELECT role, COUNT(*) as jml FROM users GROUP BY role");
if($resRole){
  while($row = $resRole->fetch_assoc()){
    $roleLabels[] = ucfirst($row['role']);
    $roleValues[] = $row['jml'];
  }
}

// Data grafik bar: Top uploader
$userDocLabels = [];
$userDocValues = [];
$resUserDocs = $db->query("
  SELECT u.username, COUNT(d.id) as jml
  FROM documents d
  JOIN users u ON d.uploaded_by = u.id
  GROUP BY u.username
  ORDER BY jml DESC
  LIMIT 10
");
if($resUserDocs){
  while($row = $resUserDocs->fetch_assoc()){
    $userDocLabels[] = $row['username'];
    $userDocValues[] = $row['jml'];
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Laporan Statistik</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
<div class="container py-4">
  <h3 class="mb-4"><i class="fa-solid fa-file-alt me-2"></i>Laporan Statistik</h3>
  
  <!-- Statistik Ringkas -->
  <div class="row g-4 mb-5">
    <div class="col-md-3">
      <div class="card shadow-sm rounded-4 text-center p-3 bg-primary text-white">
        <i class="fa fa-users fa-2x mb-2"></i>
        <h4><?= $totalUsers ?></h4>
        <p class="mb-0">Total Pengguna</p>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm rounded-4 text-center p-3 bg-success text-white">
        <i class="fa fa-file fa-2x mb-2"></i>
        <h4><?= $totalDocs ?></h4>
        <p class="mb-0">Total Dokumen</p>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm rounded-4 text-center p-3 bg-warning text-dark">
        <i class="fa fa-folder fa-2x mb-2"></i>
        <h4><?= $totalFolders ?></h4>
        <p class="mb-0">Total Folder</p>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm rounded-4 text-center p-3 bg-info text-dark">
        <i class="fa fa-share fa-2x mb-2"></i>
        <h4><?= $totalTransfers ?></h4>
        <p class="mb-0">Total Transfer</p>
      </div>
    </div>
  </div>

  <!-- Grafik -->
  <div class="row g-4">
    <div class="col-md-8">
      <div class="card shadow-sm rounded-4 p-4">
        <h5 class="mb-3"><i class="fa fa-chart-line me-2"></i>Tren Upload Dokumen (12 bulan terakhir)</h5>
        <canvas id="chartDocs" height="100"></canvas>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card shadow-sm rounded-4 p-4">
        <h5 class="mb-3"><i class="fa fa-chart-pie me-2"></i>Distribusi Pengguna</h5>
        <canvas id="chartUsers" height="100"></canvas>
      </div>
    </div>
  </div>

  <div class="row g-4 mt-3">
    <div class="col-md-12">
      <div class="card shadow-sm rounded-4 p-4">
        <h5 class="mb-3"><i class="fa fa-chart-bar me-2"></i>Top Uploader (10 besar)</h5>
        <canvas id="chartTopUploader" height="100"></canvas>
      </div>
    </div>
  </div>
</div>

<script>
// Grafik Line Dokumen
new Chart(document.getElementById('chartDocs'), {
  type: 'line',
  data: {
    labels: <?= json_encode($chartLabels) ?>,
    datasets: [{
      label: 'Dokumen di-upload',
      data: <?= json_encode($chartValues) ?>,
      borderColor: 'rgba(54, 162, 235, 1)',
      backgroundColor: 'rgba(54, 162, 235, 0.2)',
      tension: 0.3,
      fill: true,
      pointRadius: 5,
      pointHoverRadius: 7
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true } }
  }
});

// Grafik Pie Distribusi User
new Chart(document.getElementById('chartUsers'), {
  type: 'pie',
  data: {
    labels: <?= json_encode($roleLabels) ?>,
    datasets: [{
      data: <?= json_encode($roleValues) ?>,
      backgroundColor: ['#007bff','#28a745','#ffc107','#dc3545']
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { position: 'bottom' } }
  }
});

// Grafik Bar Top Uploader
new Chart(document.getElementById('chartTopUploader'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($userDocLabels) ?>,
    datasets: [{
      label: 'Jumlah Dokumen',
      data: <?= json_encode($userDocValues) ?>,
      backgroundColor: 'rgba(75, 192, 192, 0.6)',
      borderColor: 'rgba(75, 192, 192, 1)',
      borderWidth: 1
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true } }
  }
});
</script>
</body>
</html>
