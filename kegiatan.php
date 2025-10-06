<?php
session_start();
include 'config.php';

// Pastikan user login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";

// Tambah kegiatan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    $nama_kegiatan = $_POST['nama_kegiatan'] ?? '';
    $tanggal = $_POST['tanggal'] ?? '';

    if ($nama_kegiatan && $tanggal) {
        $stmt = $conn->prepare("INSERT INTO kegiatan (nama_kegiatan, tanggal) VALUES (?, ?)");
        $stmt->bind_param("ss", $nama_kegiatan, $tanggal);

        if ($stmt->execute()) {
            $message = "<div class='bg-green-100 text-green-700 px-4 py-2 rounded-lg mb-4'>✅ Kegiatan berhasil ditambahkan!</div>";
        } else {
            $message = "<div class='bg-red-100 text-red-700 px-4 py-2 rounded-lg mb-4'>❌ Gagal simpan kegiatan!</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='bg-yellow-100 text-yellow-700 px-4 py-2 rounded-lg mb-4'>⚠️ Nama kegiatan & tanggal wajib diisi.</div>";
    }
}

// Hapus kegiatan
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    $conn->query("DELETE FROM kegiatan WHERE id = $id");
    header("Location: kegiatan.php");
    exit();
}

// Ambil daftar kegiatan
$kegiatanList = $conn->query("SELECT * FROM kegiatan ORDER BY tanggal DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Kegiatan</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-r from-green-100 via-blue-100 to-purple-100 min-h-screen flex items-center justify-center">
    <div class="bg-white shadow-lg rounded-2xl p-8 w-full max-w-4xl">
        <h1 class="text-2xl font-bold text-green-700 mb-4">📌 Manajemen Kegiatan</h1>

        <?php if ($message) echo $message; ?>

        <!-- Form Tambah Kegiatan -->
        <form method="POST" class="space-y-4 mb-8">
            <div>
                <label class="block font-medium mb-1">Nama Kegiatan</label>
                <input type="text" name="nama_kegiatan" class="w-full border px-3 py-2 rounded-lg" required>
            </div>
            <div>
                <label class="block font-medium mb-1">Tanggal</label>
                <input type="date" name="tanggal" class="w-full border px-3 py-2 rounded-lg" required>
            </div>
            <button type="submit" name="tambah" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">+ Tambah Kegiatan</button>
        </form>

        <!-- Tabel Daftar Kegiatan -->
        <h2 class="text-xl font-bold text-green-700 mb-3">📂 Daftar Kegiatan</h2>
        <div class="overflow-x-auto">
            <table class="w-full border border-gray-300 rounded-lg">
                <thead class="bg-green-600 text-white">
                    <tr>
                        <th class="px-3 py-2">No</th>
                        <th class="px-3 py-2">Nama Kegiatan</th>
                        <th class="px-3 py-2">Tanggal</th>
                        <th class="px-3 py-2">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php 
                    $no = 1;
                    while ($kg = $kegiatanList->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 text-center"><?= $no++; ?></td>
                            <td class="px-3 py-2"><?= htmlspecialchars($kg['nama_kegiatan']); ?></td>
                            <td class="px-3 py-2"><?= htmlspecialchars($kg['tanggal']); ?></td>
                            <td class="px-3 py-2 text-center">
                                <a href="kegiatan.php?hapus=<?= $kg['id']; ?>" 
                                   onclick="return confirm('Yakin hapus kegiatan ini?')" 
                                   class="text-red-600 hover:underline">🗑 Hapus</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex justify-between">
            <a href="dashboard.php" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-300">⬅️ Kembali</a>
            <a href="upload.php" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">➡️ Upload File</a>
        </div>
    </div>
</body>
</html>
