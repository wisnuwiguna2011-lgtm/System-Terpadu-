<?php 
session_start();
include "config.php";

// Cek login & role pimpinan
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pimpinan') {
    header("Location: login.php");
    exit;
}

$tahun = $_POST['tahun'] ?? date("Y");
$bulan = $_POST['bulan'] ?? date("n");
$notif = "";

// ======================
// Generate Otomatis Bulanan dari Harian
// ======================
if (isset($_POST['generate'])) {
    $q = $conn->prepare("
        SELECT pegawai_id,
               AVG(disiplin)  AS disiplin,
               AVG(kuantitas) AS kuantitas,
               AVG(kualitas)  AS kualitas,
               AVG(inovasi)   AS inovasi
        FROM penilaian_harian
        WHERE YEAR(tanggal)=? AND MONTH(tanggal)=?
        GROUP BY pegawai_id
    ");
    $q->bind_param("ii", $tahun, $bulan);
    $q->execute();
    $result = $q->get_result();

    while ($row = $result->fetch_assoc()) {
        $pegawai_id = $row['pegawai_id'];
        $disiplin   = round($row['disiplin'],2);
        $kuantitas  = round($row['kuantitas'],2);
        $kualitas   = round($row['kualitas'],2);
        $inovasi    = round($row['inovasi'],2);

        $cek = $conn->prepare("SELECT id FROM penilaian_bulanan WHERE pegawai_id=? AND tahun=? AND bulan=?");
        $cek->bind_param("iii", $pegawai_id, $tahun, $bulan);
        $cek->execute();
        $res = $cek->get_result();

        if ($res->num_rows > 0) {
            $rowUpd = $res->fetch_assoc();
            $upd = $conn->prepare("UPDATE penilaian_bulanan 
                                   SET disiplin=?, kuantitas=?, kualitas=?, inovasi=? 
                                   WHERE id=?");
            $upd->bind_param("dddii", $disiplin, $kuantitas, $kualitas, $inovasi, $rowUpd['id']);
            $upd->execute();
            $upd->close();
        } else {
            $ins = $conn->prepare("INSERT INTO penilaian_bulanan 
                (pegawai_id, tahun, bulan, disiplin, kuantitas, kualitas, inovasi) 
                VALUES (?,?,?,?,?,?,?)");
            $ins->bind_param("iiiddd", $pegawai_id, $tahun, $bulan, $disiplin, $kuantitas, $kualitas, $inovasi);
            $ins->execute();
            $ins->close();
        }
        $cek->close();
    }
    $q->close();
    $notif = "<div class='alert alert-success'>✅ Data bulanan berhasil digenerate otomatis!</div>";
}

// ======================
// Ambil Data Pegawai + Nilai Bulanan
// ======================
$stmt = $conn->prepare("
    SELECT p.id, p.nama_lengkap, p.nip, p.jabatan,
           pb.disiplin, pb.kuantitas, pb.kualitas, pb.inovasi
    FROM pegawai p
    LEFT JOIN penilaian_bulanan pb 
           ON pb.pegawai_id = p.id AND pb.tahun=? AND pb.bulan=?
    ORDER BY p.nama_lengkap
");
$stmt->bind_param("ii", $tahun, $bulan);
$stmt->execute();
$res = $stmt->get_result();
$pegawaiList = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$labels = [
  "1"=>"Januari","2"=>"Februari","3"=>"Maret","4"=>"April","5"=>"Mei","6"=>"Juni",
  "7"=>"Juli","8"=>"Agustus","9"=>"September","10"=>"Oktober","11"=>"November","12"=>"Desember"
];

// ======================
// Export Excel
// ======================
if (isset($_POST['export_excel'])) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=penilaian_bulanan_{$bulan}_{$tahun}.xls");
    echo "Nama Pegawai\tNIP\tJabatan\tDisiplin\tKuantitas\tKualitas\tInovasi\tRata-rata\n";
    foreach ($pegawaiList as $p) {
        $avg = ($p['disiplin']+$p['kuantitas']+$p['kualitas']+$p['inovasi'])/4;
        echo "{$p['nama_lengkap']}\t{$p['nip']}\t{$p['jabatan']}\t{$p['disiplin']}\t{$p['kuantitas']}\t{$p['kualitas']}\t{$p['inovasi']}\t".number_format($avg,2)."\n";
    }
    exit;
}

// ======================
// Export PDF
// ======================
if (isset($_POST['export_pdf'])) {
    require("fpdf/fpdf.php");
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont("Arial","B",14);
    $pdf->Cell(0,10,"Rekap Penilaian Bulanan Pegawai",0,1,"C");
    $pdf->SetFont("Arial","",10);
    $pdf->Cell(0,10,"Bulan: ".$labels[$bulan]." ".$tahun,0,1,"C");

    // Header
    $pdf->SetFont("Arial","B",10);
    $pdf->Cell(45,8,"Nama Pegawai",1);
    $pdf->Cell(30,8,"NIP",1);
    $pdf->Cell(35,8,"Jabatan",1);
    $pdf->Cell(15,8,"Dis",1);
    $pdf->Cell(15,8,"Kuan",1);
    $pdf->Cell(15,8,"Kul",1);
    $pdf->Cell(15,8,"Ino",1);
    $pdf->Cell(20,8,"Rata2",1);
    $pdf->Ln();

    // Data
    $pdf->SetFont("Arial","",9);
    foreach ($pegawaiList as $p) {
        $avg = ($p['disiplin']+$p['kuantitas']+$p['kualitas']+$p['inovasi'])/4;
        $pdf->Cell(45,8,$p['nama_lengkap'],1);
        $pdf->Cell(30,8,$p['nip'],1);
        $pdf->Cell(35,8,$p['jabatan'],1);
        $pdf->Cell(15,8,$p['disiplin'],1,0,"C");
        $pdf->Cell(15,8,$p['kuantitas'],1,0,"C");
        $pdf->Cell(15,8,$p['kualitas'],1,0,"C");
        $pdf->Cell(15,8,$p['inovasi'],1,0,"C");
        $pdf->Cell(20,8,number_format($avg,2),1,0,"C");
        $pdf->Ln();
    }

    $pdf->Output("D","penilaian_bulanan_{$bulan}_{$tahun}.pdf");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Penilaian Bulanan Pegawai</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; }
    .content { margin-left: 260px; padding: 20px; }
  </style>
</head>
<body>

<?php include "sidebar_pimpinan.php"; ?>


<div class="content">
  <div class="container-fluid">
    <h3 class="mb-3">📆 Rekap Penilaian Bulanan Pegawai</h3>

    <?= $notif ?>

    <!-- Filter Bulan & Tahun -->
    <form method="post" class="row g-2 mb-3">
      <div class="col-md-3">
        <select name="bulan" class="form-control" required>
          <?php foreach($labels as $k=>$v): ?>
            <option value="<?= $k ?>" <?= ($bulan==$k?'selected':'') ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <input type="number" name="tahun" value="<?= $tahun ?>" class="form-control" required>
      </div>
      <div class="col-md-2">
        <button class="btn btn-primary" type="submit">Tampilkan</button>
      </div>
      <div class="col-md-3">
        <button class="btn btn-warning" type="submit" name="generate">⚡ Generate Otomatis</button>
      </div>
    </form>

    <!-- Export -->
    <form method="post" class="mb-3">
      <input type="hidden" name="bulan" value="<?= $bulan ?>">
      <input type="hidden" name="tahun" value="<?= $tahun ?>">
      <button class="btn btn-outline-success" name="export_excel">⬇️ Export Excel</button>
      <button class="btn btn-outline-danger" name="export_pdf">⬇️ Export PDF</button>
    </form>

    <!-- Tabel Data Bulanan -->
    <div class="card shadow-sm border-0">
      <div class="card-body table-responsive">
        <table class="table table-bordered align-middle">
          <thead class="table-dark">
            <tr>
              <th>Nama Pegawai</th>
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
            <?php foreach ($pegawaiList as $p): 
              $avg = ($p['disiplin']+$p['kuantitas']+$p['kualitas']+$p['inovasi'])/4;
            ?>
            <tr>
              <td><?= htmlspecialchars($p['nama_lengkap']) ?></td>
              <td><?= htmlspecialchars($p['nip'] ?? '-') ?></td>
              <td><?= htmlspecialchars($p['jabatan'] ?? '-') ?></td>
              <td><?= $p['disiplin'] ?? '-' ?></td>
              <td><?= $p['kuantitas'] ?? '-' ?></td>
              <td><?= $p['kualitas'] ?? '-' ?></td>
              <td><?= $p['inovasi'] ?? '-' ?></td>
              <td><?= number_format($avg,2) ?></td>
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
