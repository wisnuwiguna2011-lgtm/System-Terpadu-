<?php
session_start();
include "config.php";

// Proteksi hanya admin/kepegawaian
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kepegawaian') {
    header("Location: login.php");
    exit;
}

$success = $error = "";

// Proses kirim notifikasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim'])) {
    $pesan = trim($_POST['pesan']);
    $tujuan = $_POST['tujuan'];

    if ($pesan === "") {
        $error = "Pesan tidak boleh kosong.";
    } else {
        if ($tujuan === "all") {
            // user_id = NULL berarti semua pegawai
            $stmt = $conn->prepare("INSERT INTO notifikasi (user_id, pesan, status, created_at) VALUES (NULL, ?, 'baru', NOW())");
            $stmt->bind_param("s", $pesan);
        } else {
            $uid = intval($tujuan);
            $stmt = $conn->prepare("INSERT INTO notifikasi (user_id, pesan, status, created_at) VALUES (?, ?, 'baru', NOW())");
            $stmt->bind_param("is", $uid, $pesan);
        }

        if ($stmt->execute()) {
            $success = "✅ Notifikasi berhasil dikirim.";
        } else {
            $error = "❌ Gagal mengirim notifikasi: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Ambil daftar pegawai untuk dropdown
$listPegawai = $conn->query("
    SELECT u.id, p.nama_lengkap, p.nip 
    FROM users u 
    JOIN pegawai p ON u.id = p.user_id 
    ORDER BY p.nama_lengkap ASC
")->fetch_all(MYSQLI_ASSOC);

// Ambil riwayat notifikasi
$notifikasi = $conn->query("
    SELECT n.id, n.user_id, n.pesan, n.status, n.created_at, p.nama_lengkap, u.username
    FROM notifikasi n
    LEFT JOIN users u ON n.user_id = u.id
    LEFT JOIN pegawai p ON p.user_id = u.id
    ORDER BY n.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifikasi Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background:#f5f7fa; font-family:"Segoe UI", sans-serif; }
.content { margin-left:250px; padding:30px; }
.table td, .table th { vertical-align: middle; }
</style>
</head>
<body>

<?php include "sidebar_kepegawaian.php"; ?>

<div class="content">
<h3 class="mb-4"><i class="bi bi-bell-fill text-warning"></i> Notifikasi Admin</h3>

<?php if ($success): ?>
<div class="alert alert-success"><?= $success ?></div>
<?php elseif ($error): ?>
<div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<!-- Form kirim notifikasi -->
<div class="card shadow-sm mb-4">
  <div class="card-body">
    <form method="post">
      <div class="mb-3">
        <label>Pesan</label>
        <textarea name="pesan" class="form-control" required></textarea>
      </div>
      <div class="mb-3">
        <label>Tujuan</label>
        <select name="tujuan" class="form-select" required>
          <option value="all">Semua Pegawai</option>
          <?php foreach($listPegawai as $peg): ?>
          <option value="<?= $peg['id'] ?>"><?= htmlspecialchars($peg['nama_lengkap']) ?> (<?= $peg['nip'] ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" name="kirim" class="btn btn-primary"><i class="bi bi-send"></i> Kirim Notifikasi</button>
    </form>
  </div>
</div>

<!-- Riwayat notifikasi -->
<div class="card shadow-sm">
  <div class="card-body">
    <h5 class="mb-3">Riwayat Notifikasi</h5>
    <table class="table table-bordered table-striped">
      <thead class="table-dark text-center">
        <tr>
          <th>#</th>
          <th>Pesan</th>
          <th>Tujuan</th>
          <th>Status</th>
          <th>Tanggal</th>
        </tr>
      </thead>
      <tbody>
        <?php if(count($notifikasi) > 0): ?>
        <?php foreach($notifikasi as $i=>$n): ?>
        <tr>
          <td class="text-center"><?= $i+1 ?></td>
          <td><?= htmlspecialchars($n['pesan']) ?></td>
          <td class="text-center">
            <?php if($n['user_id']===null): ?>
              <span class="badge bg-info">Semua Pegawai</span>
            <?php else: ?>
              <?= htmlspecialchars($n['nama_lengkap'] ?: $n['username']) ?>
            <?php endif; ?>
          </td>
          <td class="text-center"><?= $n['status']=='baru' ? '<span class="badge bg-warning">Baru</span>' : '<span class="badge bg-success">Dibaca</span>' ?></td>
          <td class="text-center"><?= date("d M Y H:i", strtotime($n['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php else: ?>
        <tr><td colspan="5" class="text-center">Belum ada notifikasi.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
