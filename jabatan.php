<?php
session_start();
include "config.php";
include "sidebar_kepegawaian.php"; // Sidebar

// Proteksi role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kepegawaian') {
    header("Location: login.php");
    exit;
}

// Ambil daftar pegawai
$sql = "SELECT id, nip, nama_lengkap FROM pegawai ORDER BY nama_lengkap ASC";
$result = $conn->query($sql);
$pegawaiList = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Ambil data riwayat jabatan
$sql2 = "SELECT rj.*, p.nama_lengkap, p.nip 
         FROM riwayat_jabatan rj 
         JOIN pegawai p ON rj.pegawai_id = p.id 
         ORDER BY rj.tmt_jabatan DESC";
$result2 = $conn->query($sql2);
$riwayatList = $result2 ? $result2->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>📋 Riwayat Jabatan Pegawai</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
<link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.6.2/dist/select2-bootstrap4.min.css" rel="stylesheet">

<style>
body { background:#f5f7fa; font-family:"Segoe UI",sans-serif; }
.content { margin-left:250px; padding:20px; }

.table td, .table th { vertical-align: middle; font-size:14px; }
.table thead th { white-space: nowrap; text-align: center; }
.truncate { max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
</style>
</head>
<body>

<div class="content">
  <h3 class="mb-4">📋 Riwayat Jabatan Pegawai</h3>

  <!-- Form Tambah Jabatan -->
  <div class="card mb-4 shadow-sm">
    <div class="card-header bg-primary text-white">
      <i class="bi bi-person-plus"></i> Tambah Jabatan
    </div>
    <div class="card-body">
      <form id="formJabatan" action="proses_jabatan.php" method="POST" class="row g-3">

        <div class="col-md-6">
          <label class="form-label">Pegawai <span class="text-danger">*</span></label>
          <select name="pegawai_id" class="form-select select2" required>
            <option value="">-- Pilih Pegawai --</option>
            <?php foreach($pegawaiList as $pg): ?>
              <option value="<?= $pg['id'] ?>">
                <?= htmlspecialchars($pg['nama_lengkap'])." (".$pg['nip'].")" ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Nama Jabatan <span class="text-danger">*</span></label>
          <input type="text" name="jabatan" class="form-control" required>
        </div>

        <div class="col-md-6">
          <label class="form-label">Unit Kerja <span class="text-danger">*</span></label>
          <input type="text" name="unit_kerja" class="form-control" required>
        </div>

        <div class="col-md-3">
          <label class="form-label">TMT Jabatan <span class="text-danger">*</span></label>
          <input type="date" name="tmt_jabatan" class="form-control" required>
        </div>

        <div class="col-md-3">
          <label class="form-label">Sampai</label>
          <input type="date" name="sampai" class="form-control">
        </div>

        <div class="col-md-6">
          <label class="form-label">Nomor SK <span class="text-danger">*</span></label>
          <input type="text" name="no_sk" class="form-control" required>
        </div>

        <div class="col-md-3">
          <label class="form-label">Tanggal SK <span class="text-danger">*</span></label>
          <input type="date" name="tgl_sk" class="form-control" required>
        </div>

        <div class="col-md-3">
          <label class="form-label">Pejabat Penetap <span class="text-danger">*</span></label>
          <input type="text" name="pejabat_penetap" class="form-control" required>
        </div>

        <div class="col-md-12">
          <label class="form-label">Keterangan</label>
          <textarea name="keterangan" class="form-control" rows="2"></textarea>
        </div>

        <div class="col-12 text-end mt-3">
          <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Simpan Data</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Tabel Riwayat Jabatan -->
  <div class="card shadow-sm">
    <div class="card-body table-responsive">
      <table id="jabatanTable" class="table table-bordered table-striped table-hover nowrap w-100">
        <thead class="table-dark">
          <tr>
            <th>#</th>
            <th>NIP</th>
            <th>Nama</th>
            <th>Jabatan</th>
            <th>Unit Kerja</th>
            <th>TMT</th>
            <th>Sampai</th>
            <th>No SK</th>
            <th>Tgl SK</th>
            <th>Pejabat Penetap</th>
            <th>Keterangan</th>
          </tr>
        </thead>
        <tbody>
          <?php if(count($riwayatList)>0): ?>
            <?php foreach($riwayatList as $i=>$r): ?>
              <tr>
                <td class="text-center"><?= $i+1 ?></td>
                <td class="text-center"><?= htmlspecialchars($r['nip']) ?></td>
                <td class="truncate" title="<?= htmlspecialchars($r['nama_lengkap']) ?>"><?= htmlspecialchars($r['nama_lengkap']) ?></td>
                <td class="truncate"><?= htmlspecialchars($r['jabatan']) ?></td>
                <td class="truncate"><?= htmlspecialchars($r['unit_kerja']) ?></td>
                <td class="text-center"><?= $r['tmt_jabatan'] ?></td>
                <td class="text-center"><?= $r['sampai'] ?: '-' ?></td>
                <td class="truncate"><?= htmlspecialchars($r['no_sk']) ?></td>
                <td class="text-center"><?= $r['tgl_sk'] ?></td>
                <td class="truncate"><?= htmlspecialchars($r['pejabat_penetap']) ?></td>
                <td class="truncate"><?= htmlspecialchars($r['keterangan'] ?: '-') ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="11" class="text-center">Belum ada data jabatan</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- JS -->
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
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function(){
  // DataTables
  $('#jabatanTable').DataTable({
    responsive:true,
    dom: "<'row mb-3'<'col-md-6 d-flex align-items-center'B><'col-md-6 d-flex justify-content-end'f>>" +
         "<'row'<'col-sm-12'tr>>" +
         "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-end'p>>",
    buttons:[
      { extend:'excelHtml5', className:'btn btn-success me-2 btn-sm', text:'📊 Excel' },
      { extend:'pdfHtml5', className:'btn btn-danger me-2 btn-sm', text:'📄 PDF', orientation:'landscape', pageSize:'A4' },
      { extend:'print', className:'btn btn-secondary btn-sm', text:'🖨️ Print' }
    ],
    language:{ url:"//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" },
    autoWidth:false
  });

  // Select2
  $('.select2').select2({ theme: "bootstrap-5", placeholder: "-- Pilih Pegawai --" });

  // Validasi form
  $('#formJabatan').on('submit', function(e){
    let valid = true;
    $('#formJabatan [required]').each(function(){
      if($(this).val()===''){ valid=false; return false; }
    });
    if(!valid){ e.preventDefault(); alert("❌ Data wajib belum lengkap!"); }
  });
});
</script>
</body>
</html>
