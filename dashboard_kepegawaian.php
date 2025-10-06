<?php
session_start();
include "config.php";

// Proteksi role kepegawaian
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kepegawaian') {
    header("Location: login.php");
    exit;
}

// Ambil data statistik dari database
$totalPegawai = $conn->query("SELECT COUNT(*) AS total FROM pegawai")->fetch_assoc()['total'];
$totalIzin = $conn->query("SELECT COUNT(*) AS total FROM izin_pegawai")->fetch_assoc()['total'];
$totalDokumen = $conn->query("SELECT COUNT(*) AS total FROM dokumen_pegawai")->fetch_assoc()['total'];
$totalNotif = $conn->query("SELECT COUNT(*) AS total FROM notifikasi WHERE status='baru'")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Kepegawaian</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; }
    .content-wrapper { margin-left: 250px; padding: 20px; }
    .card-hover:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.2); }
    .card-hover i { transition: transform 0.3s, color 0.3s; }
    .card-hover:hover i { transform: scale(1.2); color: #fff; }
    .tooltip-inner { max-width: 200px; text-align: center; }
  </style>
</head>
<body>
  <?php include "sidebar_kepegawaian.php"; ?>

  <div class="content-wrapper">
    <h3 class="mb-4"><i class="bi bi-speedometer2"></i> Dashboard Kepegawaian</h3>

    <div class="row g-4">
      <!-- Total Pegawai -->
      <div class="col-md-3 d-flex">
        <div class="card card-hover text-white bg-primary w-100 h-100" data-bs-toggle="tooltip" title="Jumlah seluruh pegawai">
          <div class="card-body d-flex align-items-center justify-content-between">
            <div>
              <h6>Total Pegawai</h6>
              <h3><?= $totalPegawai ?></h3>
            </div>
            <i class="bi bi-people-fill display-4 opacity-75"></i>
          </div>
        </div>
      </div>

      <!-- Total Izin -->
      <div class="col-md-3 d-flex">
        <div class="card card-hover text-white bg-success w-100 h-100" data-bs-toggle="tooltip" title="Jumlah izin pegawai saat ini">
          <div class="card-body d-flex align-items-center justify-content-between">
            <div>
              <h6>Total Izin</h6>
              <h3><?= $totalIzin ?></h3>
            </div>
            <i class="bi bi-journal-check display-4 opacity-75"></i>
          </div>
        </div>
      </div>

      <!-- Dokumen Pegawai -->
      <div class="col-md-3 d-flex">
        <div class="card card-hover text-white bg-warning w-100 h-100" data-bs-toggle="tooltip" title="Jumlah dokumen pegawai">
          <div class="card-body d-flex align-items-center justify-content-between">
            <div>
              <h6>Dokumen Pegawai</h6>
              <h3><?= $totalDokumen ?></h3>
            </div>
            <i class="bi bi-file-earmark-text display-4 opacity-75"></i>
          </div>
        </div>
      </div>

      <!-- Notifikasi Baru -->
      <div class="col-md-3 d-flex">
        <div class="card card-hover text-white bg-danger w-100 h-100" data-bs-toggle="tooltip" title="Notifikasi baru yang belum dibaca">
          <div class="card-body d-flex align-items-center justify-content-between">
            <div>
              <h6>Notifikasi Baru</h6>
              <h3><?= $totalNotif ?></h3>
            </div>
            <i class="bi bi-bell-fill display-4 opacity-75"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Bisa tambahkan chart/rekap tambahan di bawah -->
    <div class="row mt-4">
      <div class="col-12">
        <div class="card shadow-sm p-3">
          <h5>Statistik Pegawai</h5>
          <canvas id="chartPegawai" height="100"></canvas>
        </div>
      </div>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    // Tooltip bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    })

    // Chart Pegawai (contoh)
    const ctx = document.getElementById('chartPegawai').getContext('2d');
    const chart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['Pegawai Aktif', 'Izin', 'Dokumen', 'Notifikasi Baru'],
        datasets: [{
          label: 'Jumlah',
          data: [<?= $totalPegawai ?>, <?= $totalIzin ?>, <?= $totalDokumen ?>, <?= $totalNotif ?>],
          backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545']
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, precision:0 } }
      }
    });
  </script>
</body>
</html>
