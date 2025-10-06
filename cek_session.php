<?php
session_start();

// kalau belum ada user_id di session, isi default
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = rand(1000,9999); // random biar ketahuan
}

// tampilkan semua data session & cookie
echo "<h2>Cek Session & Cookie</h2>";

echo "<h3>Session Data</h3><pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Cookie Data</h3><pre>";
print_r($_COOKIE);
echo "</pre>";

// info tambahan
echo "<h3>Info Server</h3><pre>";
echo 'Session ID: ' . session_id() . "\n";
echo 'Session Save Path: ' . session_save_path() . "\n";
echo 'Session Cookie Params: ';
print_r(session_get_cookie_params());
echo "</pre>";
?>
