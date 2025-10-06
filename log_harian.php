<?php
session_start();
include "config.php";

// Proteksi role pegawai
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pegawai') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data pegawai
$stmt = $conn->prepare("SELECT * FROM pegawai WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pegawai = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pegawai) die("Data pegawai tidak ditemukan.");

$success = $error = "";

/* =====================================================
   ================ TAMBAH LOG HARIAN =================
   ===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_log'])) {
    $tanggal        = $_POST['tanggal'];
    $nama_aktivitas = trim($_POST['nama_aktivitas']);
    $deskripsi      = trim($_POST['deskripsi']);
    $tautan_skp     = trim($_POST['tautan_skp']);
    $output         = trim($_POST['output']);
    $file_bukti     = null;

    // Upload file bukti jika ada
    if (!empty($_FILES['file_bukti']['name'])) {
        $uploadDir = "uploads/logs/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = "bukti_" . $pegawai['id'] . "_" . time() . "." . pathinfo($_FILES['file_bukti']['name'], PATHINFO_EXTENSION);
        $filePath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['file_bukti']['tmp_name'], $filePath)) {
            $file_bukti = $fileName;
        }
    }

    // Simpan ke database
    $stmt = $conn->prepare("INSERT INTO log_harian (pegawai_id, tanggal, nama_aktivitas, deskripsi, tautan_skp, output, file_bukti) VALUES (?,?,?,?,?,?,?)");
    $stmt->bind_param("issssss", $pegawai['id'], $tanggal, $nama_aktivitas, $deskripsi, $tautan_skp, $output, $file_bukti);
    if ($stmt->execute()) {
        $success = "✅ Log harian berhasil ditambahkan.";
    } else {
        $error = "❌ Terjadi kesalahan saat menyimpan log.";
    }
    $stmt->close();
}

/* =====================================================
   ================= HAPUS LOG HARIAN =================
   ===================================================== */
if (isset($_GET['hapus'])) {
    $log_id = intval($_GET['hapus']);
    $stmt = $conn->prepare("DELETE FROM log_harian WHERE id=? AND pegawai_id=?");
    $stmt->bind_param("ii", $log_id, $pegawai['id']);
    if ($stmt->execute()) {
        $success = "✅ Log harian berhasil dihapus.";
    } else {
        $error = "❌ Gagal menghapus log.";
    }
    $stmt->close();
}

/* =====================================================
   ================= AMBIL LOG HARIAN =================
   ===================================================== */
$stmt = $conn->prepare("SELECT * FROM log_harian WHERE pegawai_id=? ORDER BY tanggal DESC, id DESC");
$stmt->bind_param("i", $pegawai['id']);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Log Harian - <?= htmlspecialchars($pegawai['nama_lengkap']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; }
    .sidebar { width:240px; min-height:100vh; background:#1e293b; color:white; position:fixed; }
    .sidebar a { color:#e2e8f0; text-decoration:none; display:block; padding:12px 20px; border-radius:8px; margin:4px 0; }
    .sidebar a:hover, .sidebar a.active { background:#334155; color:#fff; }
    .content { margin-left:240px; padding:20px; }
    .card { border:none; border-radius:15px; box-shadow:0 2px 6px rgba(0,0,0,0.08); }
    .table thead { background:#e2e8f0; }
  </style>
</head>
<body>

<?php include "sidebar_pegawai.php"; ?>

<div class="content">
  <h3 class="fw-bold mb-4"><i class="bi bi-journal-text"></i> Log Harian</h3>

  <!-- Notifikasi -->
  <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= $success ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= $error ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Form Tambah Log -->
  <div class="card mb-4">
    <div class="card-header bg-primary text-white fw-bold"><i class="bi bi-pencil-square"></i> Tambah Log Harian</div>
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data" class="row g-3">
        <div class="col-md-3"><input type="date" name="tanggal" class="form-control" required></div>
        <div class="col-md-9"><input type="text" name="nama_aktivitas" class="form-control" placeholder="Nama Aktivitas" required></div>
        <div class="col-12"><textarea name="deskripsi" class="form-control" rows="3" placeholder="Deskripsi aktivitas..." required></textarea></div>
        <div class="col-md-6"><input type="text" name="tautan_skp" class="form-control" placeholder="Tautan SKP (opsional)"></div>
        <div class="col-md-6"><input type="text" name="output" class="form-control" placeholder="Output (opsional)"></div>
        <div class="col-12"><input type="file" name="file_bukti" class="form-control"></div>
        <div class="col-12"><button type="submit" name="tambah_log" class="btn btn-success"><i class="bi bi-save"></i> Simpan Log</button></div>
      </form>
    </div>
  </div>

  <!-- Riwayat Log -->
  <div class="card">
    <div class="card-header bg-secondary text-white fw-bold"><i class="bi bi-journal-bookmark"></i> Riwayat Log Harian</div>
    <div class="card-body">
      <?php if ($logs): ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr><th>Tanggal</th><th>Aktivitas</th><th>Deskripsi</th><th>SKP</th><th>Output</th><th>Bukti</th><th>Aksi</th></tr>
            </thead>
            <tbody>
              <?php foreach ($logs as $log): ?>
                <tr>
                  <td><?= date("d-m-Y", strtotime($log['tanggal'])) ?></td>
                  <td><?= htmlspecialchars($log['nama_aktivitas']) ?></td>
                  <td><?= nl2br(htmlspecialchars($log['deskripsi'])) ?></td>
                  <td><a href="<?= htmlspecialchars($log['tautan_skp']) ?>" target="_blank"><?= $log['tautan_skp'] ?: "-" ?></a></td>
                  <td><?= htmlspecialchars($log['output']) ?: "-" ?></td>
                  <td>
                    <?php if ($log['file_bukti']): ?>
                      <a href="uploads/logs/<?= htmlspecialchars($log['file_bukti']) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-paperclip"></i> Lihat</a>
                    <?php else: ?>-<?php endif; ?>
                  </td>
                  <td>
                    <a href="?hapus=<?= $log['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus log ini?')"><i class="bi bi-trash"></i></a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="text-muted">Belum ada log harian.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
