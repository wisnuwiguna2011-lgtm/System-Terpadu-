<?php
session_start();
include "config.php";

// Debug mode (hapus di production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // ✅ lebih aman
    $role = $_POST['role'];

    if ($username && $password && $role) {
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        if (!$stmt) {
            die("Query error: " . $conn->error); // tampilkan jika query gagal
        }
        $stmt->bind_param("sss", $username, $password, $role);
        $stmt->execute();
        $stmt->close();

        header("Location: dashboard.php");
        exit;
    } else {
        echo "Data tidak lengkap!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Tambah Akun</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    *{margin:0;padding:0;box-sizing:border-box;font-family:"Poppins",sans-serif;}
    body{display:flex;justify-content:center;align-items:center;min-height:100vh;background:linear-gradient(135deg,#2563eb,#1e40af);}
    .card{background:#fff;padding:2rem;border-radius:1rem;box-shadow:0 8px 20px rgba(0,0,0,0.15);width:100%;max-width:400px;text-align:center;animation:fadeIn 0.6s ease-in-out;}
    h2{margin-bottom:1.5rem;color:#1e3a8a;}
    form{display:flex;flex-direction:column;gap:1rem;}
    input,select{padding:0.8rem 1rem;border:1px solid #cbd5e1;border-radius:0.5rem;font-size:1rem;transition:0.3s;}
    input:focus,select:focus{border-color:#2563eb;outline:none;box-shadow:0 0 0 3px rgba(37,99,235,0.2);}
    button{background:#2563eb;color:#fff;padding:0.9rem;border:none;border-radius:0.5rem;font-size:1rem;cursor:pointer;transition:0.3s;}
    button:hover{background:#1d4ed8;}
    a{display:inline-block;margin-top:1rem;text-decoration:none;color:#2563eb;font-size:0.95rem;transition:0.3s;}
    a:hover{color:#1d4ed8;text-decoration:underline;}
    @keyframes fadeIn{from{opacity:0;transform:translateY(-20px);}to{opacity:1;transform:translateY(0);}}
  </style>
</head>
<body>
  <div class="card">
    <h2>➕ Tambah Akun Baru</h2>
    <form method="post">
      <input type="text" name="username" placeholder="Masukkan Username" required>
      <input type="password" name="password" placeholder="Masukkan Password" required>
      <select name="role" required>
        <option value="">-- Pilih Role --</option>
        <option value="admin">Admin</option>
        <option value="keuangan">Keuangan</option>
        <option value="kepegawaian">Kepegawaian</option>
        <option value="kearsipan">Kearsipan</option>
        <option value="bmn">BMN</option>
      </select>
      <button type="submit">💾 Simpan</button>
    </form>
    <a href="dashboard.php">⬅️ Kembali ke Dashboard</a>
  </div>
</body>
</html>
