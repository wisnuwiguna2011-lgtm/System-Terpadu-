<?php
session_start();
include 'config.php';

// Proteksi login keuangan
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'keuangan') {
    header("Location: login.php");
    exit;
}

// Tambah folder
if (isset($_POST['add_folder'])) {
    $folder_name = $_POST['folder_name'];
    $stmt = $conn->prepare("INSERT INTO odners (name) VALUES (?)");
    $stmt->bind_param("s", $folder_name);
    $stmt->execute();
    $stmt->close();
    header("Location: odner_add.php");
    exit;
}

// Ambil statistik
$total_folder = $conn->query("SELECT COUNT(*) AS total FROM folders")->fetch_assoc()['total'];
$total_odner  = $conn->query("SELECT COUNT(*) AS total FROM odners")->fetch_assoc()['total'];
$total_file   = $conn->query("SELECT COUNT(*) AS total FROM files")->fetch_assoc()['total'];

// Ambil semua folder
$folders = $conn->query("SELECT * FROM odners ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Dashboard Keuangan - Simpan Folder</title>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-r from-blue-100 via-purple-100 to-pink-100 min-h-screen">

<!-- Navbar -->
<nav class="bg-white shadow-md px-6 py-4 flex justify-between items-center">
    <h1 class="text-xl font-bold text-indigo-700">📊 Dashboard Keuangan</h1>
    <div class="space-x-4">
        <a href="index.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">🏠 Dashboard</a>
        <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">🚪 Logout</a>
    </div>
</nav>

<div class="container mx-auto mt-6 p-6 bg-white shadow-xl rounded-2xl">

    <!-- Menu Keuangan -->
    <h2 class="text-2xl font-bold text-indigo-700 mb-4">Menu Keuangan</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <a href="folder_add.php" class="bg-blue-500 text-white p-4 rounded-lg hover:bg-blue-600 text-center">📁 Simpan Box</a>
        <a href="odner_add.php" class="bg-purple-500 text-white p-4 rounded-lg hover:bg-purple-600 text-center">📂 Simpan Folder</a>
        <a href="upload.php" class="bg-green-500 text-white p-4 rounded-lg hover:bg-green-600 text-center">📤 Upload Dokumen</a>
        <a href="print_list.php" class="bg-yellow-500 text-white p-4 rounded-lg hover:bg-yellow-600 text-center">🖨️ Cetak Daftar</a>
    </div>

    <!-- Form Tambah Folder -->
    <h2 class="text-2xl font-bold text-indigo-700 mb-4">Tambah Folder Baru</h2>
    <form method="POST" class="mb-6">
        <div class="flex space-x-2">
            <input type="text" name="folder_name" placeholder="Nama Folder" class="border p-2 rounded flex-1" required>
            <button type="submit" name="add_folder" class="bg-blue-500 text-white px-4 rounded hover:bg-blue-600">Tambah Folder</button>
        </div>
    </form>

    <!-- Tabel Daftar Folder -->
    <h2 class="text-2xl font-bold text-indigo-700 mb-4">Daftar Folder</h2>
    <table class="w-full bg-white rounded-lg shadow overflow-hidden">
        <thead class="bg-gray-200">
            <tr>
                <th class="p-2 text-left">#</th>
                <th class="p-2 text-left">Nama Folder</th>
                <th class="p-2 text-left">Jumlah File</th>
                <th class="p-2 text-left">Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php $i=1; while($row = $folders->fetch_assoc()): ?>
            <?php
            $folder_id = $row['id'];
            $file_count = $conn->query("SELECT COUNT(*) as total FROM files WHERE odner_id = $folder_id")->fetch_assoc()['total'];
            ?>
            <tr class="border-t">
                <td class="p-2"><?= $i++ ?></td>
                <td class="p-2"><?= htmlspecialchars($row['name']) ?></td>
                <td class="p-2"><?= $file_count ?></td>
                <td class="p-2 space-x-2">
                    <a href="files_add.php?odner_id=<?= $folder_id ?>" class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600">Tambah File</a>
                    <a href="sticker_print.php?odner_id=<?= $folder_id ?>" class="bg-yellow-500 text-white px-2 py-1 rounded hover:bg-yellow-600">Cetak Stiker</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Statistik -->
    <h2 class="text-2xl font-bold text-indigo-700 mt-6 mb-4">Statistik</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-blue-100 p-4 rounded-lg text-center">
            <h3 class="font-bold">Total Folder</h3>
            <p class="text-2xl font-bold"><?= $total_folder ?></p>
        </div>
        <div class="bg-purple-100 p-4 rounded-lg text-center">
            <h3 class="font-bold">Total Odner</h3>
            <p class="text-2xl font-bold"><?= $total_odner ?></p>
        </div>
        <div class="bg-green-100 p-4 rounded-lg text-center">
            <h3 class="font-bold">Total File</h3>
            <p class="text-2xl font-bold"><?= $total_file ?></p>
        </div>
    </div>
</div>

</body>
</html>
