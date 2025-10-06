<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pimpinan') {
    header("Location: login.php");
    exit;
}

$tahun = $_POST['tahun'] ?? date("Y");
$bulan = $_POST['bulan'] ?? 0; // 0 = semua bulan

// =======================
// Ambil Rekap dari penilaian_harian
// =======================
if ($bulan > 0) {
    // Rekap bulanan
    $stmt = $conn->prepare("
        SELECT p.nama_lengkap, p.nip, p.jabatan,
               AVG(ph.disiplin) as disiplin,
               AVG(ph.kuantitas) as kuantitas,
               AVG(ph.kualitas) as kualitas,
               AVG(ph.inovasi) as inovasi
        FROM pegawai p
        LEFT JOIN penilaian_harian ph 
               ON p.id=ph.pegawai_id 
              AND YEAR(ph.tanggal)=? 
              AND MONTH(ph.tanggal)=?
        GROUP BY p.id
        ORDER BY p.nama_lengkap
    ");
    $stmt->bind_param("ii", $tahun, $bulan);
} else {
    // Rekap tahunan
    $stmt = $conn->prepare("
        SELECT p.nama_lengkap, p.nip, p.jabatan,
               AVG(ph.disiplin) as disiplin,
               AVG(ph.kuantitas) as kuantitas,
               AVG(ph.kualitas) as kualitas,
               AVG(ph.inovasi) as inovasi
        FROM pegawai p
        LEFT JOIN penilaian_harian ph 
               ON p.id=ph.pegawai_id 
              AND YEAR(ph.tanggal)=?
        GROUP BY p.id
        ORDER BY p.nama_lengkap
    ");
    $stmt->bind_param("i", $tahun);
}
$stmt->execute();
$res = $stmt->get_result();
$pegawaiList = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$labels = [
    "1"=>"Januari","2"=>"Februari","3"=>"Maret","4"=>"April","5"=>"Mei","6"=>"Juni",
    "7"=>"Juli","8"=>"Agustus","9"=>"September","10"=>"Oktober","11"=>"November","12"=>"Desember"
];

// =======================
// Export Excel
// =======================
if (isset($_POST['export_excel'])) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=rekap_penilaian_$tahun.xls");
    echo "Nama\tNIP\tJabatan\tDisiplin\tKuantitas\tKualitas\tInovasi\tRata-rata\n";
    foreach ($pegawaiList as $row) {
        $d = $row['disiplin'] ?? 0;
        $q = $row['kuantitas'] ?? 0;
        $k = $row['kualitas'] ?? 0;
        $i = $row['inovasi'] ?? 0;
        $avg = ($d+$q+$k+$i)/4;
        echo "{$row['nama_lengkap']}\t{$row['nip']}\t{$row['jabatan']}\t$d\t$q\t$k\t$i\t$avg\n";
    }
    exit;
}

// =======================
// Export PDF
// =======================
if (isset($_POST['export_pdf'])) {
    require_once("tcpdf/tcpdf.php");
    $pdf = new TCPDF();
    $pdf->AddPage();
    $pdf->SetFont("helvetica","",10);
    $html = "<h3>Rekap Penilaian Pegawai Tahun $tahun".($bulan?(" Bulan ".$labels[$bulan]):"")."</h3>";
    $html .= "<table border='1' cellpadding='4'>
                <tr style='font-weight:bold;'>
                    <td>Nama</td><td>NIP</td><td>Jabatan</td>
                    <td>Disiplin</td><td>Kuantitas</td><td>Kualitas</td><td>Inovasi</td><td>Rata-rata</td>
                </tr>";
    foreach ($pegawaiList as $row) {
        $d = $row['disiplin'] ?? 0;
        $q = $row['kuantitas'] ?? 0;
        $k = $row['kualitas'] ?? 0;
        $i = $row['inovasi'] ?? 0;
        $avg = ($d+$q+$k+$i)/4;
        $html .= "<tr>
                    <td>{$row['nama_lengkap']}</td>
                    <td>{$row['nip']}</td>
                    <td>{$row['jabatan']}</td>
                    <td>".number_format($d,1)."</td>
                    <td>".number_format($q,1)."</td>
                    <td>".number_format($k,1)."</td>
                    <td>".number_format($i,1)."</td>
                    <td>".number_format($avg,1)."</td>
                  </tr>";
    }
    $html .= "</table>";
    $pdf->writeHTML($html,true,false,true,false,"");
    $pdf->Output("rekap_penilaian_$tahun.pdf","D");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Rekap Penilaian Pegawai</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <style>
    body { background:#f8f9fa; }
    .content { margin-left:260px; padding:20px; }
  </style>
</head>
<body>
<?php include "sidebar_pimpinan.php"; ?>

<div class="content">
  <div class="container-fluid">
    <h3 class="mb-3">📊 Rekapitulasi Penilaian Pegawai</h3>

    <!-- Filter -->
    <form method="post" class="row g-2 mb-3">
      <div class="col-md-3">
        <select name="bulan" class="form-control">
          <option value="0" <?= ($bulan==0?'selected':'') ?>>-- Semua Bulan (Tahunan) --</option>
          <?php foreach($labels as $k=>$v): ?>
            <option value="<?= $k ?>" <?= ($bulan==$k?'selected':'') ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <input type="number" name="tahun" value="<?= $tahun ?>" class="form-control" required>
      </div>
      <div class="col-md-2">
        <button class="btn btn-primary">Tampilkan</button>
      </div>
      <div class="col-md-2">
        <button name="export_excel" class="btn btn-success"><i class="bi bi-file-earmark-excel"></i> Excel</button>
      </div>
      <div class="col-md-2">
        <button name="export_pdf" class="btn btn-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
      </div>
    </form>

    <!-- Tabel Rekap -->
    <div class="card shadow-sm border-0">
      <div class="card-body table-responsive">
        <table class="table table-bordered table-striped align-middle">
          <thead class="table-dark">
            <tr>
              <th>Nama</th>
              <th>NIP</th>
              <th>Jabatan</th>
              <th>Disiplin</th>
              <th>Kuantitas</th>
              <th>Kualitas</th>
              <th>Inovasi</th>
              <th>Rata-rata</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($pegawaiList as $row): 
                $d = $row['disiplin'] ?? 0;
                $q = $row['kuantitas'] ?? 0;
                $k = $row['kualitas'] ?? 0;
                $i = $row['inovasi'] ?? 0;
                $avg = ($d+$q+$k+$i)/4;
            ?>
              <tr>
                <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                <td><?= htmlspecialchars($row['nip'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['jabatan'] ?? '-') ?></td>
                <td><?= number_format($d,1) ?></td>
                <td><?= number_format($q,1) ?></td>
                <td><?= number_format($k,1) ?></td>
                <td><?= number_format($i,1) ?></td>
                <td class="fw-bold"><?= number_format($avg,1) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</body>
</html>
