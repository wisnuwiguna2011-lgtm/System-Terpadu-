<?php
session_start();
include "config.php";

// Proteksi role kepegawaian
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kepegawaian') {
    header("Location: login.php");
    exit;
}

// Ambil semua pegawai
$result = $conn->query("SELECT * FROM pegawai ORDER BY nama_lengkap ASC");
$pegawaiList = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Ambil semua izin
$result = $conn->query("SELECT izin_pegawai.*, pegawai.nama_lengkap 
                        FROM izin_pegawai 
                        JOIN pegawai ON izin_pegawai.pegawai_id = pegawai.id 
                        ORDER BY izin_pegawai.created_at DESC");
$izinList = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Buat ringkasan izin per pegawai
$summary = [];
foreach ($izinList as $izin) {
    $pid = $izin['pegawai_id'];
    if (!isset($summary[$pid])) {
        $summary[$pid] = [
            'nama' => $izin['nama_lengkap'],
            'sakit' => 0,
            'cuti' => 0,
            'lupa_datang' => 0,
            'lupa_pulang' => 0,
            'surat_penugasan' => 0,
            'lain_lain' => 0,
            'detail' => []
        ];
    }

    // Hitung jenis izin
    switch ($izin['jenis']) {
        case 'sakit': $summary[$pid]['sakit']++; break;
        case 'cuti': $summary[$pid]['cuti']++; break;
        case 'lupa_isi_daftar_hadir_datang': $summary[$pid]['lupa_datang']++; break;
        case 'lupa_isi_daftar_hadir_pulang': $summary[$pid]['lupa_pulang']++; break;
        case 'surat_penugasan': $summary[$pid]['surat_penugasan']++; break;
        case 'lain_lain': $summary[$pid]['lain_lain']++; break;
    }

    // Simpan detail
    $summary[$pid]['detail'][] = $izin;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Ringkasan & Riwayat Izin - Kepegawaian</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background-color:#f8f9fa; }
.content { margin-left:250px; padding:20px; }
.card { border:none; border-radius:12px; box-shadow:0 2px 6px rgba(0,0,0,0.08); margin-bottom:1rem; }
.table thead { background:#e2e8f0; }
.table td, .table th { vertical-align: middle; }
.detail-row { display:none; background:#f1f3f5; }
.toggle-detail { cursor:pointer; color:#0d6efd; text-decoration:underline; }
</style>
</head>
<body>
<?php include "sidebar_kepegawaian.php"; ?>

<div class="content">
<h3 class="fw-bold mb-4"><i class="bi bi-calendar-check"></i> Ringkasan & Riwayat Izin Pegawai</h3>

<!-- Ringkasan & Detail -->
<div class="card">
<div class="card-header bg-dark text-white fw-bold"><i class="bi bi-file-earmark-text"></i> Ringkasan Izin Pegawai</div>
<div class="card-body">
<?php if($summary): ?>
<div class="table-responsive">
<table class="table table-hover align-middle">
<thead>
<tr>
<th>Pegawai</th>
<th>Sakit</th>
<th>Cuti</th>
<th>Lupa Datang</th>
<th>Lupa Pulang</th>
<th>Surat Penugasan</th>
<th>Lain-lain</th>
<th>Total</th>
</tr>
</thead>
<tbody>
<?php foreach($summary as $pid => $data): 
    $total = $data['sakit']+$data['cuti']+$data['lupa_datang']+$data['lupa_pulang']+$data['surat_penugasan']+$data['lain_lain'];
?>
<tr>
<td>
<span class="toggle-detail" data-id="<?= $pid ?>"><i class="bi bi-chevron-down"></i> <?= htmlspecialchars($data['nama']) ?></span>
</td>
<td><?= $data['sakit'] ?></td>
<td><?= $data['cuti'] ?></td>
<td><?= $data['lupa_datang'] ?></td>
<td><?= $data['lupa_pulang'] ?></td>
<td><?= $data['surat_penugasan'] ?></td>
<td><?= $data['lain_lain'] ?></td>
<td><?= $total ?></td>
</tr>

<!-- Detail tiap izin -->
<tr class="detail-row" id="detail-<?= $pid ?>">
<td colspan="8">
<table class="table table-bordered table-sm mb-0">
<thead>
<tr>
<th>Jenis</th>
<th>Mulai</th>
<th>Selesai</th>
<th>Keterangan</th>
<th>File</th>
</tr>
</thead>
<tbody>
<?php foreach($data['detail'] as $i): ?>
<tr>
<td><?= ucfirst(str_replace('_',' ',$i['jenis'])) ?></td>
<td><?= date("d-m-Y", strtotime($i['tanggal_mulai'])) ?></td>
<td><?= date("d-m-Y", strtotime($i['tanggal_selesai'])) ?></td>
<td><?= htmlspecialchars($i['keterangan'] ?: '-') ?></td>
<td>
<?php if ($i['file_surat']): ?>
<a href="uploads/izin/<?= htmlspecialchars($i['file_surat']) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-paperclip"></i> Lihat</a>
<?php else: ?>-<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</td>
</tr>

<?php endforeach; ?>
</tbody>
</table>
</div>
<?php else: ?><p class="text-muted">Belum ada pengajuan izin dari pegawai.</p><?php endif; ?>
</div>
</div>

</div>

<script>
document.querySelectorAll('.toggle-detail').forEach(el=>{
    el.addEventListener('click', ()=>{
        const id = el.getAttribute('data-id');
        const row = document.getElementById('detail-' + id);
        if(row.style.display === 'table-row'){
            row.style.display = 'none';
            el.querySelector('i').classList.replace('bi-chevron-up','bi-chevron-down');
        } else {
            row.style.display = 'table-row';
            el.querySelector('i').classList.replace('bi-chevron-down','bi-chevron-up');
        }
    });
});
</script>
</body>
</html>
