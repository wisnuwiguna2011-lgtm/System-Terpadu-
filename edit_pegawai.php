<?php
session_start();
include "config.php";

// Proteksi role kepegawaian
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kepegawaian') {
    header("Location: login.php");
    exit;
}

// Ambil ID
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: daftar_pegawai.php");
    exit;
}

// Ambil data pegawai
$stmt = $conn->prepare("SELECT * FROM pegawai WHERE id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$pegawai = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pegawai) {
    header("Location: daftar_pegawai.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Edit Pegawai</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { font-family:"Segoe UI", sans-serif; background:#f5f7fa; }
.content { margin-left:250px; padding:20px; }
.card-form { max-width:1100px; margin:30px auto; }
.card-form h4 { border-bottom:2px solid #eee; padding-bottom:10px; margin-bottom:20px; }
.foto-thumb { width:100px; height:100px; object-fit:cover; border-radius:10px; margin-top:5px; border:1px solid #ccc; }
.form-label { font-weight:600; }
</style>
</head>
<body>

<?php include "sidebar_kepegawaian.php"; ?>

<div class="content">
  <div class="card card-form shadow-sm p-4 bg-white">
    <h4><i class="bi bi-person-lines-fill"></i> Edit Data Pegawai</h4>

    <form method="post" action="update_pegawai.php" enctype="multipart/form-data">
      <input type="hidden" name="id" value="<?= $pegawai['id'] ?>">
      <input type="hidden" name="foto_lama" value="<?= htmlspecialchars($pegawai['foto']) ?>">

      <div class="row">
        <div class="col-md-6">
          <label class="form-label">NIP</label>
          <input type="text" name="nip" class="form-control" value="<?= htmlspecialchars($pegawai['nip']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Nama Lengkap</label>
          <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($pegawai['nama_lengkap']) ?>" required>
        </div>
      </div>

      <div class="row mt-2">
        <div class="col-md-6">
          <label class="form-label">Jabatan</label>
          <input type="text" name="jabatan" class="form-control" value="<?= htmlspecialchars($pegawai['jabatan']) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Unit Kerja</label>
          <input type="text" name="unit_kerja" class="form-control" value="<?= htmlspecialchars($pegawai['unit_kerja']) ?>">
        </div>
      </div>

      <div class="row mt-2">
        <div class="col-md-6">
          <label class="form-label">Tempat Lahir</label>
          <input type="text" name="tempat_lahir" class="form-control" value="<?= htmlspecialchars($pegawai['tempat_lahir']) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Tanggal Lahir</label>
          <input type="date" name="tgl_lahir" class="form-control" value="<?= htmlspecialchars($pegawai['tgl_lahir']) ?>">
        </div>
      </div>

      <div class="row mt-2">
        <div class="col-md-4">
          <label class="form-label">Pangkat / Golongan</label>
          <input type="text" name="pangkat_gol" class="form-control" value="<?= htmlspecialchars($pegawai['pangkat_gol']) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">TMT Pangkat</label>
          <input type="date" name="tmt_pangkat" class="form-control" value="<?= htmlspecialchars($pegawai['tmt_pangkat']) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">TMT Golongan</label>
          <input type="date" name="tmt_gol" class="form-control" value="<?= htmlspecialchars($pegawai['tmt_gol']) ?>">
        </div>
      </div>

      <div class="row mt-2">
        <div class="col-md-6">
          <label class="form-label">Pendidikan</label>
          <input type="text" name="pendidikan" class="form-control" value="<?= htmlspecialchars($pegawai['pendidikan']) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Status Keluarga</label>
          <input type="text" name="status_keluarga" class="form-control" value="<?= htmlspecialchars($pegawai['status_keluarga']) ?>">
        </div>
      </div>

      <div class="row mt-2">
        <div class="col-md-4">
          <label class="form-label">Gaji</label>
          <input type="text" name="gaji" class="form-control" value="<?= htmlspecialchars($pegawai['gaji']) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Masa Kerja (tahun)</label>
          <input type="number" name="masa_kerja" class="form-control" value="<?= htmlspecialchars($pegawai['masa_kerja']) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Tunjangan Kinerja</label>
          <input type="number" name="tunjangan_kinerja" class="form-control" value="<?= htmlspecialchars($pegawai['tunjangan_kinerja']) ?>">
        </div>
      </div>

      <div class="row mt-2">
        <div class="col-md-6">
          <label class="form-label">No WhatsApp</label>
          <input type="text" name="no_whatsapp" class="form-control" value="<?= htmlspecialchars($pegawai['no_whatsapp']) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($pegawai['email']) ?>">
        </div>
      </div>

      <div class="row mt-2">
        <div class="col-md-4">
          <label class="form-label">SKP</label>
          <input type="text" name="skp" class="form-control" value="<?= htmlspecialchars($pegawai['skp']) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Diklat</label>
          <input type="text" name="diklat" class="form-control" value="<?= htmlspecialchars($pegawai['diklat']) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Penghargaan</label>
          <input type="text" name="penghargaan" class="form-control" value="<?= htmlspecialchars($pegawai['penghargaan']) ?>">
        </div>
      </div>

      <div class="row mt-2">
        <div class="col-md-6">
          <label class="form-label">Foto Pegawai</label>
          <input type="file" name="foto" class="form-control">
          <?php if (!empty($pegawai['foto'])): ?>
            <img src="uploads/<?= htmlspecialchars($pegawai['foto']) ?>" class="foto-thumb mt-2">
          <?php endif; ?>
        </div>
      </div>

      <div class="d-flex justify-content-between mt-4">
        <a href="daftar_pegawai.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

</body>
</html>
