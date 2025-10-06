<?php
session_start();
include 'config.php';

// Proteksi login keuangan
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'keuangan') {
    header("Location: login.php");
    exit;
}

$msg = "";

// Tambah Folder (SPM)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_folder'])) {
    $no_spm         = trim($_POST['no_spm'] ?? '');
    $tahun_kegiatan = trim($_POST['tahun_kegiatan'] ?? '');
    $nilai_spm      = preg_replace('/[^0-9]/', '', $_POST['nilai_spm'] ?? '0');
    $keterangan     = trim($_POST['keterangan'] ?? '');

    if ($no_spm !== "") {
        $stmt_check = $conn->prepare("SELECT COUNT(*) AS jumlah FROM folders WHERE nama_folder=? AND tahun_kegiatan=?");
        $stmt_check->bind_param("ss", $no_spm, $tahun_kegiatan);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result()->fetch_assoc();
        if ($res_check['jumlah'] > 0) {
            $msg = "⚠️ Nomor SPM '$no_spm' sudah ada di tahun $tahun_kegiatan!";
        } else {
            $stmt = $conn->prepare("INSERT INTO folders (nama_folder, tahun_kegiatan, nilai_spm, keterangan, created_at) 
                                    VALUES (?, ?, ?, ?, NOW())");
            if ($stmt) {
                $tk = $tahun_kegiatan !== '' ? $tahun_kegiatan : null;
                $stmt->bind_param("ssis", $no_spm, $tk, $nilai_spm, $keterangan);
                if ($stmt->execute()) $msg = "✅ SPM baru berhasil ditambahkan!";
                else $msg = "❌ Gagal tambah SPM: ".$stmt->error;
                $stmt->close();
            }
        }
        $stmt_check->close();
    } else $msg = "⚠️ Nomor SPM tidak boleh kosong!";
}

// Cari
$search_spm = trim($_GET['search_spm'] ?? '');
$spm_arr = [];
if ($search_spm !== '') {
    $stmt = $conn->prepare("SELECT * FROM folders WHERE nama_folder LIKE ? ORDER BY created_at DESC");
    $like = "%".$search_spm."%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $spm_arr[] = $r;
    $stmt->close();
} else {
    $res = $conn->query("SELECT * FROM folders ORDER BY created_at DESC");
    if ($res) while ($r = $res->fetch_assoc()) $spm_arr[] = $r;
}

// Dropdown Tahun
$tahun_db = [];
$res = $conn->query("SELECT DISTINCT tahun_kegiatan FROM folders WHERE tahun_kegiatan IS NOT NULL ORDER BY tahun_kegiatan DESC");
if ($res) while ($row = $res->fetch_assoc()) $tahun_db[] = (int)$row['tahun_kegiatan'];

$tahun_skrg = date("Y");
$tahun_awal = $tahun_skrg - 10;
$tahun_akhir = $tahun_skrg + 5;

$tahun_range = range($tahun_akhir, $tahun_awal);
$tahun_final = array_unique(array_merge($tahun_db, $tahun_range));
rsort($tahun_final);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Dashboard Keuangan - SPM</title>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<style>
.sidebar { transition: width 0.3s; }
.sidebar.collapsed { width: 70px; }
.sidebar.collapsed .menu-text { display: none; }
.sidebar.collapsed .menu-icon { margin-right: 0; }
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
            <i class="ph ph-folder-simple text-xl menu-icon mr-3"></i>
            <span class="menu-text">Daftar SPM</span>
        </a>
        <a href="logout.php" class="flex items-center px-4 py-2 text-red-600 rounded-lg hover:bg-red-100 mt-auto">
            <i class="ph ph-sign-out text-xl menu-icon mr-3"></i>
            <span class="menu-text">Logout</span>
        </a>
    </nav>
</aside>

