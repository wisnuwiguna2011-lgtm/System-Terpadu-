<?php
// --- Debugging PHP ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include "config.php";

// Proteksi role kepegawaian
if(!isset($_SESSION['role']) || $_SESSION['role']!=='kepegawaian'){
    header("Location: login.php");
    exit;
}

// Fungsi hitung tanggal kenaikan pangkat berikutnya (4 tahun sekali)
function tanggalPangkatBerikut($tmt_pangkat){
    if(empty($tmt_pangkat)) return null;
    try {
        $tgl_awal = new DateTime($tmt_pangkat);
    } catch (Exception $e){
        return null;
    }

    $tgl_sekarang = new DateTime();

    // Hitung selisih tahun, lompat mendekati tahun sekarang (4 tahunan)
    $diff = $tgl_awal->diff($tgl_sekarang);
    $tahun_selisih = floor($diff->y / 4) * 4;
    if($tahun_selisih > 0){
        $tgl_awal->modify("+{$tahun_selisih} years");
    }

    // Kalau masih <= sekarang, tambah 4 tahun lagi
    while($tgl_awal <= $tgl_sekarang){
        $tgl_awal->modify('+4 years');
    }

    return $tgl_awal;
}

// Ambil daftar pegawai
$sql = "SELECT id, nip, nama_lengkap, tmt_pangkat FROM pegawai ORDER BY nama_lengkap ASC";
$result = $conn->query($sql);
$pegawaiList = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$pegawaiUpdated = [];

// Proses reminder pangkat
foreach($pegawaiList as $p){
    if(empty($p['tmt_pangkat'])) {
        $pegawaiUpdated[] = $p;
        continue;
    }

    $tgl_awal = new DateTime($p['tmt_pangkat']);
    $tgl_pangkat = tanggalPangkatBerikut($p['tmt_pangkat']);
    if(!$tgl_pangkat){
        $pegawaiUpdated[] = $p;
        continue;
    }

    $today   = new DateTime();
    $selisih = $today->diff($tgl_pangkat);

    $pesan   = "Kenaikan pangkat berikutnya pada ".$tgl_pangkat->format('d-m-Y');

    if($tgl_pangkat < $today){
        $tipe_db     = 'jatuh_tempo';
        $status_text = "❌ Sudah lewat, segera perbaiki";
    } elseif($selisih->days <= 30){
        $tipe_db     = 'reminder';
        $status_text = "⚠️ Segera proses kenaikan pangkat";
    } else {
        $tipe_db     = 'reminder';
        $status_text = "✅ Normal";
    }

    // Simpan ke array
    $p['status_text']    = $status_text;
    $p['tgl_pangkat_last'] = $tgl_awal->format('d-m-Y');
    $p['tgl_pangkat_next'] = $tgl_pangkat->format('d-m-Y');
    $pegawaiUpdated[]    = $p;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Reminder Kenaikan Pangkat</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<style>
.content{margin-left:250px;padding:20px;}
</style>
</head>
<body>
<?php include "sidebar_kepegawaian.php"; ?>

<div class="content">
<h3>📌 Reminder Kenaikan Pangkat Pegawai</h3>

<div class="card shadow-sm p-3">
<table id="pangkatTable" class="table table-bordered table-striped table-hover w-100 align-middle">
    <thead class="table-dark text-center">
        <tr>
            <th>No</th>
            <th>NIP</th>
            <th>Nama</th>
            <th>TMT Pangkat</th>
            <th>Kenaikan Pangkat Terakhir</th>
            <th>Kenaikan Pangkat Berikutnya</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach($pegawaiUpdated as $i=>$p): ?>
    <tr class="text-center">
        <td><?= $i+1 ?></td>
        <td class="text-start"><?= htmlspecialchars($p['nip']) ?></td>
        <td class="text-start"><?= htmlspecialchars($p['nama_lengkap']) ?></td>
        <td><?= htmlspecialchars($p['tmt_pangkat'] ?? '-') ?></td>
        <td><?= $p['tgl_pangkat_last'] ?? '-' ?></td>
        <td><?= $p['tgl_pangkat_next'] ?? '-' ?></td>
        <td><?= $p['status_text'] ?? '-' ?></td>
        <td>
            <?php if(!empty($p['status_text']) && 
                (str_contains($p['status_text'],'Segera') || str_contains($p['status_text'],'Sudah'))): ?>
                <a href="proses_pangkat.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-primary">Proses Pangkat</a>
            <?php else: ?>
                -
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function(){
    $('#pangkatTable').DataTable({
        language:{url:"//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"},
        scrollX:false
    });
});
</script>
</body>
</html>
