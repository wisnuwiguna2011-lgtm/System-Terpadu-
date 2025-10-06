<?php
session_start();
include "config.php";

// Proteksi role kepegawaian
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kepegawaian') {
    header("Location: login.php");
    exit;
}

// CSRF token
if(empty($_SESSION['csrf_token'])){
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// Pesan sukses/error
$success = $error = "";

// Proses simpan
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])){
        $error = "❌ Token CSRF tidak valid.";
    } else {
        $id = intval($_POST['id'] ?? 0);
        $nip = trim($_POST['nip'] ?? '');
        $nama = trim($_POST['nama_lengkap'] ?? '');
        $unit_kerja = trim($_POST['unit_kerja'] ?? '');
        $jabatan = trim($_POST['jabatan'] ?? '');
        $golongan = $_POST['golongan'] ?? '';
        $subgolongan = $_POST['subgolongan'] ?? '';
        $tmt_gol = !empty($_POST['tmt_gol']) ? $_POST['tmt_gol'] : null;
        $no_whatsapp = trim($_POST['no_whatsapp'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $tempat = trim($_POST['tempat_lahir'] ?? '');
        $tgl_lahir = !empty($_POST['tgl_lahir']) ? $_POST['tgl_lahir'] : null;
        $pendidikan = trim($_POST['pendidikan'] ?? '');
        $status_keluarga = trim($_POST['status_keluarga'] ?? '');
        $pangkat_gol = $golongan . strtolower($subgolongan);

        if($nip === '' || $nama === '' || $tmt_gol === null){
            $error = "❌ NIP, Nama, dan TMT Golongan wajib diisi.";
        } else {
            if($id > 0){
                // Update pegawai
                $stmt = $conn->prepare("UPDATE pegawai 
                    SET nip=?, nama_lengkap=?, tempat_lahir=?, tgl_lahir=?, pendidikan=?, status_keluarga=?, unit_kerja=?, jabatan=?, pangkat_gol=?, tmt_gol=?, no_whatsapp=?, email=? 
                    WHERE id=?");
                if(!$stmt) die("Prepare gagal: ".$conn->error);
                $stmt->bind_param("ssssssssssssi",
                    $nip,
                    $nama,
                    $tempat ?: null,
                    $tgl_lahir ?: null,
                    $pendidikan ?: null,
                    $status_keluarga ?: null,
                    $unit_kerja ?: null,
                    $jabatan ?: null,
                    $pangkat_gol ?: null,
                    $tmt_gol ?: null,
                    $no_whatsapp ?: null,
                    $email ?: null,
                    $id
                );
                $stmt->execute() ? $success="✅ Data pegawai berhasil diperbarui." : $error="❌ Gagal update: ".$stmt->error;
                $stmt->close();
            } else {
                // Tambah baru
                $stmt_check = $conn->prepare("SELECT id FROM users WHERE username=?");
                $stmt_check->bind_param("s", $nip);
                $stmt_check->execute();
                $stmt_check->store_result();
                if($stmt_check->num_rows > 0){
                    $error = "❌ NIP ini sudah memiliki akun login.";
                    $stmt_check->close();
                } else {
                    $stmt_check->close();
                    $default_pass = password_hash($nip, PASSWORD_DEFAULT);
                    $role = 'pegawai';
                    $stmt_user = $conn->prepare("INSERT INTO users (username,password,role,status,created_at) VALUES (?,?,?,'approved',NOW())");
                    $stmt_user->bind_param("sss",$nip,$default_pass,$role);
                    $stmt_user->execute();
                    $user_id = $stmt_user->insert_id;
                    $stmt_user->close();

                    $stmt = $conn->prepare("INSERT INTO pegawai 
                        (user_id,nip,nama_lengkap,tempat_lahir,tgl_lahir,pendidikan,status_keluarga,unit_kerja,jabatan,pangkat_gol,tmt_gol,no_whatsapp,email,created_at)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())");
                    $stmt->bind_param("issssssssssss",
                        $user_id,
                        $nip,
                        $nama,
                        $tempat ?: null,
                        $tgl_lahir ?: null,
                        $pendidikan ?: null,
                        $status_keluarga ?: null,
                        $unit_kerja ?: null,
                        $jabatan ?: null,
                        $pangkat_gol ?: null,
                        $tmt_gol ?: null,
                        $no_whatsapp ?: null,
                        $email ?: null
                    );
                    $stmt->execute() ? $success="✅ Data pegawai dan akun login berhasil dibuat. Username/Password = NIP" : $error="❌ Gagal tambah: ".$stmt->error;
                    $stmt->close();
                }
            }
        }
    }
}

// Ambil data pegawai
$result = $conn->query("SELECT * FROM pegawai ORDER BY nama_lengkap ASC");
$pegawaiList = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Data Pegawai</title>
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
.truncate { max-width: 160px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.text-left { text-align: left !important; }
.text-center { text-align: center !important; }
</style>
</head>
<body>
<?php include "sidebar_kepegawaian.php"; ?>

<div class="content">
  <h3 class="mb-3">👤 Data Pegawai</h3>
  <?php if($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <!-- Form Input -->
  <div class="card p-3 shadow-sm mb-4">
    <h5>Tambah / Edit Pegawai</h5>
    <form method="post" class="row g-3">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="id" id="pegawai_id">

      <div class="col-md-3">
        <label class="form-label">NIP</label>
        <input type="text" name="nip" id="nip" class="form-control" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Nama Lengkap</label>
        <input type="text" name="nama_lengkap" id="nama_lengkap" class="form-control" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">No WhatsApp</label>
        <input type="text" name="no_whatsapp" id="no_whatsapp" class="form-control" placeholder="628xxxxxxx">
      </div>
      <div class="col-md-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" id="email" class="form-control">
      </div>

      <div class="col-md-3">
        <label class="form-label">Tempat Lahir</label>
        <input type="text" name="tempat_lahir" id="tempat_lahir" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label">Tanggal Lahir</label>
        <input type="date" name="tgl_lahir" id="tgl_lahir" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label">Pendidikan</label>
        <input type="text" name="pendidikan" id="pendidikan" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label">Status Keluarga</label>
        <input type="text" name="status_keluarga" id="status_keluarga" class="form-control">
      </div>

      <div class="col-md-3">
        <label class="form-label">Unit Kerja</label>
        <input type="text" name="unit_kerja" id="unit_kerja" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label">Jabatan</label>
        <input type="text" name="jabatan" id="jabatan" class="form-control">
      </div>
      <div class="col-md-2">
        <label class="form-label">Golongan</label>
        <select name="golongan" id="golongan" class="form-select" required>
          <option value="">--</option><option>I</option><option>II</option><option>III</option><option>IV</option>
        </select>
      </div>
      <div class="col-md-1">
        <label class="form-label">Sub</label>
        <select name="subgolongan" id="subgolongan" class="form-select" required>
          <option value="">--</option><option>a</option><option>b</option><option>c</option><option>d</option><option>e</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">TMT Golongan</label>
        <input type="date" name="tmt_gol" id="tmt_gol" class="form-control" required>
      </div>

      <div class="col-12">
        <button type="submit" class="btn btn-primary">💾 Simpan</button>
      </div>
    </form>
  </div>

  <!-- Tabel Data Pegawai -->
  <div class="card shadow-sm">
    <div class="card-body table-responsive">
      <table id="pegawaiTable" class="table table-bordered table-striped table-hover align-middle nowrap w-100">
        <thead class="table-dark">
          <tr>
            <th>#</th><th>NIP</th><th>Nama</th><th>Email</th>
            <th>Unit Kerja</th><th>Jabatan</th><th>Golongan</th><th>TMT Gol</th><th>Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php $no=1; foreach($pegawaiList as $peg): ?>
          <tr>
            <td class="text-center"><?= $no++ ?></td>
            <td><?= htmlspecialchars($peg['nip']) ?></td>
            <td><?= htmlspecialchars($peg['nama_lengkap']) ?></td>
            <td><?= htmlspecialchars($peg['email']) ?></td>
            <td><?= htmlspecialchars($peg['unit_kerja']) ?></td>
            <td><?= htmlspecialchars($peg['jabatan']) ?></td>
            <td class="text-center"><?= htmlspecialchars($peg['pangkat_gol']) ?></td>
            <td class="text-center"><?= htmlspecialchars($peg['tmt_gol']) ?></td>
            <td class="text-center">
              <button class="btn btn-sm btn-warning edit-btn"
                      data-id="<?= $peg['id'] ?>"
                      data-nip="<?= htmlspecialchars($peg['nip']) ?>"
                      data-nama="<?= htmlspecialchars($peg['nama_lengkap']) ?>"
                      data-email="<?= htmlspecialchars($peg['email']) ?>"
                      data-unit="<?= htmlspecialchars($peg['unit_kerja']) ?>"
                      data-jabatan="<?= htmlspecialchars($peg['jabatan']) ?>"
                      data-gol="<?= htmlspecialchars(substr($peg['pangkat_gol'],0,-1)) ?>"
                      data-sub="<?= htmlspecialchars(substr($peg['pangkat_gol'],-1)) ?>"
                      data-tmt="<?= $peg['tmt_gol'] ?>">
                ✏️ Edit
              </button>
            </td>
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

  // Isi form saat klik edit
  $(document).on('click','.edit-btn',function(){
    $('#pegawai_id').val($(this).data('id'));
    $('#nip').val($(this).data('nip'));
    $('#nama_lengkap').val($(this).data('nama'));
    $('#email').val($(this).data('email'));
    $('#unit_kerja').val($(this).data('unit'));
    $('#jabatan').val($(this).data('jabatan'));
    $('#golongan').val($(this).data('gol'));
    $('#subgolongan').val($(this).data('sub'));
    $('#tmt_gol').val($(this).data('tmt'));
  });

  // Reset form jika berhasil tambah
  <?php if($success): ?>
      $('form')[0].reset();
      $('#pegawai_id').val('');
  <?php endif; ?>
});
</script>
</body>
</html>
