<?php
session_start();
include "config.php";

// Proteksi hanya role kepegawaian
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kepegawaian') {
    header("Location: login.php");
    exit;
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$success = $error = "";

/* ==========================
   PROSES TAMBAH PEGAWAI
   ========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "❌ Token CSRF tidak valid.";
    } else {
        $nip          = trim($_POST['nip']);
        $nama_lengkap = trim($_POST['nama_lengkap']);
        $unit_kerja   = trim($_POST['unit_kerja'] ?? '');
        $tempat       = trim($_POST['tempat_lahir'] ?? '');
        $tgl_lahir    = $_POST['tgl_lahir'] ?? null;
        $pangkat      = trim($_POST['pangkat_gol'] ?? '');
        $no_whatsapp  = trim($_POST['no_whatsapp'] ?? '');

        if ($nip === '' || $nama_lengkap === '') {
            $error = "❌ NIP/NIK dan Nama Lengkap wajib diisi.";
        } else {
            // cek apakah username sudah ada
            $chk = $conn->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
            $chk->bind_param("s", $nip);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                $error = "❌ Username (NIP/NIK) sudah digunakan.";
            } else {
                $chk->close();

                $default_pass = password_hash($nip, PASSWORD_DEFAULT);
                $role = 'pegawai';
                $u = $conn->prepare("INSERT INTO users (username, password, role, created_at) VALUES (?,?,?,NOW())");
                $u->bind_param("sss", $nip, $default_pass, $role);
                if ($u->execute()) {
                    $user_id = $u->insert_id;
                    $u->close();

                    // handle foto upload
                    $foto_name = null;
                    if (!empty($_FILES['foto']['name']) && is_uploaded_file($_FILES['foto']['tmp_name'])) {
                        $targetDir = __DIR__ . "/uploads/";
                        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
                        $foto_name = time() . "_" . preg_replace('/[^a-zA-Z0-9-_\.]/','',basename($_FILES['foto']['name']));
                        $targetPath = $targetDir . $foto_name;
                        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $targetPath)) {
                            $foto_name = null;
                        }
                    }

                    // insert pegawai
                    $p = $conn->prepare("INSERT INTO pegawai 
                        (user_id, nip, nama_lengkap, tempat_lahir, tgl_lahir, pangkat_gol, unit_kerja, no_whatsapp, foto, created_at) 
                        VALUES (?,?,?,?,?,?,?,?,?,NOW())");
                    $p->bind_param("issssssss", $user_id, $nip, $nama_lengkap, $tempat, $tgl_lahir, $pangkat, $unit_kerja, $no_whatsapp, $foto_name);
                    if ($p->execute()) {
                        $success = "✅ Pegawai <b>" . htmlspecialchars($nama_lengkap) . "</b> berhasil ditambahkan. 
                                   Akun: <b>" . htmlspecialchars($nip) . "</b> (password default = NIP/NIK).";
                        $p->close();
                    } else {
                        // rollback
                        $conn->query("DELETE FROM users WHERE id=" . intval($user_id));
                        $error = "❌ Gagal menyimpan data pegawai.";
                    }
                } else {
                    $error = "❌ Gagal membuat akun user.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tambah Pegawai</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background:#f5f7fa; font-family: "Segoe UI", sans-serif; }
    .content { margin-left:250px; padding:20px; }
    .container { max-width:800px; margin-top:40px; }
  </style>
</head>
<body>
<?php include "sidebar_kepegawaian.php"; ?>    

<div class="content">
  <div class="container">
    <h3>➕ Tambah Pegawai</h3>

    <?php if ($success): ?>
      <div class="alert alert-success"><?= $success ?></div>
    <?php elseif ($error): ?>
      <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="card p-4 shadow-sm bg-white">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

      <div class="mb-3">
        <label class="form-label">NIP / NIK</label>
        <input type="text" name="nip" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Nama Lengkap</label>
        <input type="text" name="nama_lengkap" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Unit Kerja</label>
        <input type="text" name="unit_kerja" class="form-control">
      </div>
      <div class="mb-3">
        <label class="form-label">Tempat Lahir</label>
        <input type="text" name="tempat_lahir" class="form-control">
      </div>
      <div class="mb-3">
        <label class="form-label">Tanggal Lahir</label>
        <input type="date" name="tgl_lahir" class="form-control">
      </div>
      <div class="mb-3">
        <label class="form-label">Pangkat / Gol</label>
        <input type="text" name="pangkat_gol" class="form-control">
      </div>
      <div class="mb-3">
        <label class="form-label">No WhatsApp</label>
        <input type="text" name="no_whatsapp" class="form-control" placeholder="6281234567890">
      </div>
      <div class="mb-3">
        <label class="form-label">Foto</label>
        <input type="file" name="foto" class="form-control" accept="image/*">
      </div>

      <div class="d-flex justify-content-between">
        <a href="daftar_pegawai.php" class="btn btn-secondary">⬅️ Kembali</a>
        <button type="submit" class="btn btn-primary">💾 Simpan</button>
      </div>
    </form>
  </div>
</div>
</body>
</html>
