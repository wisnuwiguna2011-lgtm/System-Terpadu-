<?php
include "config.php"; // koneksi database langsung

// ⚠️ sementara: hapus pengecekan session agar bisa akses langsung
$message = "";

session_start();
include "config.php";

// Hanya admin yang boleh akses
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Kalau mau lebih ketat, bisa cek role = admin
// misalnya kalau tabel users ada kolom role
// if ($_SESSION['role'] !== 'admin') { die("Akses ditolak!"); }

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $newpass  = trim($_POST['newpass']);

    if ($username && $newpass) {
        // hash password baru
        $hashed = password_hash($newpass, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password=? WHERE username=?");
        $stmt->bind_param("ss", $hashed, $username);

        if ($stmt->execute()) {
            $message = "✅ Password untuk user <b>$username</b> berhasil diubah!";
        } else {
            $message = "❌ Gagal update password!";
        }
    } else {
        $message = "⚠️ Username & password tidak boleh kosong.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Reset Password User</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-r from-indigo-100 via-purple-100 to-pink-100 min-h-screen flex items-center justify-center">
  <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-md">
    <h2 class="text-xl font-bold text-indigo-700 mb-4">🔑 Reset Password User</h2>

    <?php if ($message): ?>
      <div class="mb-4 p-3 rounded-lg bg-yellow-100 text-yellow-700 text-sm">
        <?php echo $message; ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div>
        <label class="block text-sm font-medium">Username</label>
        <input type="text" name="username" class="w-full border rounded-lg p-2" required>
      </div>
      <div>
        <label class="block text-sm font-medium">Password Baru</label>
        <input type="password" name="newpass" class="w-full border rounded-lg p-2" required>
      </div>
      <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700">
        Update Password
      </button>
    </form>

    <div class="mt-4 text-center">
      <a href="user_dashboard.php" class="text-sm text-indigo-600 hover:underline">⬅️ Kembali ke Dashboard</a>
    </div>
  </div>
</body>
</html>
