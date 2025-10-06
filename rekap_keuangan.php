<?php
session_start();
include 'config.php';

// Proteksi login khusus role keuangan
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'keuangan') {
    header("Location: login.php");
    exit;
}

// Ambil filter
$filter_folder = $_GET['folder'] ?? '';
$filter_year   = $_GET['year'] ?? '';
$search        = $_GET['search'] ?? '';

// Ambil daftar tahun
$years_arr = [];
$res = $conn->query("SELECT DISTINCT YEAR(uploaded_at) AS year FROM files WHERE uploaded_at IS NOT NULL ORDER BY year DESC");
if ($res) while ($row = $res->fetch_assoc()) $years_arr[] = $row['year'];

// Ambil daftar folder
$folders_arr = [];
$res = $conn->query("SELECT id, nama_folder FROM folders ORDER BY nama_folder ASC");
if ($res) while ($row = $res->fetch_assoc()) $folders_arr[] = $row;

// Build SQL
$sql = "SELECT f.*, fo.nama_folder, u.username AS uploader
        FROM files f
        JOIN folders fo ON f.folder_id = fo.id
        LEFT JOIN users u ON f.uploaded_by = u.id
        WHERE 1=1";
$params = []; $types = "";

// filter folder
if ($filter_folder !== '') {
    $sql .= " AND fo.id = ?";
    $params[] = (int)$filter_folder; $types .= "i";
}
// filter tahun
if ($filter_year !== '') {
    $sql .= " AND YEAR(f.uploaded_at) = ?";
    $params[] = (int)$filter_year; $types .= "i";
}
// search global
if ($search !== '') {
    $sql .= " AND (f.nama_file LIKE ? OR f.no_surat LIKE ? OR f.uploaded_at LIKE ? 
                OR fo.nama_folder LIKE ? OR f.nama_pembayaran LIKE ? OR f.keterangan LIKE ?)";
    $like = "%".$search."%";
    for ($i=0;$i<6;$i++) { $params[] = $like; $types .= "s"; }
}

$sql .= " ORDER BY fo.nama_folder ASC, f.uploaded_at DESC";
$stmt = $conn->prepare($sql);
if ($params) {
    $bind = []; $bind[] = &$types;
    foreach ($params as $k=>$v) $bind[] = &$params[$k];
    call_user_func_array([$stmt, 'bind_param'], $bind);
}
$stmt->execute();
$result = $stmt->get_result();

