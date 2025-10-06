<?php
session_start();
include "config.php";

// Pesan notifikasi
$msg = "";

// Tambah User
if (isset($_POST['tambah'])) {
    $username = trim($_POST['username']);
    $password = password_hash(trim($_POST['password']), PASSWORD_BCRYPT);
    $role     = $_POST['role'];

    // Cek duplicate username
    $cek = $conn->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
    $cek->bind_param("s", $username);
    $cek->execute();
    $res = $cek->get_result();
    if ($res->num_rows > 0) {
        $msg = "⚠️ Username <b>$username</b> sudah digunakan!";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $password, $role);
        if ($stmt->execute()) {
            $msg = "✅ User berhasil ditambahkan!";
        } else {
            $msg = "❌ Error: " . $stmt->error;
        }
        $stmt->close();
    }
    $cek->close();
}

// Hapus User
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $conn->query("DELETE FROM users WHERE id=$id");
    $msg = "🗑️ User berhasil dihapus!";
}

// Update User
if (isset($_POST['update'])) {
    $id       = intval($_POST['id']);
    $username = trim($_POST['username']);
    $role     = $_POST['role'];

    if (!empty($_POST['password'])) {
        $password = password_hash(trim($_POST['password']), PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET username=?, password=?, role=? WHERE id=?");
        $stmt->bind_param("sssi", $username, $password, $role, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username=?, role=? WHERE id=?");
        $stmt->bind_param("ssi", $username, $role, $id);
    }

    if ($stmt->execute()) {
        $msg = "✏️ User berhasil diperbarui!";
    } else {
        $msg = "❌ Error: " . $stmt->error;
    }
    $stmt->close();
}

// Ambil semua user
$result = $conn->query("SELECT * FROM users ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Pengguna</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .msg { margin: 10px 0; padding: 10px; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        form { margin-bottom: 20px; padding: 10px; border: 1px solid #ccc; background: #f9f9f9; }
        input, select { padding: 5px; margin: 5px 0; }
        button { padding: 6px 12px; margin-top: 5px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        table, th, td { border: 1px solid #ccc; }
        th, td { padding: 8px; text-align: center; }
        a { text-decoration: none; color: red; }
    </style>
</head>
<body>
    <h2>Kelola Pengguna</h2>

    <?php if (!empty($msg)): ?>
        <div class="msg success"><?= $msg; ?></div>
    <?php endif; ?>

    <!-- Form Tambah User -->
    <h3>Tambah User</h3>
    <form method="POST">
        <label>Username:</label><br>
        <input type="text" name="username" required><br>

        <label>Password:</label><br>
        <input type="password" name="password" required><br>

        <label>Role:</label><br>
        <select name="role" required>
            <option value="">-- Pilih Role --</option>
            <option value="admin">Admin</option>
            <option value="keuangan">Keuangan</option>
            <option value="kepegawaian">Kepegawaian</option>
            <option value="kearsipan">Kearsipan</option>
            <option value="user">User</option>
        </select><br><br>

        <button type="submit" name="tambah">Tambah User</button>
    </form>

    <!-- Tabel User -->
    <h3>Daftar User</h3>
    <table>
        <tr>
            <th>ID</th><th>Username</th><th>Role</th><th>Aksi</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id']; ?></td>
            <td><?= $row['username']; ?></td>
            <td><?= $row['role']; ?></td>
            <td>
                <a href="?edit=<?= $row['id']; ?>">✏️ Edit</a> | 
                <a href="?hapus=<?= $row['id']; ?>" onclick="return confirm('Yakin hapus user ini?')">🗑️ Hapus</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <!-- Form Edit User -->
    <?php if (isset($_GET['edit'])): 
        $id = intval($_GET['edit']);
        $user = $conn->query("SELECT * FROM users WHERE id=$id")->fetch_assoc();
    ?>
    <h3>Edit User</h3>
    <form method="POST">
        <input type="hidden" name="id" value="<?= $user['id']; ?>">

        <label>Username:</label><br>
        <input type="text" name="username" value="<?= $user['username']; ?>" required><br>

        <label>Password (kosongkan jika tidak diganti):</label><br>
        <input type="password" name="password"><br>

        <label>Role:</label><br>
        <select name="role" required>
            <option value="admin" <?= ($user['role']=="admin"?"selected":""); ?>>Admin</option>
            <option value="keuangan" <?= ($user['role']=="keuangan"?"selected":""); ?>>Keuangan</option>
            <option value="kepegawaian" <?= ($user['role']=="kepegawaian"?"selected":""); ?>>Kepegawaian</option>
            <option value="kearsipan" <?= ($user['role']=="kearsipan"?"selected":""); ?>>Kearsipan</option>
            <option value="user" <?= ($user['role']=="user"?"selected":""); ?>>User</option>
        </select><br><br>

        <button type="submit" name="update">Update User</button>
    </form>
    <?php endif; ?>
</body>
</html>
