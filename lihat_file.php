<?php
session_start();
include 'config.php';

// Proteksi login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'keuangan') {
    header("Location: login.php");
    exit;
}

$file_id = isset($_GET['file_id']) ? intval($_GET['file_id']) : 0;
if ($file_id <= 0) {
    die("File tidak valid.");
}

$sql = "SELECT f.*, fo.nama_folder, u.username AS uploader
        FROM files f
        JOIN folders fo ON f.folder_id = fo.id
        LEFT JOIN users u ON f.uploaded_by = u.id
        WHERE f.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $file_id);
$stmt->execute();
$result = $stmt->get_result();
$file = $result->fetch_assoc();
$stmt->close();

if (!$file) {
    die("File tidak ditemukan.");
}

// Cek file fisik
$file_path = "uploads/" . $file['nama_file'];
$file_exists = file_exists(__DIR__ . "/uploads/" . $file['nama_file']);
$ext = strtolower(pathinfo($file['nama_file'], PATHINFO_EXTENSION));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Detail File - <?= htmlspecialchars($file['nama_file']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">

<nav class="bg-white shadow-md px-6 py-4 flex justify-between items-center">
    <h1 class="text-xl font-bold text-indigo-700">📄 Detail File</h1>
    <a href="rekap_keuangan.php" class="bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300">⬅️ Kembali</a>
</nav>

<div class="max-w-4xl mx-auto mt-6 p-6 bg-white shadow-lg rounded-2xl">

    <h2 class="text-lg font-semibold text-gray-800 mb-4">Informasi Dokumen</h2>
    <table class="table-auto w-full text-sm border">
        <tbody>
            <tr><td class="border px-3 py-2 font-semibold bg-gray-50">Nama File</td><td class="border px-3 py-2"><?= htmlspecialchars($file['nama_file']) ?></td></tr>
            <tr><td class="border px-3 py-2 font-semibold bg-gray-50">No Surat</td><td class="border px-3 py-2"><?= htmlspecialchars($file['no_surat'] ?: '-') ?></td></tr>
            <tr><td class="border px-3 py-2 font-semibold bg-gray-50">Tanggal Surat</td><td class="border px-3 py-2"><?= $file['tanggal_surat'] ? date("d-m-Y", strtotime($file['tanggal_surat'])) : '-' ?></td></tr>
            <tr><td class="border px-3 py-2 font-semibold bg-gray-50">Folder</td><td class="border px-3 py-2"><?= htmlspecialchars($file['nama_folder']) ?></td></tr>
            <tr><td class="border px-3 py-2 font-semibold bg-gray-50">Jenis Dokumen</td><td class="border px-3 py-2"><?= htmlspecialchars($file['jenis_file'] ?: '-') ?></td></tr>
            <tr><td class="border px-3 py-2 font-semibold bg-gray-50">Nama Pembayaran</td><td class="border px-3 py-2"><?= htmlspecialchars($file['nama_pembayaran'] ?: '-') ?></td></tr>
            <tr><td class="border px-3 py-2 font-semibold bg-gray-50">Nilai SPM</td><td class="border px-3 py-2"><?= $file['nilai_spm'] ? 'Rp '.number_format($file['nilai_spm'],0,',','.') : '-' ?></td></tr>
            <tr><td class="border px-3 py-2 font-semibold bg-gray-50">Kategori</td><td class="border px-3 py-2"><?= htmlspecialchars($file['kategori'] ?: '-') ?></td></tr>
            <tr><td class="border px-3 py-2 font-semibold bg-gray-50">Penanda Tangan</td><td class="border px-3 py-2"><?= htmlspecialchars($file['penanda_tangan'] ?: '-') ?></td></tr>
            <tr><td class="border px-3 py-2 font-semibold bg-gray-50">Uraian Kegiatan</td><td class="border px-3 py-2"><?= nl2br(htmlspecialchars($file['uraian_kegiatan'] ?: '-')) ?></td></tr>
            <tr><td class="border px-3 py-2 font-semibold bg-gray-50">Keterangan</td><td class="border px-3 py-2"><?= nl2br(htmlspecialchars($file['keterangan'] ?: '-')) ?></td></tr>
            <tr><td class="border px-3 py-2 font-semibold bg-gray-50">Uploader</td><td class="border px-3 py-2"><?= htmlspecialchars($file['uploader'] ?: 'Unknown') ?></td></tr>
            <tr><td class="border px-3 py-2 font-semibold bg-gray-50">Tanggal Upload</td><td class="border px-3 py-2"><?= $file['uploaded_at'] ? date("d-m-Y H:i", strtotime($file['uploaded_at'])) : '-' ?></td></tr>
        </tbody>
    </table>

    <div class="mt-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-2">📑 Preview File</h2>
        <?php if ($file_exists): ?>
            <?php if (in_array($ext, ['jpg','jpeg','png','gif','webp'])): ?>
                <img src="<?= $file_path ?>" alt="Preview" class="max-w-full rounded shadow">
            <?php elseif ($ext === 'pdf'): ?>
                <embed src="<?= $file_path ?>" type="application/pdf" class="w-full h-[600px] border rounded" />
            <?php else: ?>
                <p class="text-gray-600">Tidak ada preview untuk file ini. Silakan download.</p>
            <?php endif; ?>
            <div class="mt-3">
                <a href="<?= $file_path ?>" download class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">⬇️ Download File</a>
            </div>
        <?php else: ?>
            <p class="text-red-500">❌ File fisik tidak ditemukan di server.</p>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
