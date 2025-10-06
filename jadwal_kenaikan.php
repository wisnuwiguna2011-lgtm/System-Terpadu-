<?php
session_start();
include "config.php";

// Proteksi role kepegawaian
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kepegawaian') {
    header("Location: login.php");
    exit;
}

// Ambil data pegawai dengan TMT terbaru
$sql = "SELECT p.id, p.nama_lengkap, d.tmt, d.jenis_dokumen
        FROM pegawai p
        LEFT JOIN (
            SELECT dp1.*
            FROM dokumen_pegawai dp1
            INNER JOIN (
                SELECT pegawai_id, MAX(tmt) as max_tmt 
                FROM dokumen_pegawai 
                GROUP BY pegawai_id
            ) dp2 ON dp1.pegawai_id = dp2.pegawai_id AND dp1.tmt = dp2.max_tmt
        ) d ON p.id = d.pegawai_id
        ORDER BY p.nama_lengkap ASC";
$result = $conn->query($sql);

$jadwal = [];
$today = new DateTime();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['tmt'])) {
            $tmt_date = new DateTime($row['tmt']);
            $gaji_berkala = (clone $tmt_date)->modify("+2 years");
            $kenaikan_pangkat = (clone $tmt_date)->modify("+4 years");

            $row['gaji_berkala'] = $gaji_berkala->format("d-m-Y");
            $row['kenaikan_pangkat'] = $kenaikan_pangkat->format("d-m-Y");

            // Reminder & jatuh tempo (disimpan ke notifikasi)
            // ... kode reminder sama seperti sebelumnya ...
        } else {
            $row['gaji_berkala'] = "-";
            $row['kenaikan_pangkat'] = "-";
        }
        $jadwal[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Jadwal Kenaikan Pegawai</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; }
    .content { margin-left:250px; padding:20px; }
    .table th { background: #2c3e50; color: #fff; }
  </style>
</head>
<body>

<?php include "sidebar_kepegawaian.php"; ?>

<div class="content">
  <h3><i class="bi bi-calendar-check"></i> Jadwal Kenaikan Pegawai</h3>
  <hr>

  <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
      <thead>
        <tr>
          <th>No</th>
          <th>Nama Pegawai</th>
          <th>Jenis Dokumen</th>
          <th>TMT</th>
          <th>Kenaikan Gaji Berkala (2 Tahun)</th>
          <th>Kenaikan Pangkat (4 Tahun)</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($jadwal) > 0): ?>
          <?php foreach ($jadwal as $i => $row): ?>
            <tr>
              <td><?= $i+1 ?></td>
              <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
              <td><?= htmlspecialchars($row['jenis_dokumen'] ?? '-') ?></td>
              <td><?= $row['tmt'] ? date("d-m-Y", strtotime($row['tmt'])) : "-" ?></td>
              <td><?= $row['gaji_berkala'] ?></td>
              <td><?= $row['kenaikan_pangkat'] ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="6" class="text-center text-muted">Belum ada data TMT pegawai.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <a href="dashboard_kepegawaian.php" class="btn btn-secondary">
    <i class="bi bi-arrow-left"></i> Kembali
  </a>
</div>

</body>
</html>
