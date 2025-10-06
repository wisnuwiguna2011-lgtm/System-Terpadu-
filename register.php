<?php
session_start();
include 'config.php'; // koneksi ke database

// Aktifkan error log (hilangkan di production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username   = trim($_POST['username']);
    $email      = trim($_POST['email']);
    $nip        = trim($_POST['nip']);
    $nik        = trim($_POST['nik']);
    $password   = trim($_POST['password']);
    $confirm_pw = trim($_POST['confirm_password']);

    // Validasi input kosong
    if (empty($username) || empty($email) || empty($nip) || empty($nik) || empty($password) || empty($confirm_pw)) {
        die("Semua field wajib diisi. <a href='register.html'>Kembali</a>");
    }

    // Validasi password
    if ($password !== $confirm_pw) {
        die("Password dan konfirmasi tidak sama. <a href='register.html'>Coba lagi</a>");
    }

    // Cek username sudah ada
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        die("Username sudah terdaftar. <a href='register.html'>Coba username lain</a>");
    }
    $stmt->close();

    // Cek email sudah ada
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        die("Email sudah terdaftar. <a href='register.html'>Gunakan email lain</a>");
    }
    $stmt->close();

    // Cek NIP unik
    $stmt = $conn->prepare("SELECT id FROM users WHERE nip = ?");
    $stmt->bind_param("s", $nip);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        die("NIP sudah digunakan. <a href='register.html'>Gunakan NIP lain</a>");
    }
    $stmt->close();

    // Cek NIK unik
    $stmt = $conn->prepare("SELECT id FROM users WHERE nik = ?");
    $stmt->bind_param("s", $nik);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        die("NIK sudah digunakan. <a href='register.html'>Gunakan NIK lain</a>");
    }
    $stmt->close();

    // Hash password
    $hashed_pw = password_hash($password, PASSWORD_DEFAULT);

    // Buat token verifikasi email
    $token = bin2hex(random_bytes(16));

    // Simpan ke database (status default: pending, email_verified default: 0)
    $stmt = $conn->prepare("INSERT INTO users (username, email, nip, nik, password, role, status, email_verified, verify_token) 
                            VALUES (?, ?, ?, ?, ?, 'keuangan', 'pending', 0, ?)");
    $stmt->bind_param("ssssss", $username, $email, $nip, $nik, $hashed_pw, $token);

    if ($stmt->execute()) {
        // Kirim link verifikasi ke email (contoh sederhana)
        $verify_link = "http://yourdomain.com/verify.php?token=$token";
        // mail($email, "Verifikasi Email", "Klik link untuk verifikasi: $verify_link");

        echo "Registrasi berhasil! Silakan cek email Anda untuk verifikasi.";
    } else {
        echo "Terjadi kesalahan: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: register.html");
    exit();
}
?>
