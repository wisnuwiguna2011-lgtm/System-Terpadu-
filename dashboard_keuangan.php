<?php
session_start();
include 'config.php';

// Proteksi role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'keuangan') {
    header("Location: login.php");
    exit;
}

// Ambil data statistik
$total_folder = $conn->query("SELECT COUNT(*) AS total FROM folders")->fetch_assoc()['total'] ?? 0;
$total_odner  = $conn->query("SELECT COUNT(*) AS total FROM odners")->fetch_assoc()['total'] ?? 0;
$total_file   = $conn->query("SELECT COUNT(*) AS total FROM files")->fetch_assoc()['total'] ?? 0;
$total_spm    = $conn->query("SELECT SUM(nilai_spm) AS total FROM folders")->fetch_assoc()['total'] ?? 0;

// Ambil data chart
$stat_tahun = [];
$res = $conn->query("SELECT tahun_kegiatan AS tahun, COUNT(*) AS jumlah FROM folders GROUP BY tahun_kegiatan ORDER BY tahun_kegiatan ASC");
if ($res) while ($row = $res->fetch_assoc()) $stat_tahun[] = $row;

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Keuangan</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .sidebar-link {
      @apply flex items-center gap-3 px-4 py-2 rounded-lg text-gray-700 transition-all duration-200;
    }
    .sidebar-link svg {
      @apply w-5 h-5 text-gray-500;
    }
    .sidebar-link span {
      @apply text-sm font-medium;
    }
    .sidebar-link:hover {
      @apply bg-indigo-50 text-indigo-700;
    }
    .sidebar-link.active {
      @apply bg-indigo-100 text-indigo-700 font-semibold;
    }
  </style>
</head>
<body class="h-screen flex bg-gray-100">

  <!-- Sidebar -->
<aside id="sidebar" class="w-64 bg-white shadow-lg flex flex-col transition-all duration-300">
  <!-- Header -->
  <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-200">
    <div class="w-10 h-10 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold">DK</div>
    <div>
      <h1 class="text-base font-bold text-gray-800">Dashboard</h1>
      <p class="text-xs text-gray-500">Keuangan</p>
    </div>
  </div>

  <!-- Menu -->
  <nav class="flex-1 px-3 py-6 overflow-y-auto">
    <p class="text-xs font-semibold text-gray-400 uppercase mb-3 px-2">Main Menu</p>
    <ul class="space-y-1">

      <!-- Dashboard -->
      <li>
        <a href="dashboard_keuangan.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-all <?= $current_page=='dashboard_keuangan.php'?'bg-indigo-100 text-indigo-700 font-semibold':'' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7m-9 2v8m-4 0h8"/>
          </svg>
          <span class="text-sm">Dashboard</span>
        </a>
      </li>

      <!-- Daftar SPM -->
      <li>
        <a href="upload.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-all <?= $current_page=='upload.php'?'bg-indigo-100 text-indigo-700 font-semibold':'' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          <span class="text-sm">Daftar SPM</span>
        </a>
      </li>

      <!-- Rekap SPM -->
      <li>
        <a href="rekap_keuangan.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-all <?= $current_page=='rekap_keuangan.php'?'bg-indigo-100 text-indigo-700 font-semibold':'' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6h6v6m2 4H7a2 2 0 01-2-2V7a2 2 0 012-2h10a2 2 0 012 2z"/>
          </svg>
          <span class="text-sm">Rekap SPM</span>
        </a>
      </li>

      <!-- Cetak SPM -->
      <li>
        <a href="cetak_stiker_folder.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-all <?= $current_page=='cetak_stiker_folder.php'?'bg-indigo-100 text-indigo-700 font-semibold':'' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18h12v4H6v-4z"/>
          </svg>
          <span class="text-sm">Cetak SPM</span>
        </a>
      </li>

      <!-- Pencarian -->
      <li>
        <a href="print_list.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-all <?= $current_page=='print_list.php'?'bg-indigo-100 text-indigo-700 font-semibold':'' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4-4m0 0A7 7 0 1110 4a7 7 0 017 13z"/>
          </svg>
          <span class="text-sm">Pencarian</span>
        </a>
      </li>
    </ul>
  </nav>

  <!-- Footer -->
  <div class="p-4 border-t border-gray-200">
    <a href="logout.php" class="block text-center px-4 py-2 rounded-lg bg-red-500 hover:bg-red-600 text-white text-sm font-medium">
      Logout
    </a>
  </div>
