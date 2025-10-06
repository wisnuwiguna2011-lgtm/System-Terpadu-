<?php
session_start();
include 'config.php';

// Proteksi login keuangan
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'keuangan') {
    header("Location: login.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);
$msg = "";

// Ambil data SPM berdasarkan ID
$stmt = $conn->prepare("SELECT * FROM folders WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$spm = $res->fetch_assoc();
$stmt->close();

if (!$spm) {
    die("❌ Data SPM tidak ditemukan.");
}

// Update data SPM
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_spm         = trim($_POST['no_spm'] ?? '');
    $nilai_spm      = trim($_POST['nilai_spm'] ?? '');
    $tahun_kegiatan = trim($_POST['tahun_kegiatan'] ?? '');
    $keterangan     = trim($_POST['keterangan'] ?? '');

    if ($no_spm !== '') {
        $stmt = $conn->prepare("UPDATE folders 
                                SET nama_folder=?, nilai_spm=?, tahun_kegiatan=?, keterangan=? 
                                WHERE id=?");
        $stmt->bind_param("ssssi", $no_spm, $nilai_spm, $tahun_kegiatan, $keterangan, $id);
        if ($stmt->execute()) {
            $msg = "✅ Data SPM berhasil diperbarui.";
            header("Location: upload.php");
            exit;
        } else {
            $msg = "❌ Gagal update: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $msg = "⚠️ Nomor SPM tidak boleh kosong!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Edit SPM</title>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen p-6">
<div class="max-w-xl mx-auto bg-white p-6 shadow-lg rounded-lg">
    <h1 class="text-xl font-bold mb-4">✏️ Edit Data SPM</h1>

    <?php if ($msg): ?>
        <div class="mb-4 text-red-700 bg-red-100 p-2 rounded"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-3">
        <div>
            <label class="block mb-1">Nomor SPM</label>
            <input type="text" name="no_spm" value="<?= htmlspecialchars($spm['nama_folder']) ?>" required
                   class="border px-2 py-1 rounded w-full">
        </div>
        <div>
            <label class="block mb-1">Nilai SPM</label>
            <input type="number" step="0.01" name="nilai_spm" value="<?= htmlspecialchars($spm['nilai_spm'] ?? '') ?>"
                   class="border px-2 py-1 rounded w-full">
        </div>
        <div>
            <label class="block mb-1">Tahun Kegiatan</label>
            <input type="number" name="tahun_kegiatan" value="<?= htmlspecialchars($spm['tahun_kegiatan']) ?>"
                   class="border px-2 py-1 rounded w-full">
        </div>
        <div>
            <label class="block mb-1">Keterangan</label>
            <input type="text" name="keterangan" value="<?= htmlspecialchars($spm['keterangan']) ?>"
                   class="border px-2 py-1 rounded w-full">
        </div>
        <div class="flex justify-between">
            <a href="upload.php" class="bg-gray-400 text-white px-4 py-2 rounded hover:bg-gray-500">⬅️ Kembali</a>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">💾 Simpan</button>
        </div>
    </form>
</div>
</body>
</html>
