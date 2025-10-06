<?php
session_start();
include "config.php";

// Proteksi khusus role pegawai
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pegawai') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data pegawai
$stmt = $conn->prepare("SELECT * FROM pegawai WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$pegawai = $result->fetch_assoc();
$stmt->close();

if (!$pegawai) {
    $pegawai = [
        "id" => 0,
        "nama_lengkap" => $_SESSION['username'],
        "nip" => "-",
        "foto" => "default.png",
        "unit_kerja" => "-"
    ];
}

// Hitung jumlah log harian
$stmt = $conn->prepare("SELECT COUNT(*) as jml FROM log_harian WHERE pegawai_id = ?");
$stmt->bind_param("i", $pegawai['id']);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$total_log = $res['jml'] ?? 0;
$stmt->close();

// Hitung ketepatan waktu
$stmt = $conn->prepare("SELECT COUNT(*) as tepat FROM log_harian WHERE pegawai_id = ? AND DATE(created_at) = tanggal");
$stmt->bind_param("i", $pegawai['id']);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$tepat_waktu = $res['tepat'] ?? 0;
$stmt->close();

$ketepatan = $total_log > 0 ? round(($tepat_waktu / $total_log) * 100) : 0;

// Hitung keterkaitan log (pakai rata-rata nilai keterkaitan dari pimpinan)
$stmt = $conn->prepare("SELECT AVG(keterkaitan) as rata FROM log_harian WHERE pegawai_id = ?");
$stmt->bind_param("i", $pegawai['id']);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$keterkaitan = $res['rata'] ? round($res['rata']) : 0;
$stmt->close();

// Ambil 3 log terakhir
$stmt = $conn->prepare("SELECT * FROM log_harian WHERE pegawai_id = ? ORDER BY tanggal DESC, id DESC LIMIT 3");
$stmt->bind_param("i", $pegawai['id']);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Ambil notifikasi terbaru
$stmt = $conn->prepare("SELECT * FROM notifikasi WHERE user_id=? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Pegawai</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background-color: #f5f7fa; }
    .sidebar {
      width: 240px; min-height: 100vh;
      background: #1e293b; color: white; position: fixed;
    }
    .sidebar a { color: white; text-decoration: none; padding: 12px 20px; display: block; border-radius: 8px; }
    .sidebar a:hover, .sidebar a.active { background: #334155; }
    .content { margin-left: 240px; padding: 20px; }
    .profile-img {
      width: 110px; height: 110px; object-fit: cover;
      border-radius: 50%; border: 3px solid #3b82f6;
    }
    #clock { font-weight: bold; }
  </style>
</head>
<body>

<!-- Sidebar -->
<?php include "sidebar_pegawai.php"; ?>

<!-- Content -->
<div class="content">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3>Dashboard Pegawai</h3>
    <div>
      <span id="clock"></span>
      <span class="ms-3 fw-bold"><?= htmlspecialchars($pegawai['nama_lengkap']) ?></span>
    </div>
  </div>

  <!-- Salam -->
  <div class="card mb-4 p-4 text-center shadow-sm">
    <?php if (!empty($pegawai['foto']) && file_exists("uploads/".$pegawai['foto'])): ?>
      <img src="uploads/<?= htmlspecialchars($pegawai['foto']) ?>" class="profile-img mb-3">
    <?php else: ?>
      <img src="foto/default.png" class="profile-img mb-3">
    <?php endif; ?>
    <h4>Selamat datang, <?= htmlspecialchars($pegawai['nama_lengkap']) ?>!</h4>
    <p class="text-muted">NIP: <?= htmlspecialchars($pegawai['nip']) ?> | Unit: <?= htmlspecialchars($pegawai['unit_kerja']) ?></p>
  </div>

  <!-- Statistik -->
  <div class="row mb-4">
    <div class="col-md-4">
      <div class="card p-3 shadow text-center">
        <h6>Jumlah Log Harian</h6>
        <h2 class="text-primary"><?= $total_log ?></h2>
        <a href="log_harian.php" class="btn btn-sm btn-outline-primary mt-2">Lihat Log</a>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card p-3 shadow text-center">
        <h6>Ketepatan Waktu</h6>
        <h2 class="text-success"><?= $ketepatan ?>%</h2>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card p-3 shadow text-center">
        <h6>Keterkaitan Log</h6>
        <h2 class="text-danger"><?= $keterkaitan ?>%</h2>
      </div>
    </div>
  </div>

  <!-- Notifikasi -->
  <div class="card shadow mb-4">
    <div class="card-header bg-warning text-dark">🔔 Notifikasi Terbaru</div>
    <div class="card-body">
      <?php if (count($notifs) > 0): ?>
        <ul class="list-group">
          <?php foreach ($notifs as $n): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <div>
                <strong><?= htmlspecialchars($n['pesan']) ?></strong><br>
                <small class="text-muted"><?= date("d-m-Y H:i", strtotime($n['created_at'])) ?></small>
              </div>
              <?php if ($n['status'] === 'baru'): ?>
                <a href="baca_notifikasi.php?id=<?= $n['id'] ?>" class="btn btn-sm btn-outline-primary">
                  Tandai dibaca
                </a>
              <?php else: ?>
                <span class="badge bg-success">Dibaca</span>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="text-muted mb-0">Belum ada notifikasi.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Ringkasan Log Harian -->
  <div class="card shadow">
    <div class="card-header bg-primary text-white">📝 3 Log Harian Terbaru</div>
    <div class="card-body">
      <?php if (count($logs) > 0): ?>
        <ul class="list-group">
          <?php foreach ($logs as $log): ?>
            <li class="list-group-item">
              <strong><?= htmlspecialchars($log['nama_aktivitas']) ?></strong><br>
              <small class="text-muted"><?= date("d-m-Y", strtotime($log['tanggal'])) ?></small><br>
              <?= nl2br(htmlspecialchars(substr($log['deskripsi'],0,100))) ?>...
            </li>
          <?php endforeach; ?>
        </ul>
        <div class="mt-3 text-end">
          <a href="log_harian.php" class="btn btn-sm btn-primary">📖 Lihat Semua</a>
        </div>
      <?php else: ?>
        <p class="text-muted mb-0">Belum ada log harian yang ditambahkan.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
// Script jam realtime
function updateClock() {
  let now = new Date();
  let h = String(now.getHours()).padStart(2, '0');
  let m = String(now.getMinutes()).padStart(2, '0');
  let s = String(now.getSeconds()).padStart(2, '0');
  document.getElementById("clock").innerText = `${h}:${m}:${s}`;
}
setInterval(updateClock, 1000);
updateClock();
</script>

</body>
</html>