</aside>

  <!-- Konten Utama -->
  <main class="flex-1 p-6 overflow-y-auto">
    <header class="mb-8">
      <h2 class="text-2xl font-bold text-gray-800">Dashboard Keuangan</h2>
      <p class="text-gray-500">Ringkasan data keuangan terbaru</p>
    </header>

    <!-- Statistik Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
      <div class="p-6 bg-white rounded-xl shadow text-center">
        <h3 class="text-sm font-medium text-gray-500">Total Folder</h3>
        <p class="text-2xl font-bold text-gray-800 mt-2"><?= $total_folder ?></p>
      </div>
      <div class="p-6 bg-white rounded-xl shadow text-center">
        <h3 class="text-sm font-medium text-gray-500">Total Odner</h3>
        <p class="text-2xl font-bold text-gray-800 mt-2"><?= $total_odner ?></p>
      </div>
      <div class="p-6 bg-white rounded-xl shadow text-center">
        <h3 class="text-sm font-medium text-gray-500">Total File</h3>
        <p class="text-2xl font-bold text-gray-800 mt-2"><?= $total_file ?></p>
      </div>
      <div class="p-6 bg-white rounded-xl shadow text-center">
        <h3 class="text-sm font-medium text-gray-500">Total Nilai SPM</h3>
        <p class="text-xl font-bold text-indigo-600 mt-2">Rp <?= number_format($total_spm,0,',','.') ?></p>
      </div>
    </div>

    <!-- Chart Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
      <div class="bg-white p-6 rounded-xl shadow">
        <h3 class="text-sm font-semibold text-gray-600 mb-4">Distribusi Data</h3>
        <canvas id="pieChart"></canvas>
      </div>
      <div class="bg-white p-6 rounded-xl shadow">
        <h3 class="text-sm font-semibold text-gray-600 mb-4">Tren Folder per Tahun</h3>
        <canvas id="barChart"></canvas>
      </div>
    </div>

    <!-- Tabel Ringkasan -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Tahun</th>
            <th class="px-6 py-3 text-right text-sm font-semibold text-gray-600">Jumlah Folder</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php foreach($stat_tahun as $row): ?>
          <tr>
            <td class="px-6 py-4 text-sm text-gray-800"><?= htmlspecialchars($row['tahun']) ?></td>
            <td class="px-6 py-4 text-sm text-right text-gray-800"><?= $row['jumlah'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>

  <script>
    // Toggle Sidebar
    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      sidebar.classList.toggle('-ml-64');
    }

    // Pie Chart
    const pieCtx = document.getElementById('pieChart').getContext('2d');
    new Chart(pieCtx, {
      type: 'doughnut',
      data: {
        labels: ['Folder', 'Odner', 'File'],
        datasets: [{
          data: [<?= $total_folder ?>, <?= $total_odner ?>, <?= $total_file ?>],
          backgroundColor: ['#6366f1', '#8b5cf6', '#10b981'],
          borderWidth: 1
        }]
      },
      options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });

    // Bar Chart
    const barCtx = document.getElementById('barChart').getContext('2d');
    new Chart(barCtx, {
      type: 'bar',
      data: {
        labels: <?= json_encode(array_column($stat_tahun, 'tahun')) ?>,
        datasets: [{
          label: 'Jumlah Folder',
          data: <?= json_encode(array_column($stat_tahun, 'jumlah')) ?>,
          backgroundColor: '#6366f1',
          borderRadius: 6
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
