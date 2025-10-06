<?php
session_start();
include "config.php";

// Proteksi login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pimpinan') {
    header("Location: login.php");
    exit;
}

// Ambil semua pegawai
$pegawai = $conn->query("SELECT id, nama_lengkap FROM pegawai ORDER BY nama_lengkap");

// Ambil semua kriteria
$kriteria = $conn->query("SELECT * FROM kriteria_penilaian ORDER BY id ASC");

// Periode default (bulan ini)
$periode = $_GET['periode'] ?? date('Y-m');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Penilaian Massal Pegawai</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  table th, table td { text-align:center; vertical-align:middle; }
  .sticky-header th { position:sticky; top:0; background:#f8f9fa; z-index:2; }
</style>
</head>
<body class="bg-light">
<div class="container-fluid py-4">
  <h3 class="mb-4">⚡ Penilaian Massal Pegawai (<?= $periode ?>)</h3>

  <!-- Filter Periode -->
  <form method="get" class="row g-2 mb-3">
    <div class="col-md-3">
      <input type="month" name="periode" value="<?= $periode ?>" class="form-control">
    </div>
    <div class="col-md-2">
      <button class="btn btn-primary">Tampilkan</button>
    </div>
  </form>

  <!-- Form Penilaian -->
  <form method="post" action="save_penilaian_massal.php">
    <input type="hidden" name="periode" value="<?= $periode ?>">

    <div class="table-responsive" style="max-height:70vh; overflow:auto;">
      <table class="table table-bordered table-hover align-middle">
        <thead class="sticky-header">
          <tr>
            <th>Pegawai</th>
            <?php while($k = $kriteria->fetch_assoc()): ?>
              <th><?= htmlspecialchars($k['nama_kriteria']) ?><br><small>(1-5)</small></th>
            <?php endwhile; ?>
            <th>Komentar</th>
          </tr>
        </thead>
        <tbody>
          <?php while($p = $pegawai->fetch_assoc()): ?>
            <tr>
              <td class="text-start"><?= htmlspecialchars($p['nama_lengkap']) ?></td>
              <?php
              // Reset pointer kriteria untuk looping lagi
              $kriteria2 = $conn->query("SELECT * FROM kriteria_penilaian ORDER BY id ASC");
              while($k2 = $kriteria2->fetch_assoc()): ?>
                <td>
                  <select name="nilai[<?= $p['id'] ?>][<?= $k2['id'] ?>]" class="form-select form-select-sm">
                    <option value="">-</option>
                    <?php for($i=1;$i<=5;$i++): ?>
                      <option value="<?= $i ?>"><?= $i ?></option>
                    <?php endfor; ?>
                  </select>
                </td>
              <?php endwhile; ?>
              <td>
                <input type="text" name="komentar[<?= $p['id'] ?>]" class="form-control form-control-sm" placeholder="Komentar umum">
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <button class="btn btn-success mt-3">💾 Simpan Semua Penilaian</button>
  </form>
</div>
</body>
</html>
