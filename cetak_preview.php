<?php
session_start();
include 'config.php';

// Proteksi login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'keuangan') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['username'] ?? 'User Keuangan';

// Ambil kegiatan yang sudah dicentang user
$stmt = $conn->prepare("
    SELECT k.nama_kegiatan, k.tanggal, k.jenis_kegiatan
    FROM cetak c
    JOIN kegiatan k ON c.kegiatan_id = k.id
    WHERE c.user_id = ?
    ORDER BY k.tanggal ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$kegiatan = [];
while ($row = $result->fetch_assoc()) {
    $kegiatan[] = $row;
}
$stmt->close();

date_default_timezone_set('Asia/Jakarta');
$datetime_now = date("d-m-Y H:i:s");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Preview Cetak Daftar Berkas</title>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<style>
@media print {
    .no-print { display: none !important; }
}
</style>
</head>
<body class="bg-gray-100">

<div class="max-w-4xl mx-auto mt-6 bg-white p-6 rounded shadow">
    <div class="text-center mb-4">
        <h1 class="text-xl font-bold">Daftar Berkas</h1>
        <p><?= htmlspecialchars($username) ?></p>
        <p><?= $datetime_now ?></p>
    </div>

    <?php if (empty($kegiatan)): ?>
        <p class="text-center text-gray-500">Belum ada kegiatan dipilih.</p>
    <?php else: ?>
        <table class="w-full border border-black text-sm">
            <thead>
                <tr class="bg-gray-200">
                    <th class="border px-2 py-1">No</th>
                    <th class="border px-2 py-1">Nama Kegiatan</th>
                    <th class="border px-2 py-1">Tanggal</th>
                    <th class="border px-2 py-1">Jenis Kegiatan</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($kegiatan as $k): ?>
                    <tr>
                        <td class="border px-2 py-1"><?= $no++ ?></td>
                        <td class="border px-2 py-1"><?= htmlspecialchars($k['nama_kegiatan']) ?></td>
                        <td class="border px-2 py-1"><?= htmlspecialchars($k['tanggal']) ?></td>
                        <td class="border px-2 py-1"><?= htmlspecialchars($k['jenis_kegiatan']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="text-center mt-4 no-print">
        <button onclick="window.print()" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">🖨️ Cetak</button>
        <a href="cetak_daftar_berkas.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">⬅️ Kembali</a>
    </div>
</div>

</body>
</html>
