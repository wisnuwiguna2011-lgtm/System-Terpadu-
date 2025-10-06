<?php
session_start();
include 'config.php';

// Proteksi login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'keuangan') {
    header("Location: login.php");
    exit;
}

// Pastikan folder_id ada di URL
if (!isset($_GET['folder_id']) || !is_numeric($_GET['folder_id'])) {
    die("Folder tidak ditemukan.");
}

$folder_id = intval($_GET['folder_id']);

// Ambil info folder
$stmt = $conn->prepare("SELECT * FROM folders WHERE id = ?");
$stmt->bind_param("i", $folder_id);
$stmt->execute();
$folder = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$folder) {
    die("Folder tidak ditemukan.");
}

// Ambil daftar file dari list_files / files
$stmt = $conn->prepare("SELECT * FROM files WHERE folder_id = ? ORDER BY subfolder ASC, uploaded_at DESC");
$stmt->bind_param("i", $folder_id);
$stmt->execute();
$result = $stmt->get_result();

// Group by subfolder
$files_by_subfolder = [];
while ($row = $result->fetch_assoc()) {
    $sf = $row['subfolder'] ?: "-";
    $files_by_subfolder[$sf][] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Cetak Stiker - <?= htmlspecialchars($folder['nama_folder']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<style>
.sticker {
    display: inline-block;
    width: 200px;
    height: 80px;
    border: 2px solid #333;
    margin: 5px;
    padding: 5px;
    text-align: center;
    vertical-align: middle;
    font-size: 14px;
    font-weight: bold;
}
@media print {
    body { background: none; }
    .no-print { display: none; }
    .sticker { page-break-inside: avoid; }
}
</style>
</head>
<body class="bg-gray-100 p-6">

<div class="mb-4">
    <button onclick="window.print()" class="no-print bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">🖨️ Cetak Stiker</button>
    <a href="index.php" class="no-print bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">⬅️ Dashboard</a>
</div>

<h1 class="text-xl font-bold mb-4">Stiker - <?= htmlspecialchars($folder['nama_folder']) ?></h1>

<?php if (empty($files_by_subfolder)): ?>
    <p>Tidak ada file untuk dicetak.</p>
<?php else: ?>
    <?php foreach ($files_by_subfolder as $subfolder => $files): ?>
        <h2 class="font-semibold mt-4 mb-2">📂 Kegiatan: <?= htmlspecialchars($subfolder) ?></h2>
        <div class="flex flex-wrap">
            <?php foreach ($files as $f): ?>
                <div class="sticker">
                    <?= htmlspecialchars($f['nama_file']) ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
