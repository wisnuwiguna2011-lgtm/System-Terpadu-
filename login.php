<?php
session_start();
include "config.php"; // koneksi DB + base_url

// --- Paksa non-www ---
if (strpos($_SERVER['HTTP_HOST'], 'www.') === 0) {
    $redirect_url = "https://" . substr($_SERVER['HTTP_HOST'], 4) . $_SERVER['REQUEST_URI'];
    header("Location: $redirect_url", true, 301);
    exit;
}

// --- Konfigurasi error ---
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

// --- Logging aktivitas ---
function log_activity($message) {
    $log_file = __DIR__ . '/login_activity.log';
    $time = date("Y-m-d H:i:s");
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
    $line = "[$time] IP: $ip | $message | UA: $ua" . PHP_EOL;
    file_put_contents($log_file, $line, FILE_APPEND);
}

$error = "";

// --- Proses login ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE username=? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                // Simpan session
                $_SESSION['user_id']  = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role']     = $row['role'];

                log_activity("✅ LOGIN BERHASIL: {$row['username']} (Role: {$row['role']})");

                // Redirect sesuai role
                switch ($row['role']) {
                    case 'admin':
                        header("Location: " . $base_url . "dashboard.php");
                        break;
                    case 'keuangan':
                        header("Location: " . $base_url . "dashboard_keuangan.php");
                        break;
                    case 'kepegawaian':
                        header("Location: " . $base_url . "dashboard_kepegawaian.php");
                        break;
                    case 'pegawai':
                        header("Location: " . $base_url . "dashboard_pegawai.php");
                        break;
                    case 'pimpinan':
                        header("Location: " . $base_url . "dashboard_pimpinan.php");
                        break;
                    default:
                        header("Location: " . $base_url);
                }
                exit;
            } else {
                $error = "❌ Password salah.";
                log_activity("❌ LOGIN GAGAL: Username $username (Password salah)");
            }
        } else {
            $error = "❌ Username tidak ditemukan.";
            log_activity("❌ LOGIN GAGAL: Username $username (tidak ditemukan)");
        }
        $stmt->close();
    } else {
        $error = "❌ Terjadi kesalahan sistem.";
        log_activity("⚠️ ERROR: Query prepare gagal untuk $username");
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Login Sistem</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f5f7fa; }
    .login-box {
      max-width: 400px;
      margin: 80px auto;
      padding: 25px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .form-control { border-radius: 8px; }
    .btn { border-radius: 8px; }
  </style>
</head>
<body>

<div class="login-box">
  <h3 class="text-center mb-4">🔐 Login Sistem</h3>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" autocomplete="off">
    <div class="mb-3">
      <label class="form-label">Username</label>
      <input type="text" name="username" class="form-control" required autofocus>
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">Login</button>
  </form>
</div>

</body>
</html>