<!-- Main Content -->
<main class="ml-64 flex-1 p-6 transition-all">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">📑 Daftar SPM</h1>

    <?php if ($msg): ?>
    <div class="mb-4 p-3 rounded-lg <?= strpos($msg,'✅')!==false ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
        <?= htmlspecialchars($msg) ?>
    </div>
    <?php endif; ?>

    <!-- Form Tambah -->
    <div class="mb-6 p-5 bg-white shadow rounded-lg">
        <h2 class="text-lg font-semibold mb-3">➕ Tambah SPM</h2>
        <form method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-3" id="formSPM">
            <input type="text" name="no_spm" id="no_spm" placeholder="Nomor SPM" class="border px-3 py-2 rounded w-full" required>
            <select name="tahun_kegiatan" id="tahun_kegiatan" class="border px-3 py-2 rounded w-full">
                <option value="">Pilih Tahun</option>
                <?php foreach ($tahun_final as $th): ?>
                    <option value="<?= $th ?>"><?= $th ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="nilai_spm" id="nilai_spm" placeholder="Nilai SPM" class="border px-3 py-2 rounded w-full">
            <input type="text" name="keterangan" placeholder="Keterangan" class="border px-3 py-2 rounded w-full">
            <button type="submit" id="btnSubmit" name="tambah_folder" class="bg-indigo-600 text-white px-5 py-2 rounded-lg hover:bg-indigo-700">Tambah</button>
        </form>
    </div>

    <!-- Form Cari -->
    <div class="mb-6 p-5 bg-white shadow rounded-lg">
        <h2 class="text-lg font-semibold mb-3">🔍 Cari SPM</h2>
        <form method="GET" class="flex gap-2 items-center">
            <input type="text" name="search_spm" value="<?= htmlspecialchars($search_spm) ?>" placeholder="Masukkan Nomor SPM..." class="border px-3 py-2 rounded w-64">
            <button type="submit" class="bg-indigo-500 text-white px-4 py-2 rounded hover:bg-indigo-600">Cari</button>
            <a href="upload.php" class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400">Reset</a>
        </form>
    </div>

    <!-- Daftar -->
    <div class="p-5 bg-white shadow rounded-lg">
        <h2 class="text-lg font-semibold mb-3">📂 Daftar SPM</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-200 rounded-lg">
                <thead class="bg-indigo-100 text-indigo-800">
                    <tr>
                        <th class="border px-3 py-2 text-left">No</th>
                        <th class="border px-3 py-2 text-left">Nomor SPM</th>
                        <th class="border px-3 py-2 text-left">Tahun</th>
                        <th class="border px-3 py-2 text-left">Nilai SPM</th>
                        <th class="border px-3 py-2 text-left">Keterangan</th>
                        <th class="border px-3 py-2 text-left">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php $no=1; foreach($spm_arr as $spm): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="border px-3 py-2"><?= $no++ ?></td>
                        <td class="border px-3 py-2 font-medium"><?= htmlspecialchars($spm['nama_folder']) ?></td>
                        <td class="border px-3 py-2"><?= $spm['tahun_kegiatan'] ?? '-' ?></td>
                        <td class="border px-3 py-2">Rp <?= number_format($spm['nilai_spm'],0,',','.') ?></td>
                        <td class="border px-3 py-2"><?= $spm['keterangan'] ?? '-' ?></td>
                        <td class="border px-3 py-2 space-x-1">
                            <a href="upload_file.php?folder_id=<?= $spm['id'] ?>" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">⬆️</a>
                            <a href="list_files.php?folder_id=<?= $spm['id'] ?>" class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600">👁️</a>
                            <a href="edit_spm.php?id=<?= $spm['id'] ?>" class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">✏️</a>
                            <a href="hapus_spm.php?id=<?= $spm['id'] ?>" onclick="return confirm('Yakin hapus SPM ini?')" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">🗑️</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if(count($spm_arr)===0): ?>
                    <tr><td colspan="6" class="border px-3 py-4 text-center text-gray-500">SPM tidak ditemukan.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
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
