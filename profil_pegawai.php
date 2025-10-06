<?php
session_start();
include "config.php";

// Proteksi khusus role pegawai
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pegawai') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$pesan = "";

/* =====================================================
   =============== UPDATE PROFIL PEGAWAI ===============
   ===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $nama_lengkap = $_POST['nama_lengkap'];
    $tempat_lahir = $_POST['tempat_lahir'];
    $tgl_lahir    = $_POST['tgl_lahir'];
    $unit_kerja   = $_POST['unit_kerja'];

    $foto = null;
    if (!empty($_FILES['foto']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir);
        $fotoName = "foto_" . $user_id . "_" . time() . "." . pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION);
        $targetFile = $targetDir . $fotoName;
        if (move_uploaded_file($_FILES["foto"]["tmp_name"], $targetFile)) {
            $foto = $fotoName;
        }
    }

    if ($foto) {
        $stmt = $conn->prepare("UPDATE pegawai 
            SET nama_lengkap=?, tempat_lahir=?, tgl_lahir=?, unit_kerja=?, foto=? 
            WHERE user_id=?");
        $stmt->bind_param("sssssi", $nama_lengkap, $tempat_lahir, $tgl_lahir, $unit_kerja, $foto, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE pegawai 
            SET nama_lengkap=?, tempat_lahir=?, tgl_lahir=?, unit_kerja=? 
            WHERE user_id=?");
        $stmt->bind_param("ssssi", $nama_lengkap, $tempat_lahir, $tgl_lahir, $unit_kerja, $user_id);
    }
    $stmt->execute();
    $pesan = "✅ Profil berhasil diperbarui";
}

/* =====================================================
   ================== GANTI PASSWORD ===================
   ===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ganti_password'])) {
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $password_konf = $_POST['password_konf'];

    if (strlen($password_baru) < 6) {
        $pesan = "❌ Password baru minimal 6 karakter!";
    } elseif ($password_baru !== $password_konf) {
        $pesan = "❌ Konfirmasi password baru tidak sama!";
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result && password_verify($password_lama, $result['password'])) {
            $hash_baru = password_hash($password_baru, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param("si", $hash_baru, $user_id);
            $stmt->execute();
            $pesan = "✅ Password berhasil diperbarui";
        } else {
            $pesan = "❌ Password lama salah!";
        }
    }
}

// Ambil data pegawai
$stmt = $conn->prepare("SELECT * FROM pegawai WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$pegawai = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profil <?= htmlspecialchars($pegawai['nama_lengkap']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
  <!-- Sidebar -->
  <?php include "sidebar_pegawai.php"; ?>

  <!-- Konten utama -->
  <div class="content">
    <div class="container-fluid">
      <h3 class="mb-3">👤 Profil Pegawai</h3>

      <!-- Notifikasi -->
      <?php if (!empty($pesan)): ?>
        <div class="alert alert-info"><?= htmlspecialchars($pesan) ?></div>
      <?php endif; ?>

      <!-- Data Pegawai -->
      <div class="card shadow mb-4">
        <div class="card-body text-center">
          <?php if (!empty($pegawai['foto'])): ?>
            <img src="uploads/<?= htmlspecialchars($pegawai['foto']) ?>" class="profile-img mb-3" style="width:150px;height:150px;border-radius:50%;object-fit:cover;border:4px solid #3b82f6;">
          <?php else: ?>
            <img src="foto/default.png" class="profile-img mb-3" style="width:150px;height:150px;border-radius:50%;object-fit:cover;border:4px solid #3b82f6;">
          <?php endif; ?>
          <h4><?= htmlspecialchars($pegawai['nama_lengkap']) ?></h4>
          <p class="text-muted">
            NIP: <?= htmlspecialchars($pegawai['nip'] ?? '-') ?> |
            Unit: <?= htmlspecialchars($pegawai['unit_kerja'] ?? '-') ?>
          </p>
        </div>
      </div>

      <div class="row">
        <!-- Form Edit Profil -->
        <div class="col-md-6">
          <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">✏️ Edit Profil</div>
            <div class="card-body">
              <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                  <label class="form-label">Nama Lengkap</label>
                  <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($pegawai['nama_lengkap']) ?>" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Tempat Lahir</label>
                  <input type="text" name="tempat_lahir" class="form-control" value="<?= htmlspecialchars($pegawai['tempat_lahir'] ?? '') ?>">
                </div>
                <div class="mb-3">
                  <label class="form-label">Tanggal Lahir</label>
                  <input type="date" name="tgl_lahir" class="form-control" value="<?= $pegawai['tgl_lahir'] ?>">
                </div>
                <div class="mb-3">
                  <label class="form-label">Unit Kerja</label>
                  <input type="text" name="unit_kerja" class="form-control" value="<?= htmlspecialchars($pegawai['unit_kerja'] ?? '') ?>">
                </div>
                <div class="mb-3">
                  <label class="form-label">Foto</label>
                  <input type="file" name="foto" class="form-control">
                </div>
                <button type="submit" name="update" class="btn btn-success">💾 Simpan</button>
              </form>
            </div>
          </div>
        </div>

        <!-- Form Ubah Password -->
        <div class="col-md-6">
          <div class="card shadow mb-4">
            <div class="card-header bg-warning">🔑 Ubah Password</div>
            <div class="card-body">
              <form method="post">
                <div class="mb-3">
                  <label class="form-label">Password Lama</label>
                  <input type="password" name="password_lama" class="form-control" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Password Baru (min. 6 karakter)</label>
                  <input type="password" name="password_baru" class="form-control" minlength="6" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Konfirmasi Password Baru</label>
                  <input type="password" name="password_konf" class="form-control" minlength="6" required>
                </div>
                <button type="submit" name="ganti_password" class="btn btn-warning">🔒 Ganti Password</button>
              </form>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</body>
</html>
