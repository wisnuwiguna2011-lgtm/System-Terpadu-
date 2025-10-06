<?php
session_start();
include 'config.php';

// Proteksi login BMN
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'bmn') {
    header("Location: login.php");
    exit;
}

// Ambil data real-time dari database
$total_dokumen = $conn->query("SELECT COUNT(*) as total FROM dokumen_bmn")->fetch_assoc()['total'];
$dokumen_baru  = $conn->query("SELECT COUNT(*) as total FROM dokumen_bmn WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['total'];
$total_kategori = $conn->query("SELECT COUNT(*) as total FROM kategori_dokumen")->fetch_assoc()['total'];
$total_hak_akses = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard BMN Modern</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-72 bg-white shadow-md flex flex-col">
            <div class="p-6 text-center border-b">
                <h1 class="text-2xl font-bold">BMN Dashboard</h1>
            </div>

            <!-- Search Dokumen -->
            <div class="p-4 border-b">
                <input type="text" placeholder="Cari dokumen..." class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <!-- Menu -->
            <nav class="mt-4 flex-1 overflow-y-auto">
                <ul>
                    <li class="px-6 py-3 hover:bg-gray-200">
                        <a href="dashboard_bmn.php" class="flex items-center justify-between">
                            <span>🏠 Dashboard</span>
                        </a>
                    </li>
                    <li class="px-6 py-3 hover:bg-gray-200">
                        <a href="upload_bmn.php" class="flex items-center gap-2">
                            <span>➕ Tambah Dokumen BMN</span>
                        </a>
                    </li>
                    <li class="px-6 py-3 hover:bg-gray-200 flex items-center justify-between">
                        <a href="upload_bmn.php" class="flex items-center gap-2">
                            <span>📄 Daftar Dokumen BMN</span>
                            <span class="bg-red-500 text-white text-xs font-semibold px-2 py-0.5 rounded-full"><?= $total_dokumen ?></span>
                        </a>
                    </li>
                    <li class="px-6 py-3 hover:bg-gray-200">
                        <a href="laporan.php" class="flex items-center justify-between">
                            <span>📊 Laporan & Rekap</span>
                        </a>
                    </li>
                    <li class="px-6 py-3 hover:bg-gray-200">
                        <button onclick="toggleMenu('submenu')" class="w-full flex justify-between items-center">
                            <span>⚙️ Pengaturan</span>
                            <span id="arrow">▼</span>
                        </button>
                        <ul id="submenu" class="ml-4 mt-2 hidden">
                            <li class="py-2 hover:bg-gray-100 px-2 rounded">
                                <a href="kategori_dokumen.php">Kategori Dokumen</a>
                            </li>
                            <li class="py-2 hover:bg-gray-100 px-2 rounded">
                                <a href="hak_akses.php">Hak Akses</a>
                            </li>
                        </ul>
                    </li>
                    <li class="px-6 py-3 mt-auto hover:bg-gray-200">
                        <a href="logout.php" class="flex items-center gap-2 text-red-600">
                            <span>🔓 Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Konten utama -->
        <main class="flex-1 p-6 overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-3xl font-semibold">Selamat Datang di Dashboard BMN</h2>
                <span class="text-gray-600">User: <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></strong></span>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-white p-4 shadow rounded flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium">Total Dokumen</h3>
                        <p class="text-2xl font-bold"><?= $total_dokumen ?></p>
                    </div>
                    <span class="text-3xl">📄</span>
                </div>
                <div class="bg-white p-4 shadow rounded flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium">Dokumen Baru</h3>
                        <p class="text-2xl font-bold"><?= $dokumen_baru ?></p>
                    </div>
                    <span class="text-3xl">🆕</span>
                </div>
                <div class="bg-white p-4 shadow rounded flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium">Kategori</h3>
                        <p class="text-2xl font-bold"><?= $total_kategori ?></p>
                    </div>
                    <span class="text-3xl">🏷️</span>
                </div>
                <div class="bg-white p-4 shadow rounded flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium">Hak Akses</h3>
                        <p class="text-2xl font-bold"><?= $total_hak_akses ?></p>
                    </div>
                    <span class="text-3xl">🔑</span>
                </div>
            </div>

            <p>Pilih menu di sebelah kiri untuk mengelola dokumen BMN atau gunakan search untuk mencari dokumen cepat.</p>
        </main>
    </div>

    <script>
        function toggleMenu(id) {
            const menu = document.getElementById(id);
            const arrow = document.getElementById('arrow');
            if(menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
                arrow.innerText = '▲';
            } else {
                menu.classList.add('hidden');
                arrow.innerText = '▼';
            }
        }
    </script>
</body>
</html>
