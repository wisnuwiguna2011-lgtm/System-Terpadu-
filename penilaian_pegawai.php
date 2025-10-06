<?php
session_start();
include "config.php";

// Proteksi role pimpinan
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pimpinan') {
    header("Location: login.php");
    exit;
}

// Cari kolom aktivitas di log_harian
$kolomAktivitas = null;
$cekKolom = $conn->query("SHOW COLUMNS FROM log_harian");
$daftarKolom = [];
while ($c = $cekKolom->fetch_assoc()) {
    $daftarKolom[] = $c['Field'];
}
foreach (['aktivitas','kegiatan','uraian','catatan'] as $opsi) {
    if (in_array($opsi, $daftarKolom)) {
        $kolomAktivitas = $opsi;
        break;
    }
}
if (!$kolomAktivitas) {
    die("❌ Tidak ada kolom aktivitas/kegiatan/uraian/catatan di tabel log_harian.");
}

$tanggal = date("Y-m-d");
$tahun   = date("Y");

// Query Harian (otomatis)
$sqlHarian = "
    SELECT 
        p.id AS pegawai_id,
        p.nama_lengkap,
        p.nip,
        p.jabatan,
        COUNT(l.id) AS jumlah_log,
        IFNULL(AVG(CHAR_LENGTH(l.$kolomAktivitas)),0) AS rata_kata,
        SUM(CASE 
                WHEN l.$kolomAktivitas LIKE '%inovasi%' 
                  OR l.$kolomAktivitas LIKE '%ide%' 
                  OR l.$kolomAktivitas LIKE '%improve%' 
                THEN 1 ELSE 0 
            END) AS inovasi_count,
        ph.disiplin, ph.kuantitas, ph.kualitas, ph.inovasi
    FROM pegawai p
    LEFT JOIN log_harian l ON p.id = l.pegawai_id AND DATE(l.tanggal)=CURDATE()
    LEFT JOIN penilaian_harian ph ON p.id = ph.pegawai_id AND ph.tanggal=CURDATE()
    GROUP BY p.id
    ORDER BY p.nama_lengkap ASC
";
$resHarian = $conn->query($sqlHarian);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Penilaian Pegawai</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <style>
    body { background:#f8f9fa; }
    .content { margin-left:250px; padding:20px; }
    @media(max-width:768px){
      .content{ margin-left:0; }
    }
    input[type=number]{ width:70px; text-align:center; }
  </style>
</head>
<body>
<?php include "sidebar_pimpinan.php"; ?>

<div class="content">
  <div class="container-fluid">
    <h3 class="mb-3">📝 Penilaian Harian Pegawai</h3>
    <p><b>Tanggal:</b> <?= date("d-m-Y") ?></p>

    <div class="table-responsive">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-dark">
          <tr>
            <th>Nama</th><th>NIP</th><th>Jabatan</th>
            <th>Log</th><th>Rata rata</th><th>Ide</th>
            <th>Disiplin</th><th>Kuantitas</th><th>Kualitas</th><th>Inovasi</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php while($row=$resHarian->fetch_assoc()):
              // nilai otomatis
              $auto_disiplin  = ($row['jumlah_log']>=5?5:$row['jumlah_log']);
              $auto_kuantitas = ($row['jumlah_log']>=10?5:round($row['jumlah_log']/2));
              $auto_kualitas  = ($row['rata_kata']>=100?5:round($row['rata_kata']/20));
              $auto_inovasi   = ($row['inovasi_count']>=5?5:$row['inovasi_count']);

              // nilai final: manual kalau ada, kalau tidak pakai otomatis
              $disiplin  = $row['disiplin']  ?? $auto_disiplin;
              $kuantitas = $row['kuantitas'] ?? $auto_kuantitas;
              $kualitas  = $row['kualitas']  ?? $auto_kualitas;
              $inovasi   = $row['inovasi']   ?? $auto_inovasi;
          ?>
          <tr>
            <form method="post" action="simpan_penilaian.php">
              <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
              <td><?= htmlspecialchars($row['nip'] ?? '-') ?></td>
              <td><?= htmlspecialchars($row['jabatan'] ?? '-') ?></td>
              <td class="text-center"><?= $row['jumlah_log'] ?></td>
              <td class="text-center"><?= number_format($row['rata_kata'],0) ?></td>
              <td class="text-center"><?= $row['inovasi_count'] ?></td>

              <td><input type="number" name="disiplin" min="1" max="5" value="<?= $disiplin ?>"></td>
              <td><input type="number" name="kuantitas" min="1" max="5" value="<?= $kuantitas ?>"></td>
              <td><input type="number" name="kualitas" min="1" max="5" value="<?= $kualitas ?>"></td>
              <td><input type="number" name="inovasi" min="1" max="5" value="<?= $inovasi ?>"></td>

              <td>
                <input type="hidden" name="pegawai_id" value="<?= $row['pegawai_id'] ?>">
                <input type="hidden" name="tanggal" value="<?= $tanggal ?>">
                <button type="submit" class="btn btn-sm btn-success">
                  <i class="bi bi-save"></i>
                </button>
              </td>
            </form>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>
