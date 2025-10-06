<?php
session_start();
include "config.php";

// Cek login & role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pimpinan') {
    header("Location: login.php");
    exit;
}

$tahun = date("Y");

// ======================
// Ambil daftar pegawai
// ======================
$pegawaiList = [];
$resPegawai = $conn->query("SELECT id, nama_lengkap FROM pegawai ORDER BY nama_lengkap");
while ($row = $resPegawai->fetch_assoc()) {
    $pegawaiList[] = $row;
}

// ======================
// Rekap rata-rata bulanan (bar chart)
// ======================
$stmt1 = $conn->prepare("
    SELECT bulan, AVG((disiplin+kuantitas+kualitas+inovasi)/4) as rata2
    FROM penilaian_bulanan
    WHERE tahun = ?
    GROUP BY bulan
    ORDER BY bulan
");
$stmt1->bind_param("i", $tahun);
$stmt1->execute();
$res1 = $stmt1->get_result();
$bulanData = [];
while ($r = $res1->fetch_assoc()) {
    $bulanData[(int)$r['bulan']] = round($r['rata2'] ?? 0, 2);
}
$stmt1->close();

// ======================
// Rekap tren pegawai (line chart)
// ======================
$stmt2 = $conn->prepare("
    SELECT p.nama_lengkap, pb.bulan,
           AVG((pb.disiplin+pb.kualitas+pb.kuantitas+pb.inovasi)/4) as rata2
    FROM pegawai p
    LEFT JOIN penilaian_bulanan pb 
      ON pb.pegawai_id = p.id AND pb.tahun=?
    GROUP BY p.id, pb.bulan
    ORDER BY p.nama_lengkap, pb.bulan
");
$stmt2->bind_param("i", $tahun);
$stmt2->execute();
$res2 = $stmt2->get_result();

$dataTren = [];
while ($r = $res2->fetch_assoc()) {
    $nama  = $r['nama_lengkap'];
    $bulan = (int) $r['bulan'];
    $nilai = round($r['rata2'] ?? 0, 2);
    $dataTren[$nama][$bulan] = $nilai;
}
$stmt2->close();

// ======================
// Data tabel rekap nilai rata-rata pegawai
// ======================
$stmt3 = $conn->prepare("
    SELECT p.nama_lengkap, p.nip, p.jabatan,
           ROUND(AVG((pb.disiplin+pb.kuantitas+pb.kualitas+pb.inovasi)/4),2) as rata2
    FROM pegawai p
    LEFT JOIN penilaian_bulanan pb ON pb.pegawai_id = p.id AND pb.tahun=?
    GROUP BY p.id
    ORDER BY rata2 DESC
");
$stmt3->bind_param("i", $tahun);
$stmt3->execute();
$res3 = $stmt3->get_result();
$rekapData = $res3->fetch_all(MYSQLI_ASSOC);
$stmt3->close();

// Label bulan
$labels = ["Jan","Feb","Mar","Apr","Mei","Jun","Jul","Agu","Sep","Okt","Nov","Des"];

// Dataset bar chart
$barData = [];
for ($b=1; $b<=12; $b++) {
    $barData[] = $bulanData[$b] ?? 0;
}

// Dataset line chart
$datasets = [];
$colors = [
    'rgba(54, 162, 235, 0.8)',
    'rgba(255, 99, 132, 0.8)',
    'rgba(255, 206, 86, 0.8)',
    'rgba(75, 192, 192, 0.8)',
    'rgba(153, 102, 255, 0.8)',
    'rgba(255, 159, 64, 0.8)',
];
$ci = 0;
foreach ($dataTren as $pegawai => $bulanData) {
    $nilaiBulan = [];
    for ($b=1; $b<=12; $b++) {
        $nilaiBulan[] = $bulanData[$b] ?? null;
    }
    $datasets[] = [
        "label" => $pegawai,
        "data"  => $nilaiBulan,
        "borderColor" => $colors[$ci % count($colors)],
        "tension" => 0.2,
        "fill" => false
    ];
    $ci++;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Pimpinan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- DataTables + Export -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
  <style>
    body { background-color: #f8f9fa; }
    .sidebar {
      height: 100vh; width: 250px; position: fixed; top: 0; left: 0;
      background: #343a40; padding-top: 20px;
    }
    .sidebar a {
      padding: 12px; text-decoration: none; font-size: 16px;
      color: #ddd; display: block;
    }
    .sidebar a:hover, .sidebar a.active {
      background: #495057; color: #fff;
    }
    .content { margin-left: 250px; padding: 20px; }
  </style>
</head>
<body>

<!-- Sidebar -->
<?php include "sidebar_pimpinan.php"; ?>

</div>

<!-- Content -->
<div class="content">
  <div class="container-fluid">
    <h3>Selamat Datang, <?= htmlspecialchars($_SESSION['username']) ?> 👋</h3>
    <p class="text-muted">Anda login sebagai <strong>Pimpinan</strong>.</p>

    <!-- Bar Chart Rata-rata Bulanan -->
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-body">
        <h5 class="card-title">📊 Rata-rata Nilai Pegawai per Bulan (<?= $tahun ?>)</h5>
        <canvas id="chartBar" height="120"></canvas>
      </div>
    </div>

    <!-- Line Chart Tren + Filter -->
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="card-title">📈 Tren Penilaian Pegawai Tahun <?= $tahun ?></h5>
          <select id="filterPegawai" class="form-select w-auto">
            <option value="all">Semua Pegawai</option>
            <?php foreach ($pegawaiList as $p): ?>
              <option value="<?= htmlspecialchars($p['nama_lengkap']) ?>">
                <?= htmlspecialchars($p['nama_lengkap']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <canvas id="chartLine" height="120"></canvas>
      </div>
    </div>

    <!-- Tabel Rekap dengan Export -->
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <h5 class="card-title mb-3">📑 Rekap Nilai Pegawai (<?= $tahun ?>)</h5>
        <table id="rekapTable" class="table table-bordered table-striped">
          <thead class="table-dark">
            <tr>
              <th>Nama Pegawai</th>
              <th>NIP</th>
              <th>Jabatan</th>
              <th>Rata-rata Nilai</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($rekapData as $r): ?>
              <tr>
                <td><?= htmlspecialchars($r['nama_lengkap']) ?></td>
                <td><?= htmlspecialchars($r['nip'] ?? '-') ?></td>
                <td><?= htmlspecialchars($r['jabatan'] ?? '-') ?></td>
                <td class="text-center"><?= number_format($r['rata2'] ?? 0,2) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<script>
// Data PHP
const labels = <?= json_encode($labels) ?>;
const allDatasets = <?= json_encode($datasets) ?>;

// Bar Chart
new Chart(document.getElementById('chartBar'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Rata-rata Nilai',
            data: <?= json_encode($barData) ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.7)'
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, max: 5 } }
    }
});

// Line Chart
const ctxLine = document.getElementById('chartLine');
let chartLine = new Chart(ctxLine, {
    type: 'line',
    data: { labels: labels, datasets: allDatasets },
    options: {
        responsive: true,
        interaction: { mode: 'nearest', intersect: false },
        plugins: { legend: { position: 'bottom' } },
        scales: { y: { beginAtZero: true, max: 5 } }
    }
});

// Filter pegawai
document.getElementById('filterPegawai').addEventListener('change', function() {
    const val = this.value;
    if (val === "all") {
        chartLine.data.datasets = allDatasets;
    } else {
        chartLine.data.datasets = allDatasets.filter(ds => ds.label === val);
    }
    chartLine.update();
});

// DataTables export
$(document).ready(function() {
    $('#rekapTable').DataTable({
        dom: 'Bfrtip',
        buttons: ['excelHtml5','pdfHtml5','print'],
        pageLength: 10
    });
});
</script>

</body>
</html>
