<?php
session_start();
include "config.php";

// Proteksi role kepegawaian
if(!isset($_SESSION['role']) || $_SESSION['role']!=='kepegawaian'){
    header("Location: login.php");
    exit;
}

$success=$error="";

// Ambil semua riwayat
$riwayat=$conn->query("SELECT * FROM riwayat_kepegawaian ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

// CSRF token
if(empty($_SESSION['csrf_token'])){
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf=$_SESSION['csrf_token'];

// Proses hapus riwayat
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])){
        $error="❌ Token CSRF tidak valid.";
    } else {
        $action=$_POST['action'] ?? '';
        $id=intval($_POST['id'] ?? 0);
        if($action==='delete' && $id>0){
            $stmt=$conn->prepare("DELETE FROM riwayat_kepegawaian WHERE id=?");
            $stmt->bind_param("i",$id);
            if($stmt->execute()) $success="✅ Riwayat berhasil dihapus.";
            else $error="❌ Gagal menghapus: ".$stmt->error;
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Riwayat Kepegawaian</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<!-- DataTables -->
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">

<style>
body{font-family:'Segoe UI',sans-serif;background:#f5f7fa;}
.content{margin-left:250px;padding:20px;}
.table td,.table th{vertical-align:middle;}
.card{margin-bottom:20px;}
.table thead th{white-space:nowrap;text-align:center;}
</style>
</head>
<body>
<?php include "sidebar_kepegawaian.php"; ?>

<div class="content">
<h3>Riwayat Kepegawaian</h3>
<?php if($success) echo '<div class="alert alert-success">'.$success.'</div>'; ?>
<?php if($error) echo '<div class="alert alert-danger">'.$error.'</div>'; ?>

<!-- Tabel Riwayat -->
<div class="card shadow-sm p-3">
<div class="table-responsive">
<table id="riwayatTable" class="table table-bordered table-striped table-hover nowrap w-100">
<thead class="table-dark text-center">
<tr>
<th>#</th><th>NIP</th><th>Nama</th><th>Jabatan</th><th>Pangkat/Gol</th><th>TMT Gol</th>
<th>Unit Kerja</th><th>Status</th><th>Pendidikan</th><th>WhatsApp</th><th>Tanggal</th><th>Aksi</th>
</tr>
</thead>
<tbody>
<?php foreach($riwayat as $i=>$r): ?>
<tr>
<td><?= $i+1 ?></td>
<td><?= htmlspecialchars($r['nip'] ?? '') ?></td>
<td><?= htmlspecialchars($r['nama_lengkap'] ?? '') ?></td>
<td><?= htmlspecialchars($r['jabatan'] ?? '') ?></td>
<td><?= htmlspecialchars($r['pangkat_gol'] ?? '') ?></td>
<td><?= htmlspecialchars($r['tmt_gol'] ?? '') ?></td>
<td><?= htmlspecialchars($r['unit_kerja'] ?? '') ?></td>
<td><?= htmlspecialchars($r['status_keluarga'] ?? '') ?></td>
<td><?= htmlspecialchars($r['pendidikan'] ?? '') ?></td>
<td>
  <?php if(!empty($r['no_whatsapp'])): ?>
    <a href="https://wa.me/<?= htmlspecialchars($r['no_whatsapp']) ?>" target="_blank" class="btn btn-sm btn-success">💬 WA</a>
  <?php endif; ?>
</td>
<td><?= date("d-m-Y H:i",strtotime($r['created_at'])) ?></td>
<td class="text-center">
<form method="post" style="display:inline">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
<input type="hidden" name="id" value="<?= $r['id'] ?>">
<input type="hidden" name="action" value="delete">
<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Hapus riwayat ini?')">🗑️</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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
  $('#riwayatTable').DataTable({
    responsive:true,
    dom:"<'row mb-3'<'col-md-6 d-flex align-items-center'B><'col-md-6 d-flex justify-content-end'f>>" +
        "<'row'<'col-sm-12'tr>>" +
        "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-end'p>>",
    buttons:[
      {extend:'excelHtml5',className:'btn btn-success me-2 btn-sm',text:'📊 Excel'},
      {extend:'pdfHtml5',className:'btn btn-danger me-2 btn-sm',text:'📄 PDF',orientation:'landscape',pageSize:'A4'},
      {extend:'print',className:'btn btn-secondary btn-sm',text:'🖨️ Print'}
    ],
    language:{ url:"//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" },
    autoWidth:false
  });
});
</script>
</body>
</html>
