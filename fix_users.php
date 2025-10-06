<?php
include "config.php";

// password baru
$new_password_plain = "admin123";
$new_password_hash = password_hash($new_password_plain, PASSWORD_DEFAULT);

// cek apakah user admin ada
$result = $conn->query("SELECT id, username, password FROM users WHERE username='admin' LIMIT 1");

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "✅ User ditemukan: " . $user['username'] . "<br>";

    // update password
    $update = $conn->query("UPDATE users SET password='$new_password_hash' WHERE username='admin'");
    if ($update) {
        echo "🔑 Password admin berhasil direset ke <b>admin123</b><br>";
    } else {
        echo "❌ Gagal update password: " . $conn->error;
    }
} else {
    echo "⚠️ User admin tidak ditemukan, buat baru...<br>";

    $insert = $conn->query("INSERT INTO users (username, password) VALUES ('admin', '$new_password_hash')");
    if ($insert) {
        echo "✅ User admin berhasil dibuat dengan password <b>admin123</b><br>";
    } else {
        echo "❌ Gagal insert user admin: " . $conn->error;
    }
}

$conn->close();
?>
