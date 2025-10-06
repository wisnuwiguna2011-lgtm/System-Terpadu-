<?php
session_start();
include "config.php"; // koneksi DB + session handler

// ---- Proteksi hanya admin ----
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// ---- CSRF Token ----
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$msg = "";

// ---- Hapus user ----
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: dashboard.php");
    exit;
}

// ---- Reset password ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_id'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $msg = "❌ Token tidak valid.";
    } else {
        $id = intval($_POST['reset_id']);
        $new_pass = trim($_POST['new_password']);

        if (!empty($new_pass)) {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param("si", $hashed, $id);
            $stmt->execute();
            $stmt->close();
            $msg = "✅ Password user berhasil direset.";
        } else {
            $msg = "❌ Password baru tidak boleh kosong.";
        }
    }
}

// ---- Tambah user manual (pimpinan, keuangan, dsb) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user_manual'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $msg = "❌ Token tidak valid.";
    } else {
        $username = strtolower(trim($_POST['username']));
        $password = trim($_POST['password']);
        $role = trim($_POST['role']);

        if (!empty($username) && !empty($password) && !empty($role)) {
            $check = $conn->prepare("SELECT id FROM users WHERE username=?");
            $check->bind_param("s", $username);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $msg = "❌ Username sudah dipakai.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, password, role, status, created_at) VALUES (?,?,?, 'approved', NOW())");
                $stmt->bind_param("sss", $username, $hashed, $role);
                $stmt->execute();
                $stmt->close();
                $msg = "✅ Akun berhasil dibuat dengan role <b>$role</b>.";
            }
            $check->close();
        } else {
            $msg = "❌ Username, password, dan role wajib diisi.";
        }
    }
}

// ---- Tambah pegawai (otomatis role = pegawai) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_pegawai'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $msg = "❌ Token tidak valid.";
    } else {
        $nip = trim($_POST['nip']);
        $nama = trim($_POST['nama_lengkap']);
        $tempat = trim($_POST['tempat_lahir']);
        $tgl = $_POST['tgl_lahir'];
        $pangkat = trim($_POST['pangkat_gol']);
        $unit = trim($_POST['unit_kerja']);

        if (!empty($nip) && !empty($nama)) {
            $check = $conn->prepare("SELECT id FROM users WHERE username=?");
            $check->bind_param("s", $nip);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $msg = "❌ User dengan NIP/NIK ini sudah ada.";
            } else {
                $default_pass = password_hash($nip, PASSWORD_DEFAULT);
                $role = "pegawai";
                $stmt_user = $conn->prepare("INSERT INTO users (username, password, role, status, created_at) VALUES (?,?,?, 'approved', NOW())");
                $stmt_user->bind_param("sss", $nip, $default_pass, $role);
                $stmt_user->execute();
                $user_id = $stmt_user->insert_id;
                $stmt_user->close();

                $stmt = $conn->prepare("INSERT INTO pegawai (user_id, nip, nama_lengkap, tempat_lahir, tgl_lahir, pangkat_gol, unit_kerja, created_at) 
                                        VALUES (?,?,?,?,?,?,?, NOW())");
                $stmt->bind_param("issssss", $user_id, $nip, $nama, $tempat, $tgl, $pangkat, $unit);
                $stmt->execute();
                $stmt->close();

                $msg = "✅ Pegawai berhasil ditambahkan. Akun login: <b>$nip</b>, password = NIP.";
            }
            $check->close();
        } else {
            $msg = "❌ NIP dan Nama wajib diisi.";
        }
    }
}

