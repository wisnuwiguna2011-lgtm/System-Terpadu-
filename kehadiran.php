<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Notifikasi
$success = $error = "";

/* ================================
   PROSES TAMBAH IZIN
=================================*/
if (isset($_POST['tambah_izin'])) {
    $jenis           = $_POST['jenis'];
    $tanggal_mulai   = $_POST['tanggal_mulai'];
    $tanggal_selesai = $_POST['tanggal_selesai'];
    $keterangan      = $_POST['keterangan'] ?? "";
    $file_surat      = null;

    // Upload file jika ada
    if (!empty($_FILES['file_surat']['name'])) {
        $targetDir = "uploads/izin/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fileName   = time() . "_" . basename($_FILES['file_surat']['name']);
        $targetFile = $targetDir . $fileName;
        if (move_uploaded_file($_FILES['file_surat']['tmp_name'], $targetFile)) {
            $file_surat = $fileName;
        } else {
            $error = "Gagal upload file.";
        }
    }

    if (!$error) {
        $stmt = $conn->prepare("INSERT INTO izin_pegawai 
            (pegawai_id, jenis, tanggal_mulai, tanggal_selesai, file_surat, keterangan) 
            VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("isssss", $pegawai['id'], $jenis, $tanggal_mulai, $tanggal_selesai, $file_surat, $keterangan);

        if ($stmt->execute()) {
            $success = "Izin berhasil disimpan.";
        } else {
            die("SQL Error: " . $stmt->error);
        }
        $stmt->close();
    }
}

/* ================================
   PROSES HAPUS IZIN
=================================*/
if (isset($_GET['hapus_izin'])) {
    $id = intval($_GET['hapus_izin']);
    $stmt = $conn->prepare("DELETE FROM izin_pegawai WHERE id=? AND pegawai_id=?");
    $stmt->bind_param("ii", $id, $pegawai['id']);
    if ($stmt->execute()) {
        $success = "Izin berhasil dihapus.";
    } else {
        die("SQL Error Hapus: " . $stmt->error);
    }
    $stmt->close();
}

/* ================================
   AMBIL DATA IZIN
=================================*/
$stmt = $conn->prepare("SELECT * FROM izin_pegawai WHERE pegawai_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $pegawai['id']);
$stmt->execute();
$izin = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Kehadiran & Izin - <?= htmlspecialchars($pegawai['nama_lengkap']) ?></title>
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
  <h3 class="fw-bold mb-4"><i class="bi bi-calendar-check"></i> Kehadiran / Izin</h3>

  <!-- Notifikasi -->
  <?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <!-- Form Izin -->
  <div class="card mb-4">
    <div class="card-header bg-warning fw-bold"><i class="bi bi-clipboard-plus"></i> Tambah Izin</div>
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data" class="row g-3">
        <div class="col-md-4">
          <select name="jenis" class="form-select" required>
            <option value="">-- Pilih Jenis Izin --</option>
            <option value="sakit">Sakit</option>
            <option value="cuti">Cuti</option>
            <option value="lupa_isi_daftar_hadir_datang">Lupa Hadir Datang</option>
            <option value="lupa_isi_daftar_hadir_pulang">Lupa Hadir Pulang</option>
            <option value="surat_penugasan">Surat Penugasan</option>
            <option value="lain_lain">Lain-lain</option>
          </select>
        </div>
        <div class="col-md-3"><input type="date" name="tanggal_mulai" class="form-control" required></div>
        <div class="col-md-3"><input type="date" name="tanggal_selesai" class="form-control" required></div>
        <div class="col-md-2"><input type="file" name="file_surat" class="form-control"></div>
        <div class="col-md-12"><input type="text" name="keterangan" class="form-control" placeholder="Keterangan (opsional)"></div>
        <div class="col-12">
          <button type="submit" name="tambah_izin" class="btn btn-warning"><i class="bi bi-save"></i> Simpan</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Riwayat Izin -->
  <div class="card">
    <div class="card-header bg-dark text-white fw-bold"><i class="bi bi-file-earmark-text"></i> Riwayat Izin</div>
    <div class="card-body">
      <?php if ($izin): ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th>Jenis</th>
                <th>Mulai</th>
                <th>Selesai</th>
                <th>Keterangan</th>
                <th>File</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($izin as $i): ?>
                <tr>
                  <td><span class="badge bg-info"><?= ucfirst(str_replace('_',' ',$i['jenis'])) ?></span></td>
                  <td><?= date("d-m-Y", strtotime($i['tanggal_mulai'])) ?></td>
                  <td><?= date("d-m-Y", strtotime($i['tanggal_selesai'])) ?></td>
                  <td><?= htmlspecialchars($i['keterangan'] ?? '') ?: '-' ?></td>

                  <td>
                    <?php if ($i['file_surat']): ?>
                      <a href="uploads/izin/<?= htmlspecialchars($i['file_surat']) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-paperclip"></i> Lihat</a>
                    <?php else: ?><span class="text-muted">-</span><?php endif; ?>
                  </td>
                  <td>
                    <a href="?hapus_izin=<?= $i['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus izin ini?')"><i class="bi bi-trash"></i></a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="text-muted">Belum ada pengajuan izin.</p>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
