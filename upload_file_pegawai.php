<?php
session_start();
include "config.php";

// Proteksi khusus role kepegawaian
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kepegawaian') {
    header("Location: login.php");
    exit;
}

// Ambil daftar pegawai
$query = $conn->query("SELECT id, nama_lengkap FROM pegawai ORDER BY nama_lengkap ASC");
$pegawai = $query ? $query->fetch_all(MYSQLI_ASSOC) : [];

// Jika form disubmit
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pegawai_id     = intval($_POST['pegawai_id']);
    $jenis_dokumen  = $_POST['jenis_dokumen'];
    $no_surat       = trim($_POST['no_surat']);
    $tanggal_surat  = !empty($_POST['tanggal_surat']) ? $_POST['tanggal_surat'] : null;
    $tmt            = !empty($_POST['tmt']) ? $_POST['tmt'] : null;

    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $allowed = ['pdf','jpg','jpeg','png'];
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $dir = __DIR__ . "/uploads/pegawai/$pegawai_id/";
            if (!is_dir($dir)) mkdir($dir, 0777, true);

            $filename = $jenis_dokumen . "_" . time() . "." . $ext;
            $path = $dir . $filename;

            if (move_uploaded_file($_FILES['file']['tmp_name'], $path)) {
                // Simpan ke database
                $stmt = $conn->prepare("INSERT INTO dokumen_pegawai 
                    (pegawai_id, jenis_dokumen, no_surat, tanggal_surat, tmt, file_path, uploaded_at) 
                    VALUES (?,?,?,?,?,?,NOW())");
                $db_path = "uploads/pegawai/$pegawai_id/$filename";
                $stmt->bind_param("isssss", $pegawai_id, $jenis_dokumen, $no_surat, $tanggal_surat, $tmt, $db_path);
                $stmt->execute();
                $stmt->close();

                $msg = "<div class='alert alert-success'>✅ Dokumen berhasil diupload!</div>";
            } else {
                $msg = "<div class='alert alert-danger'>❌ Gagal upload file.</div>";
            }
        } else {
            $msg = "<div class='alert alert-warning'>⚠️ Hanya file PDF/JPG/PNG yang diperbolehkan.</div>";
        }
    } else {
        $msg = "<div class='alert alert-danger'>⚠️ Tidak ada file yang diupload.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Upload Dokumen Pegawai</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background:#f5f7fa; }
    .content { margin-left:250px; padding:20px; }
  </style>
</head>
<body>

<?php include "sidebar_kepegawaian.php"; ?>

<div class="content">
  <h3><i class="bi bi-upload"></i> Upload Dokumen Pegawai</h3>
  <hr>

  <?= $msg ?>

  <form method="post" enctype="multipart/form-data" class="card p-3 shadow-sm">
    <div class="mb-3">
      <label class="form-label">Pilih Pegawai</label>
      <select name="pegawai_id" class="form-select" required>
        <option value="">-- Pilih Pegawai --</option>
        <?php foreach($pegawai as $p): ?>
          <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama_lengkap']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Jenis Dokumen</label>
      <select name="jenis_dokumen" class="form-select" required>
        <option value="sk">SK</option>
        <option value="gaji_berkala">Gaji Berkala (2 Tahun)</option>
        <option value="kenaikan_golongan">Kenaikan Golongan (4 Tahun)</option>
        <option value="lainnya">Lainnya</option>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">No Surat</label>
      <input type="text" name="no_surat" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Tanggal Surat</label>
      <input type="date" name="tanggal_surat" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">TMT (Terhitung Mulai Tanggal)</label>
      <input type="date" name="tmt" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Upload File</label>
      <input type="file" name="file" class="form-control" required>
      <small class="text-muted">Format: PDF, JPG, PNG</small>
    </div>
    <button type="submit" class="btn btn-primary"><i class="bi bi-cloud-upload"></i> Upload</button>
    <a href="dashboard_kepegawaian.php" class="btn btn-secondary">Kembali</a>
  </form>
</div>

</body>
</html>
