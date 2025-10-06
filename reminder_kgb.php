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

// Fungsi hitung KGB (2 tahun sekali)
function hitungKGB($tmt_gol){
    if(empty($tmt_gol)) return [null,null];
    try {
        $tgl_awal = new DateTime($tmt_gol);
    } catch (Exception $e){
        return [null,null];
    }

    $today = new DateTime();

    // Tentukan KGB terakhir & berikutnya
    $tgl_kgb_last = clone $tgl_awal;
    while($tgl_kgb_last <= $today){
        $tgl_kgb_last->modify('+2 years');
    }
    $tgl_kgb_next = clone $tgl_kgb_last;
    $tgl_kgb_last->modify('-2 years');

    return [$tgl_kgb_last, $tgl_kgb_next];
}

// Ambil daftar pegawai
$sql = "SELECT id, nip, nama_lengkap, tmt_gol FROM pegawai ORDER BY nama_lengkap ASC";
$result = $conn->query($sql);
$pegawaiList = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$pegawaiUpdated = [];
foreach($pegawaiList as $p){
    if(empty($p['tmt_gol'])){
        $p['tgl_kgb_last'] = '-';
        $p['tgl_kgb_next'] = '-';
        $p['status_text']  = '-';
        $p['status_badge'] = 'secondary';
        $pegawaiUpdated[]  = $p;
        continue;
    }

    list($tgl_kgb_last, $tgl_kgb_next) = hitungKGB($p['tmt_gol']);
    if(!$tgl_kgb_last || !$tgl_kgb_next){
        $p['tgl_kgb_last'] = '-';
        $p['tgl_kgb_next'] = '-';
        $p['status_text']  = '-';
        $p['status_badge'] = 'secondary';
        $pegawaiUpdated[]  = $p;
        continue;
    }

    $today   = new DateTime();
    $selisih = $today->diff($tgl_kgb_next);

    // Tentukan status + warna badge
    if($tgl_kgb_next < $today){
        $status_text  = "❌ Sudah lewat, segera perbaiki";
        $status_badge = "danger";
    } elseif($selisih->days <= 30){
        $status_text  = "⚠️ Segera proses KGB";
        $status_badge = "warning";
    } else {
        $status_text  = "✅ Normal";
        $status_badge = "success";
    }

    $p['tgl_kgb_last'] = $tgl_kgb_last->format('d-m-Y');
    $p['tgl_kgb_next'] = $tgl_kgb_next->format('d-m-Y');
    $p['status_text']  = $status_text;
    $p['status_badge'] = $status_badge;

    $pegawaiUpdated[] = $p;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Reminder KGB Pegawai</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<style>
.content {margin-left:250px;padding:20px;}
.table td, .table th {vertical-align: middle;}
</style>
</head>
<body>
<?php include "sidebar_kepegawaian.php"; ?>

<div class="content">
<h3 class="mb-4">⏰ Reminder KGB Pegawai</h3>

<div class="card shadow-lg border-0">
    <div class="card-body">
        <div class="table-responsive">
        <table id="kgbTable" class="table table-striped table-hover table-bordered align-middle">
            <thead class="table-dark text-center">
                <tr>
                    <th>No</th>
                    <th>NIP</th>
                    <th>Nama</th>
                    <th>TMT Golongan</th>
                    <th>KGB Terakhir</th>
                    <th>KGB Berikutnya</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($pegawaiUpdated as $i=>$p): ?>
            <tr>
                <td class="text-center fw-bold"><?= $i+1 ?></td>
                <td><?= htmlspecialchars($p['nip']) ?></td>
                <td><?= htmlspecialchars($p['nama_lengkap']) ?></td>
                <td class="text-center"><?= htmlspecialchars($p['tmt_gol'] ?? '-') ?></td>
                <td class="text-center"><?= $p['tgl_kgb_last'] ?? '-' ?></td>
                <td class="text-center"><?= $p['tgl_kgb_next'] ?? '-' ?></td>
                <td class="text-center">
                    <span class="badge bg-<?= $p['status_badge'] ?> px-3 py-2">
                        <?= $p['status_text'] ?? '-' ?>
                    </span>
                </td>
                <td class="text-center">
                    <?php if(!empty($p['status_text']) && 
                        (str_contains($p['status_text'],'Segera') || str_contains($p['status_text'],'Sudah'))): ?>
                        <a href="proses_kgb.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-primary">
                            Proses KGB
                        </a>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function(){
    $('#kgbTable').DataTable({
        language:{url:"//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"},
        scrollX:false
    });
});
</script>
</body>
</html>