// Group files by folder
$files_by_folder = [];
while ($row = $result->fetch_assoc()) {
    $files_by_folder[$row['nama_folder']][] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rekap Data Keuangan</title>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<style>
.sidebar { transition: width 0.3s; }
.sidebar.collapsed { width: 70px; }
.sidebar.collapsed .menu-text { display: none; }
.sidebar.collapsed .menu-icon { margin-right: 0; }
.scroll-table { max-height: 360px; overflow-y: auto; }
.table-cell-wrap { max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.badge { display:inline-block; background:#eef2ff; color:#3730a3; padding:4px 8px; border-radius:999px; font-weight:600; font-size:0.8rem; }
</style>
</head>
<body class="bg-gray-100 flex">

<!-- Sidebar -->
<aside class="sidebar w-64 bg-white shadow-lg h-screen fixed flex flex-col">
    <div class="flex items-center justify-between px-4 py-4 border-b">
        <span class="text-xl font-bold text-indigo-600">Keuangan</span>
        <button id="toggleSidebar" class="p-2 rounded hover:bg-gray-200">
            <i class="ph ph-list text-2xl"></i>
        </button>
    </div>
    <nav class="flex-1 px-2 py-4">
        <a href="dashboard_keuangan.php" class="flex items-center px-4 py-2 text-gray-700 rounded-lg hover:bg-indigo-100">
            <i class="ph ph-house-simple text-xl menu-icon mr-3"></i>
            <span class="menu-text">Dashboard</span>
        </a>
        <a href="upload.php" class="flex items-center px-4 py-2 text-gray-700 rounded-lg hover:bg-indigo-100">
            <i class="ph ph-folder text-xl menu-icon mr-3"></i>
            <span class="menu-text">Upload SPM</span>
        </a>
        <a href="rekap.php" class="flex items-center px-4 py-2 text-indigo-600 bg-indigo-50 rounded-lg">
            <i class="ph ph-chart-bar text-xl menu-icon mr-3"></i>
            <span class="menu-text">Rekap Data</span>
        </a>
        <a href="logout.php" class="flex items-center px-4 py-2 text-red-600 rounded-lg hover:bg-red-100 mt-auto">
            <i class="ph ph-sign-out text-xl menu-icon mr-3"></i>
            <span class="menu-text">Logout</span>
        </a>
    </nav>
</aside>

<!-- Main Content -->
<main class="ml-64 flex-1 p-6 transition-all">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">📊 Rekap Data Keuangan</h1>

    <!-- Filter -->
    <form method="GET" id="filterForm" class="mb-6 flex flex-wrap gap-4 items-end bg-white p-4 shadow rounded-lg">
        <div>
            <label class="block font-semibold mb-1">Folder / SPM</label>
            <select name="folder" class="border px-3 py-2 rounded w-52">
                <option value="">-- Semua Folder --</option>
                <?php foreach ($folders_arr as $f): ?>
                    <option value="<?= $f['id'] ?>" <?= ($filter_folder == $f['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($f['nama_folder']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block font-semibold mb-1">Tahun Upload</label>
            <select name="year" class="border px-3 py-2 rounded w-40">
                <option value="">-- Semua Tahun --</option>
                <?php foreach ($years_arr as $y): ?>
                    <option value="<?= $y ?>" <?= ($filter_year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block font-semibold mb-1">Pencarian</label>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari file, no surat, folder, ..." class="border px-3 py-2 rounded w-72">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Terapkan</button>
            <a href="rekap.php" class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400">Reset</a>
        </div>
    </form>

    <!-- Data -->
    <?php if (empty($files_by_folder)): ?>
        <p class="text-gray-500">❌ Belum ada data untuk filter ini.</p>
    <?php else: ?>
        <?php foreach ($files_by_folder as $folder_name => $files): 
            $total_nilai = 0; foreach ($files as $f) $total_nilai += floatval($f['nilai_spm'] ?? 0);
        ?>
        <div class="mb-5 border rounded-lg shadow bg-white">
            <div class="bg-gray-50 p-3 flex justify-between items-center cursor-pointer" onclick="this.nextElementSibling.classList.toggle('hidden')">
                <span class="font-semibold">📂 <?= htmlspecialchars($folder_name) ?> <span class="text-sm text-gray-500"> (<?= count($files) ?> file)</span></span>
                <span class="badge">Total Rp <?= number_format($total_nilai,0,',','.') ?></span>
            </div>
            <div class="p-3 hidden">
                <div class="scroll-table">
                    <table class="min-w-full border text-sm">
                        <thead class="bg-indigo-100">
                            <tr>
                                <th class="border px-3 py-2">No</th>
                                <th class="border px-3 py-2">No Surat</th>
                                <th class="border px-3 py-2">Tanggal Surat</th>
                                <th class="border px-3 py-2">Nama File</th>
                                <th class="border px-3 py-2">Jenis</th>
                                <th class="border px-3 py-2">Nilai SPM</th>
                                <th class="border px-3 py-2">Kategori</th>
                                <th class="border px-3 py-2">Pembayaran</th>
                                <th class="border px-3 py-2">Keterangan</th>
                                <th class="border px-3 py-2">Penanda Tangan</th>
                                <th class="border px-3 py-2">Uraian</th>
                                <th class="border px-3 py-2">Uploader</th>
                                <th class="border px-3 py-2">Tanggal Upload</th>
                                <th class="border px-3 py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $no=1; foreach ($files as $f): 
                            $file_path = "uploads/" . $f['nama_file'];
                            $file_exists = file_exists(__DIR__."/uploads/".$f['nama_file']);
                            $tgl_upload = $f['uploaded_at'] ? date("d-m-Y H:i", strtotime($f['uploaded_at'])) : '-';
                            $tgl_surat  = (!empty($f['tanggal_surat']) && $f['tanggal_surat'] != '0000-00-00') ? date("d-m-Y", strtotime($f['tanggal_surat'])) : '-';
                            $nama_rapi  = preg_replace('/^\d+_/', '', $f['nama_file']);
                        ?>
                            <tr>
                                <td class="border px-3 py-2"><?= $no++ ?></td>
                                <td class="border px-3 py-2"><?= htmlspecialchars($f['no_surat'] ?: '-') ?></td>
                                <td class="border px-3 py-2"><?= $tgl_surat ?></td>
                                <td class="border px-3 py-2 table-cell-wrap" title="<?= htmlspecialchars($f['nama_file']) ?>"><?= htmlspecialchars($nama_rapi) ?></td>
                                <td class="border px-3 py-2"><?= htmlspecialchars($f['jenis_file'] ?: '-') ?></td>
                                <td class="border px-3 py-2"><?= $f['nilai_spm'] ? 'Rp '.number_format($f['nilai_spm'],0,',','.') : '-' ?></td>
                                <td class="border px-3 py-2"><?= htmlspecialchars($f['kategori'] ?: '-') ?></td>
                                <td class="border px-3 py-2"><?= htmlspecialchars($f['nama_pembayaran'] ?: '-') ?></td>
                                <td class="border px-3 py-2"><?= nl2br(htmlspecialchars($f['keterangan'] ?: '-')) ?></td>
                                <td class="border px-3 py-2"><?= htmlspecialchars($f['penanda_tangan'] ?: '-') ?></td>
                                <td class="border px-3 py-2"><?= nl2br(htmlspecialchars($f['uraian_kegiatan'] ?: '-')) ?></td>
                                <td class="border px-3 py-2"><?= htmlspecialchars($f['uploader'] ?: 'Unknown') ?></td>
                                <td class="border px-3 py-2"><?= $tgl_upload ?></td>
                                <td class="border px-3 py-2 text-center">
                                    <?php if ($file_exists): ?>
                                        <a href="<?= $file_path ?>" download class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600">⬇️</a>
                                        <a href="lihat_file.php?file_id=<?= intval($f['id']) ?>" target="_blank" class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 ml-1">👁️</a>
                                    <?php else: ?>
                                        <span class="text-red-500">❌</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<script>
document.getElementById("toggleSidebar").addEventListener("click", function(){
    document.querySelector(".sidebar").classList.toggle("collapsed");
    document.querySelector("main").classList.toggle("ml-64");
    document.querySelector("main").classList.toggle("ml-20");
});
</script>
</body>
</html>