// ---- Ambil semua user & pegawai ----
$result = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
$pegawai_result = $conn->query("SELECT * FROM pegawai ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <style>
        body {font-family:'Poppins',sans-serif;margin:0;background:linear-gradient(135deg,#f1f5f9,#ffffff);}
        nav {background:#5c7cfa;padding:15px 30px;display:flex;justify-content:space-between;align-items:center;color:white;}
        nav h1 {margin:0;font-size:20px;}
        nav a {color:white;text-decoration:none;background:#3b5bdb;padding:8px 15px;border-radius:10px;margin-left:10px;font-weight:500;}
        nav a:hover {background:#364fc7;}
        .container {padding:30px;}
        table {width:100%;border-collapse:collapse;margin-top:20px;background:white;box-shadow:0 4px 15px rgba(0,0,0,0.1);border-radius:12px;overflow:hidden;}
        th, td {padding:12px 15px;border-bottom:1px solid #eee;text-align:left;}
        th {background:#e9ecef;font-weight:600;}
        tr:hover td {background:#f8f9fa;}
        .btn {padding:6px 12px;border:none;border-radius:8px;cursor:pointer;font-size:13px;text-decoration:none;margin-right:5px;}
        .btn-edit {background:#51cf66;color:white;}
        .btn-edit:hover {background:#37b24d;}
        .btn-delete {background:#ff6b6b;color:white;}
        .btn-delete:hover {background:#e03131;}
        .btn-reset {background:#fab005;color:white;}
        .btn-reset:hover {background:#e67700;}
        .msg {margin:15px 0;padding:10px;border-radius:8px;text-align:center;}
        .msg.success {background:#d3f9d8;color:#2b8a3e;}
        .msg.error {background:#ffe3e3;color:#c92a2a;}
        .popup {display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);justify-content:center;align-items:center;z-index:999;}
        .popup-content {background:white;padding:20px;border-radius:12px;width:400px;max-height:90%;overflow:auto;}
        .popup-content input, .popup-content select {width:100%;padding:8px;margin-top:6px;border:1px solid #ccc;border-radius:8px;}
    </style>
</head>
<body>
    <nav>
        <h1>Dashboard Admin</h1>
        <div>
            <a href="javascript:void(0)" onclick="document.getElementById('userForm').style.display='flex'">👤 Tambah User</a>
            <a href="javascript:void(0)" onclick="document.getElementById('pegawaiForm').style.display='flex'">👔 Tambah Pegawai</a>
            <a href="activity_log.php">📜 Log Aktivitas</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <h2>Daftar Akun</h2>

        <?php if (!empty($msg)): ?>
            <div class="msg <?= (strpos($msg,'✅')!==false?'success':'error') ?>">
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <table>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Role</th>
                <th>Tanggal Buat</th>
                <th>Aksi</th>
            </tr>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id']; ?></td>
                <td><?= htmlspecialchars($row['username']); ?></td>
                <td><?= ucfirst($row['role']); ?></td>
                <td><?= $row['created_at']; ?></td>
                <td>
                    <a class="btn btn-edit" href="edit_user.php?id=<?= $row['id']; ?>">Edit</a>
                    <a class="btn btn-delete" href="dashboard.php?delete=<?= $row['id']; ?>" onclick="return confirm('Yakin hapus akun ini?')">Hapus</a>
                    <button class="btn btn-reset" onclick="showResetForm(<?= $row['id']; ?>,'<?= htmlspecialchars($row['username']); ?>')">Reset</button>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>

        <h2 style="margin-top:40px;">Daftar Pegawai</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>NIP/NIK</th>
                <th>Nama</th>
                <th>Tempat / Tgl Lahir</th>
                <th>Pangkat/Gol</th>
                <th>Unit Kerja</th>
                <th>Tanggal Input</th>
            </tr>
            <?php while($p = $pegawai_result->fetch_assoc()): ?>
            <tr>
                <td><?= $p['id']; ?></td>
                <td><?= htmlspecialchars($p['nip']); ?></td>
                <td><?= htmlspecialchars($p['nama_lengkap']); ?></td>
                <td><?= htmlspecialchars($p['tempat_lahir']).", ".$p['tgl_lahir']; ?></td>
                <td><?= htmlspecialchars($p['pangkat_gol']); ?></td>
                <td><?= htmlspecialchars($p['unit_kerja']); ?></td>
                <td><?= $p['created_at']; ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <!-- Popup reset password -->
    <div id="resetForm" class="popup">
        <div class="popup-content">
            <h3 id="resetTitle">Reset Password</h3>
            <form method="post">
                <input type="hidden" name="reset_id" id="reset_id">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <label>Password Baru:</label>
                <input type="password" name="new_password" required>
                <button type="submit" class="btn btn-reset" style="margin-top:10px;width:100%;">Simpan</button>
                <button type="button" onclick="document.getElementById('resetForm').style.display='none'" style="margin-top:10px;width:100%;" class="btn btn-delete">Batal</button>
            </form>
        </div>
    </div>

    <!-- Popup tambah user manual -->
    <div id="userForm" class="popup">
        <div class="popup-content">
            <h3>Tambah User Manual</h3>
            <form method="post">
                <input type="hidden" name="add_user_manual" value="1">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <label>Username:</label>
                <input type="text" name="username" required>
                <label>Password:</label>
                <input type="password" name="password" required>
                <label>Role:</label>
                <select name="role" required>
                    <option value="">-- Pilih Role --</option>
                    <option value="pimpinan">Pimpinan</option>
                    <option value="keuangan">Keuangan</option>
                    <option value="kepegawaian">Kepegawaian</option>
                    <option value="bmn">BMN</option>
                    <option value="pegawai">Pegawai</option>
                </select>
                <button type="submit" class="btn btn-edit" style="margin-top:10px;width:100%;">Simpan</button>
                <button type="button" onclick="document.getElementById('userForm').style.display='none'" style="margin-top:10px;width:100%;" class="btn btn-delete">Batal</button>
            </form>
        </div>
    </div>

    <!-- Popup tambah pegawai -->
    <div id="pegawaiForm" class="popup">
        <div class="popup-content">
            <h3>Tambah Pegawai</h3>
            <form method="post">
                <input type="hidden" name="add_pegawai" value="1">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <label>NIP/NIK:</label>
                <input type="text" name="nip" required>
                <label>Nama Lengkap:</label>
                <input type="text" name="nama_lengkap" required>
                <label>Tempat Lahir:</label>
                <input type="text" name="tempat_lahir">
                <label>Tanggal Lahir:</label>
                <input type="date" name="tgl_lahir">
                <label>Pangkat/Gol:</label>
                <input type="text" name="pangkat_gol">
                <label>Unit Kerja:</label>
                <input type="text" name="unit_kerja">
                <button type="submit" class="btn btn-edit" style="margin-top:10px;width:100%;">Simpan</button>
                <button type="button" onclick="document.getElementById('pegawaiForm').style.display='none'" style="margin-top:10px;width:100%;" class="btn btn-delete">Batal</button>
            </form>
        </div>
    </div>

    <script>
        function showResetForm(id, username) {
            document.getElementById('reset_id').value = id;
            document.getElementById('resetTitle').innerText = "Reset Password: " + username;
            document.getElementById('resetForm').style.display = 'flex';
        }
    </script>
</body>
</html>
