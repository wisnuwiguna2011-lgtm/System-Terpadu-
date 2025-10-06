<?php
session_start();
include "config.php";

// Proteksi role kepegawaian
if(!isset($_SESSION['role']) || $_SESSION['role']!=='kepegawaian'){
    header("Location: login.php");
    exit;
}

$success = $error = "";

// CSRF token
if(empty($_SESSION['csrf_token'])){
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// Helper aman
function esc($val){
    return htmlspecialchars($val ?? '');
}

// Proses tambah/update pangkat & golongan
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])){
        $error = "❌ Token CSRF tidak valid.";
    } else {
        $id = intval($_POST['id'] ?? 0);
        $pangkat = trim($_POST['pangkat_gol'] ?? '');
        $tmt_gol = $_POST['tmt_gol'] ?? '';

        if($id>0){
            $stmt = $conn->prepare("UPDATE pegawai SET pangkat_gol=?, tmt_gol=? WHERE id=?");
            $stmt->bind_param("ssi", $pangkat, $tmt_gol, $id);
            if($stmt->execute()) $success = "✅ Pangkat/Golongan berhasil diperbarui.";
            else $error = "❌ Gagal memperbarui: ".$stmt->error;
            $stmt->close();
        } else {
            $error = "❌ Data pegawai tidak valid.";
        }
    }
}

// Ambil daftar pegawai
$result = $conn->query("SELECT id, nip, nama_lengkap, pangkat_gol, tmt_gol, jabatan FROM pegawai ORDER BY nama_lengkap ASC");
$pegawaiList = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Pangkat & Golongan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
body{background:#f5f7fa;font-family:"Segoe UI",sans-serif;}
.content{margin-left:250px;padding:20px;}
.table td,.table th{vertical-align:middle;}
.card{margin-bottom:20px;}
</style>
</head>
<body>
<?php include "sidebar_kepegawaian.php"; ?>

<div class="content">
<h3>📊 Pangkat & Golongan Pegawai</h3>

<?php if($success): ?><div class="alert alert-success"><?= esc($success) ?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger"><?= esc($error) ?></div><?php endif; ?>

<div class="card shadow-sm p-3 mb-4">
<h5>Update Pangkat & Golongan</h5>
<form method="post" class="row g-3">
    <input type="hidden" name="csrf_token" value="<?= esc($csrf) ?>">
    <input type="hidden" name="id" id="pegawai_id">

    <div class="col-md-4">
        <label class="form-label">Nama Pegawai</label>
        <input type="text" class="form-control" id="nama_pegawai" readonly>
    </div>
    <div class="col-md-3">
        <label class="form-label">Pangkat / Golongan</label>
        <input type="text" name="pangkat_gol" id="pangkat_gol" class="form-control" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">TMT Golongan</label>
        <input type="date" name="tmt_gol" id="tmt_gol" class="form-control" required>
    </div>
    <div class="col-2 d-flex align-items-end">
        <button type="submit" class="btn btn-primary">💾 Simpan</button>
    </div>
</form>
</div>

<div class="card shadow-sm p-3">
<h5>Daftar Pegawai</h5>
<div class="table-responsive">
<table class="table table-bordered table-striped table-hover">
    <thead class="table-dark text-center">
        <tr>
            <th>#</th>
            <th>NIP</th>
            <th>Nama</th>
            <th>Jabatan</th>
            <th>Pangkat / Gol</th>
            <th>TMT Gol</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
    <?php if(count($pegawaiList)>0): ?>
        <?php foreach($pegawaiList as $i=>$peg): ?>
        <tr>
            <td><?= $i+1 ?></td>
            <td><?= esc($peg['nip']) ?></td>
            <td><?= esc($peg['nama_lengkap']) ?></td>
            <td><?= esc($peg['jabatan']) ?></td>
            <td><?= esc($peg['pangkat_gol']) ?></td>
            <td><?= esc($peg['tmt_gol']) ?></td>
            <td class="text-center">
                <button class="btn btn-sm btn-warning edit-btn"
                    data-id="<?= esc($peg['id']) ?>"
                    data-nama="<?= esc($peg['nama_lengkap']) ?>"
                    data-pangkat="<?= esc($peg['pangkat_gol']) ?>"
                    data-tmt="<?= esc($peg['tmt_gol']) ?>">
                    ✏️ Edit
                </button>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="7" class="text-center">Belum ada data pegawai.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// edit pangkat
document.querySelectorAll('.edit-btn').forEach(btn=>{
    btn.addEventListener('click', function(){
        document.getElementById('pegawai_id').value = this.dataset.id;
        document.getElementById('nama_pegawai').value = this.dataset.nama;
        document.getElementById('pangkat_gol').value = this.dataset.pangkat;
        document.getElementById('tmt_gol').value = this.dataset.tmt;
        window.scrollTo({top:0,behavior:'smooth'});
    });
});
</script>
</body>
</html>
