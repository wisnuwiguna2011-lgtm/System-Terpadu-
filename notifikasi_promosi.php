<?php
session_start();
include "config.php";
include "sidebar_kepegawaian.php"; // Sidebar

// Proteksi role kepegawaian
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kepegawaian') {
    header("Location: login.php");
    exit;
}

// Ambil daftar pegawai
$sql = "SELECT id, nip, nama_lengkap FROM pegawai ORDER BY nama_lengkap ASC";
$result = $conn->query($sql);
$pegawaiList = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Tambah notifikasi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pegawai_id = $_POST['pegawai_id'] ?? '';
    $pesan = trim($_POST['pesan']);

    if (empty($pesan)) {
        $error = "❌ Pesan notifikasi wajib diisi!";
    } else {
        try {
            $status = 'baru';
            $tipe   = 'promosi'; // pastikan enum 'promosi' sudah ada di tabel notifikasi

            if ($pegawai_id === 'all') {
                foreach ($pegawaiList as $p) {
                    $stmt = $conn->prepare("INSERT INTO notifikasi (pegawai_id,pesan,status,tipe) VALUES (?,?,?,?)");
                    $stmt->bind_param("isss", $p['id'], $pesan, $status, $tipe);
                    $stmt->execute();
                    $stmt->close();
                }
                $success = "✅ Notifikasi berhasil dikirim ke semua pegawai.";
            } else {
                $stmt = $conn->prepare("INSERT INTO notifikasi (pegawai_id,pesan,status,tipe) VALUES (?,?,?,?)");
                $stmt->bind_param("isss", $pegawai_id, $pesan, $status, $tipe);
                $stmt->execute();
                $stmt->close();
                $success = "✅ Notifikasi berhasil dikirim ke pegawai.";
            }
        } catch (Exception $e) {
            $error = "❌ Terjadi kesalahan saat mengirim notifikasi: " . $e->getMessage();
        }
    }
}

// Ambil daftar notifikasi terbaru (tipe promosi)
$sql2 = "SELECT n.*, p.nama_lengkap, p.nip 
         FROM notifikasi n 
         LEFT JOIN pegawai p ON n.pegawai_id = p.id
         WHERE n.tipe='promosi'
         ORDER BY n.created_at DESC LIMIT 50";
$result2 = $conn->query($sql2);
$notifList = $result2 ? $result2->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>🔔 Notifikasi Promosi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.6.2/dist/select2-bootstrap4.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
.content { margin-left:250px; padding:20px; }
.select2-container--bootstrap-5 .select2-selection { height: calc(1.5em + 0.75rem + 2px); padding:0.375rem 0.75rem; }
</style>
</head>
<body>

<div class="content">
<h3 class="mb-4">🔔 Notifikasi Promosi / Kenaikan Pangkat</h3>

<?php if(isset($error)): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if(isset($success)): ?>
<div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<!-- Form Kirim Notifikasi -->
<div class="card mb-4 shadow-sm">
  <div class="card-header bg-primary text-white">➕ Tambah Notifikasi</div>
  <div class="card-body">
    <form method="POST">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Kepada</label>
          <select name="pegawai_id" class="form-select select2" required>
            <option value="">-- Pilih Pegawai --</option>
            <option value="all">Semua Pegawai</option>
            <?php foreach($pegawaiList as $pg): ?>
            <option value="<?= $pg['id'] ?>"><?= htmlspecialchars($pg['nama_lengkap'])." (".$pg['nip'].")" ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Pesan Notifikasi <span class="text-danger">*</span></label>
          <input type="text" name="pesan" class="form-control" placeholder="Contoh: Kenaikan pangkat Bapak/Ibu..." required>
        </div>
      </div>

      <div class="mt-3 text-end">
        <button type="submit" class="btn btn-success"><i class="bi bi-send-fill"></i> Kirim Notifikasi</button>
      </div>
    </form>
  </div>
</div>

<!-- Tabel Notifikasi -->
<div class="card shadow-sm">
  <div class="card-header bg-dark text-white">📑 Daftar Notifikasi Terakhir</div>
  <div class="card-body table-responsive">
    <table class="table table-bordered table-striped table-hover align-middle">
      <thead class="table-dark text-center">
        <tr>
          <th>No</th>
          <th>Pegawai</th>
          <th>Pesan</th>
          <th>Status</th>
          <th>Waktu Dibuat</th>
        </tr>
      </thead>
      <tbody>
        <?php if(count($notifList) > 0): ?>
          <?php foreach($notifList as $i => $n): ?>
          <tr>
            <td class="text-center"><?= $i+1 ?></td>
            <td><?= htmlspecialchars($n['nama_lengkap'].' ('.$n['nip'].')') ?></td>
            <td><?= htmlspecialchars($n['pesan']) ?></td>
            <td class="text-center"><?= htmlspecialchars($n['status']) ?></td>
            <td class="text-center"><?= $n['created_at'] ?></td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="5" class="text-center">Belum ada notifikasi</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
  $('.select2').select2({ theme: "bootstrap-5", placeholder: "-- Pilih Pegawai --" });
});
</script>
</body>
</html>
