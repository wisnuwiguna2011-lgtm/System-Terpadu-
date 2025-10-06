<?php
include "config.php";

echo "<h2>✅ Tes Koneksi DB</h2>";

// cek koneksi
if ($conn->connect_error) {
    die("❌ Koneksi gagal: " . $conn->connect_error);
} else {
    echo "Koneksi sukses ke DB: <b>" . $db . "</b><br><br>";
}

// cek tabel users
$sql = "SELECT id, username, password FROM users";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Username</th><th>Password Hash</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr><td>".$row['id']."</td><td>".$row['username']."</td><td>".$row['password']."</td></tr>";
    }
    echo "</table>";
} else {
    echo "⚠️ Tidak ada data di tabel users.";
}
?>
