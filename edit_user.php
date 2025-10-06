<?php
session_start();
include "config.php";

if ($_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];
$result = $conn->query("SELECT * FROM users WHERE id=$id");
$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $role = $_POST['role'];
    $password = $_POST['password'];

    if (!empty($password)) {
        // password baru → di-hash
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username=?, role=?, password=? WHERE id=?");
        $stmt->bind_param("sssi", $username, $role, $hashed_password, $id);
    } else {
        // tanpa ganti password
        $stmt = $conn->prepare("UPDATE users SET username=?, role=? WHERE id=?");
        $stmt->bind_param("ssi", $username, $role, $id);
    }
    $stmt->execute();
    $stmt->close();

    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Akun</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="card">
    <h2>Edit Akun</h2>
    <form method="post">
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']); ?>" required><br>
        <input type="password" name="password" placeholder="Isi jika ingin ganti password"><br>
        <select name="role" required>
            <option value="admin" <?= ($user['role']=='admin'?'selected':''); ?>>Admin</option>
            <option value="keuangan" <?= ($user['role']=='keuangan'?'selected':''); ?>>Keuangan</option>
            <option value="kepegawaian" <?= ($user['role']=='kepegawaian'?'selected':''); ?>>Kepegawaian</option>
            <option value="kearsipan" <?= ($user['role']=='kearsipan'?'selected':''); ?>>Kearsipan</option>
            <option value="bmn" <?= ($user['role']=='bmn'?'selected':''); ?>>BMN</option>
        </select><br>
        <button type="submit">Simpan Perubahan</button>
    </form>
    <br>
    <a href="dashboard.php">⬅️ Kembali</a>
</div>
</body>
</html>
