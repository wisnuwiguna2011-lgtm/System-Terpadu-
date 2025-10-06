<?php
session_start();
include "config.php";

// Proteksi role kepegawaian
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kepegawaian') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Daftar Pegawai</title>
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

.foto-thumb { width:42px; height:42px; object-fit:cover; border-radius:50%; }
.btn-sm { margin:1px; padding:3px 6px; font-size: 12px; }

.truncate {
  max-width: 160px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.text-left { text-align: left !important; }
.text-center { text-align: center !important; }
</style>
</head>
<body>

<?php include "sidebar_kepegawaian.php"; ?>

<div class="content">
  <h3 class="mb-3">👥 Daftar Pegawai</h3>
  <a href="data_pegawai.php" class="btn btn-primary mb-3">➕ Tambah Data Pegawai</a>

  <div class="card shadow-sm">
    <div class="card-body table-responsive">
      <table id="pegawaiTable" class="table table-bordered table-striped table-hover align-middle nowrap w-100">
        <thead class="table-dark">
          <tr>
            <th style="width:40px;">#</th>
            <th style="width:60px;">Foto</th>
            <th style="min-width:110px;">NIP</th>
            <th style="min-width:180px;">Nama</th>
            <th style="min-width:160px;">TTL</th>
            <th style="min-width:160px;">Jabatan</th>
            <th style="min-width:110px;">Pangkat/Gol</th>
            <th style="min-width:120px;">TMT Gol</th>
            <th style="min-width:150px;">Unit</th>
            <th style="min-width:180px;">Email</th>
            <th style="width:60px;">WA</th>
            <th style="min-width:120px;">File SK</th>
            <th style="width:80px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $no = 1;
          $res = $conn->query("SELECT * FROM pegawai ORDER BY nama_lengkap ASC");
          while($row = $res->fetch_assoc()):
          ?>
          <tr>
            <td class="text-center"><?= $no++ ?></td>
            <td class="text-center">
              <?php if(!empty($row['foto'])): ?>
                <img src="uploads/<?= htmlspecialchars($row['foto']) ?>" class="foto-thumb">
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
            <td class="text-center"><?= htmlspecialchars($row['nip']) ?></td>
            <td class="truncate text-left" title="<?= htmlspecialchars($row['nama_lengkap']) ?>">
              <?= htmlspecialchars($row['nama_lengkap']) ?>
            </td>
            <td class="text-left"><?= htmlspecialchars($row['tempat_lahir']).", ".htmlspecialchars($row['tgl_lahir']) ?></td>
            <td class="truncate text-left" title="<?= htmlspecialchars($row['jabatan']) ?>">
              <?= htmlspecialchars($row['jabatan']) ?>
            </td>
            <td class="text-center"><?= htmlspecialchars($row['pangkat_gol']) ?></td>
            <td class="text-center"><?= htmlspecialchars($row['tmt_gol']) ?></td>
            <td class="truncate text-left" title="<?= htmlspecialchars($row['unit_kerja']) ?>">
              <?= htmlspecialchars($row['unit_kerja']) ?>
            </td>
            <td class="truncate text-left" title="<?= htmlspecialchars($row['email']) ?>">
              <?= htmlspecialchars($row['email']) ?>
            </td>
            <td class="text-center">
              <?php if(!empty($row['no_whatsapp'])): ?>
                <a href="https://wa.me/<?= preg_replace('/\D/', '', $row['no_whatsapp']) ?>" target="_blank" class="btn btn-success btn-sm">
                  <i class="bi bi-whatsapp"></i>
                </a>
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <?php 
              $files = ['sk_cpns','sk_pns','sk_kgb'];
              foreach($files as $f){
                if(!empty($row[$f])){
                  echo '<a href="uploads/'.htmlspecialchars($row[$f]).'" target="_blank" class="btn btn-sm btn-info" title="'.strtoupper(str_replace('sk_','',$f)).'"><i class="bi bi-download"></i></a> ';
                }
              }
              ?>
            </td>
            <td class="text-center">
              <a href="edit_pegawai.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil"></i></a>
              <a href="hapus_pegawai.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus?')" title="Hapus"><i class="bi bi-trash"></i></a>
            </td>
          </tr>
          <?php endwhile; ?>
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
  $('#pegawaiTable').DataTable({
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
