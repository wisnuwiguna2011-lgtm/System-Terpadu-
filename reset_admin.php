<?php
session_start();
include "config.php";

// Kalau sudah ada admin login, kunci akses
if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
    echo "<p style='color:green'>Anda sudah login sebagai admin. Reset tidak diperlukan.</p>";
    exit;
}

$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $new_password = trim($_POST['password']);

    if (!empty($username) && !empty($new_password)) {
        // hash password baru
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);

        // cek apakah username ada
        $stmt = $conn->prepare("SELECT id FROM users WHERE username=? AND role='admin'");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // update password admin lama
            $stmt->bind_result($id);
            $stmt->fetch();
            $stmt->close();

            $update = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $update->bind_param("si", $hashed, $id);
            $update->execute();
            $update->close();

            $msg = "✅ Password admin berhasil direset. Silakan login.";
        } else {
            $stmt->close();

            // buat admin baru kalau belum ada
            $role = "admin";
            $status = "approved";
            $insert = $conn->prepare("INSERT INTO users (username, password, role, status) VALUES (?, ?, ?, ?)");
            $insert->bind_param("ssss", $username, $hashed, $role, $status);
            $insert->execute();
            $insert->close();

            $msg = "✅ Admin baru berhasil dibuat. Silakan login.";
        }
    } else {
        $msg = "❌ Username dan password wajib diisi!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Reset Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f4f6f9; display:flex; justify-content:center; align-items:center; height:100vh; }
        .box { background:white; padding:25px; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.1); width:320px; }
        h2 { text-align:center; margin-bottom:20px; }
        label { font-weight:bold; display:block; margin-top:10px; }
        input { width:100%; padding:10px; margin-top:6px; border:1px solid #ccc; border-radius:8px; }
        button { width:100%; padding:10px; margin-top:15px; background:#28a745; border:none; border-radius:8px; color:white; font-weight:bold; cursor:pointer; }
        button:hover { background:#1e7e34; }
        .msg { text-align:center; margin-bottom:10px; font-weight:bold; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Reset Admin</h2>
        <?php if ($msg): ?>
            <p class="msg"><?= htmlspecialchars($msg) ?></p>
        <?php endif; ?>
        <form method="post">
            <label>Username Admin</label>
            <input type="text" name="username" required>
            <label>Password Baru</label>
            <input type="password" name="password" required>
            <button type="submit">Reset / Buat Admin</button>
        </form>
    </div>
</body>
</html>
