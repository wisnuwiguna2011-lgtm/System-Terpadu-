<?php
session_start();
include "config.php";

// Proteksi role kepegawaian
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kepegawaian') {
    header("Location: login.php");
    exit;
}

// Ambil daftar pegawai
$sql = "SELECT id, nip, nama_lengkap, unit_kerja, jabatan, pangkat_gol, tmt_gol FROM pegawai ORDER BY nama_lengkap ASC";
$result = $conn->query($sql);
$pegawaiList = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Hitung masa kerja
function hitungMasaKerja($tmt_gol){
    if(!$tmt_gol) return ['tahun'=>0,'bulan'=>0,'hari'=>0];
    $tgl_awal = new DateTime($tmt_gol);
    $tgl_sekarang = new DateTime();
    $diff = $tgl_awal->diff($tgl_sekarang);
    return ['tahun'=>$diff->y, 'bulan'=>$diff->m, 'hari'=>$diff->d];
}

// Daftar gaji pokok PNS
$gajiMap = [
    'Ia'=>1685700,'Ib'=>1738000,'Ic'=>1790000,'Id'=>1842000,'Ie'=>1894000,
    'IIa'=>2184000,'IIb'=>2240000,'IIc'=>2296000,'IId'=>2352000,'IIe'=>2408000,
    'IIIa'=>3270000,'IIIb'=>3350000,'IIIc'=>3430000,'IIId'=>3510000,'IIIe'=>3590000,
    'IVa'=>4350000,'IVb'=>4470000,'IVc'=>4590000,'IVd'=>4710000,'IVe'=>4830000
];

// Hitung gaji pokok otomatis
function gajiPokokOtomatis($pangkat_gol, $tmt_gol, $gajiMap){
    $masaKerja = hitungMasaKerja($tmt_gol);
    $gol = preg_replace('/[a-e]/i','',$pangkat_gol);
    $sub = strtolower(preg_replace('/[IV]+/','',$pangkat_gol));
    $subOrder = ['a','b','c','d','e'];
    $subIndex = array_search($sub,$subOrder);
    $naikTingkat = intdiv($masaKerja['tahun'],2);
    $newIndex = min($subIndex + $naikTingkat,count($subOrder)-1);
    $newPangkat = $gol.$subOrder[$newIndex];
    $gaji = $gajiMap[$newPangkat] ?? 0;
    return [$newPangkat, $gaji];
}

// Hitung tunjangan
function hitungTunjangan($golongan){
    $romawi = strtoupper(preg_replace('/[a-e]/','',$golongan));
    $tunjangan = ['kinerja'=>0,'makan'=>0,'umum'=>0];
    switch($romawi){
        case 'I': $tunjangan = ['kinerja'=>150000,'makan'=>35000,'umum'=>175000]; break;
        case 'II': $tunjangan = ['kinerja'=>200000,'makan'=>36000,'umum'=>180000]; break;
        case 'III': $tunjangan = ['kinerja'=>300000,'makan'=>37000,'umum'=>185000]; break;
        case 'IV': $tunjangan = ['kinerja'=>400000,'makan'=>41000,'umum'=>190000]; break;
    }
    return $tunjangan;
}

// Total gaji
function totalGaji($pokok,$tunj){
    return $pokok + $tunj['kinerja'] + $tunj['makan'] + $tunj['umum'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Data Gaji Pegawai</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
body { background:#f5f7fa; font-family:"Segoe UI",sans-serif; }
.content { margin-left:250px; padding:20px; }

.table td, .table th { vertical-align: middle; }
.table thead th { white-space: nowrap; text-align: center; }
.table td { font-size: 14px; }

.text-center { text-align: center !important; }
.text-end { text-align: right !important; }
</style>
</head>
<body>

<?php include "sidebar_kepegawaian.php"; ?>

<div class="content">
  <h3 class="mb-3">💰 Data Gaji Pegawai</h3>

  <div class="card shadow-sm">
    <div class="card-body table-responsive">
      <table id="gajiTable" class="table table-bordered table-striped table-hover align-middle nowrap w-100">
        <thead class="table-dark">
          <tr>
            <th>#</th>
            <th>NIP</th>
            <th>Nama</th>
            <th>Unit Kerja</th>
            <th>Jabatan</th>
            <th>Golongan Awal</th>
            <th>Masa Kerja</th>
            <th>Golongan Naik</th>
            <th>Gaji Pokok</th>
            <th>Tunj. Kinerja</th>
            <th>Tunj. Makan</th>
            <th>Tunj. Umum</th>
            <th>Total Gaji</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($pegawaiList as $i=>$p):
              $masaKerja = hitungMasaKerja($p['tmt_gol']);
              list($golNaik, $gajiPokok) = gajiPokokOtomatis($p['pangkat_gol'],$p['tmt_gol'],$gajiMap);
              $tunj = hitungTunjangan($p['pangkat_gol']);
              $total = totalGaji($gajiPokok,$tunj);
          ?>
          <tr>
            <td class="text-center"><?= $i+1 ?></td>
            <td class="text-center"><?= htmlspecialchars($p['nip']) ?></td>
            <td><?= htmlspecialchars($p['nama_lengkap']) ?></td>
            <td><?= htmlspecialchars($p['unit_kerja']) ?></td>
            <td><?= htmlspecialchars($p['jabatan']) ?></td>
            <td class="text-center"><?= htmlspecialchars($p['pangkat_gol']) ?></td>
            <td class="text-center"><?= "{$masaKerja['tahun']} Thn {$masaKerja['bulan']} Bln" ?></td>
            <td class="text-center"><?= $golNaik ?></td>
            <td class="text-end">Rp <?= number_format($gajiPokok,0,",",".") ?></td>
            <td class="text-end">Rp <?= number_format($tunj['kinerja'],0,",",".") ?></td>
            <td class="text-end">Rp <?= number_format($tunj['makan'],0,",",".") ?></td>
            <td class="text-end">Rp <?= number_format($tunj['umum'],0,",",".") ?></td>
            <td class="text-end fw-bold">Rp <?= number_format($total,0,",",".") ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<script>
$(document).ready(function(){
  $('#gajiTable').DataTable({
    responsive: true,
    dom: "<'row mb-3'<'col-md-6 d-flex align-items-center'B><'col-md-6 d-flex justify-content-end'f>>" +
         "<'row'<'col-sm-12'tr>>" +
         "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-end'p>>",
    buttons: [
      { extend:'excelHtml5', className:'btn btn-success me-2 btn-sm', text:'📊 Excel' },
      { extend:'pdfHtml5', className:'btn btn-danger me-2 btn-sm', text:'📄 PDF', orientation:'landscape', pageSize:'A4' },
      { extend:'print', className:'btn btn-secondary btn-sm', text:'🖨️ Print' }
    ],
    language:{ url:"//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" },
    autoWidth:false
  });
});
</script>
</body>
</html>
