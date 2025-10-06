<?php
session_start();
include "config.php";

// Hanya admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Ambil daftar user untuk filter
$users = $conn->query("SELECT id, username FROM users ORDER BY username ASC");

// Filter berdasarkan user_id
$where = "";
if (isset($_GET['user_id']) && $_GET['user_id'] !== '') {
    $uid = intval($_GET['user_id']);
    $where = "WHERE user_id = $uid";
}

// Ambil log aktivitas
$sql = "SELECT * FROM activity_log $where ORDER BY created_at DESC";
$logs = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Log Aktivitas</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <style>
        body {font-family:'Poppins',sans-serif;margin:0;background:#f8f9fa;}
        nav {background:#5c7cfa;padding:15px 30px;display:flex;justify-content:space-between;align-items:center;color:white;}
        nav h1 {margin:0;font-size:20px;}
        nav a {color:white;text-decoration:none;background:#3b5bdb;padding:8px 15px;border-radius:10px;margin-left:10px;font-weight:500;}
        nav a:hover {background:#364fc7;}
        .container {padding:30px;}
        table {width:100%;border-collapse:collapse;margin-top:20px;background:white;box-shadow:0 4px 15px rgba(0,0,0,0.1);border-radius:12px;overflow:hidden;}
        th, td {padding:12px 15px;border-bottom:1px solid #eee;text-align:left;}
        th {background:#e9ecef;font-weight:600;}
        tr:hover td {background:#f8f9fa;}
        .filter-box {background:white;padding:15px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1);}
        select {padding:8px;border-radius:8px;border:1px solid #ccc;}
        button {padding:8px 12px;border:none;border-radius:8px;background:#5c7cfa;color:white;cursor:pointer;font-weight:500;}
        button:hover {background:#364fc7;}
    </style>
</head>
<body>
    <nav>
        <h1>Log Aktivitas</h1>
        <div>
            <a href="dashboard.php">⬅ Kembali</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="filter-box">
            <form method="get">
                <label for="user_id">Filter User: </label>
                <select name="user_id" id="user_id" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    <?php while($u = $users->fetch_assoc()): ?>
                        <option value="<?= $u['id']; ?>" <?= (isset($_GET['user_id']) && $_GET['user_id']==$u['id'])?'selected':'' ?>>
                            <?= htmlspecialchars($u['username']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </form>
        </div>

        <table>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Aktivitas</th>
                <th>Waktu</th>
            </tr>
            <?php if ($logs->num_rows > 0): ?>
                <?php while($log = $logs->fetch_assoc()): ?>
                <tr>
                    <td><?= $log['id']; ?></td>
                    <td><?= htmlspecialchars($log['username']); ?> (ID: <?= $log['user_id']; ?>)</td>
                    <td><?= htmlspecialchars($log['activity']); ?></td>
                    <td><?= $log['created_at']; ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4" style="text-align:center;color:#888;">Tidak ada log aktivitas.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>
