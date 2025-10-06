<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id'])) exit;

$periode = $_GET['periode'] ?? date('Y-m');
$sql = "SELECT p.id, p.nama_lengkap, AVG(pb.skor) as rata2
        FROM pegawai p
        LEFT JOIN penilaian_batch pb ON p.id=pb.pegawai_id AND pb.periode='$periode'
        GROUP BY p.id ORDER BY p.nama_lengkap";
$res = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rekap Bulanan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">
  <h3>📊 Rekap Bulanan Penilaian Pegawai (<?= $periode ?>)</h3>
  <form method="get" class="row g-2 mb-3">
    <div class="col-md-3">
      <input type="month" name="periode" value="<?= $periode ?>" class="form-control">
    </div>
    <div class="col-md-2">
      <button class="btn btn-primary">Tampilkan</button>
    </div>
  </form>

  <table class="table table-bordered">
    <thead class="table-light">
      <tr><th>Pegawai</th><th>Rata-rata Skor</th><th>Aksi</th></tr>
    </thead>
    <tbody>
      <?php while($r=$res->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($r['nama_lengkap']) ?></td>
        <td><?= number_format($r['rata2'],2) ?></td>
        <td>
          <a href="rekap_penilaian.php?pegawai_id=<?= $r['id'] ?>&period=<?= $periode ?>" class="btn btn-sm btn-info">Detail</a>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</body>
</html>
