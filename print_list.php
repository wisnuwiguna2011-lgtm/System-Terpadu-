<?php
session_start();
include "config.php";

// Cek role (hanya keuangan yang bisa masuk)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'keuangan') {
    header("Location: login.php");
    exit;
}

// Ambil filter pencarian
$search = $_GET['search'] ?? '';
$filterYear = $_GET['tahun'] ?? '';

// --- Ambil daftar tahun untuk dropdown ---
$years = [];
$yearQuery = $conn->query("SELECT DISTINCT tahun FROM files WHERE tahun IS NOT NULL AND tahun <> '' ORDER BY tahun DESC");
if ($yearQuery) {
    while ($row = $yearQuery->fetch_assoc()) {
        $years[] = $row['tahun'];
    }
}

// --- Siapkan query pencarian ---
$sql = "
    SELECT 
        fo.nama_folder, 
        s.nama_subfolder, 
        f.nama_pembayaran,
        f.nama_file,
        f.tahun,
        f.jenis_file,
        f.kategori,
        f.no_surat,
        f.tanggal_surat,
        f.penanda_tangan,
        f.uraian_kegiatan
    FROM files f
    LEFT JOIN folders fo ON f.folder_id = fo.id
    LEFT JOIN subfolders s ON f.subfolder_id = s.id
    WHERE 1=1
";

$params = [];
$types = "";

// Tambahkan filter jika ada
if ($search !== "") {
    $sql .= " AND (
        f.nama_file LIKE ?
        OR f.nama_pembayaran LIKE ?
        OR f.tahun LIKE ?
        OR f.jenis_file LIKE ?
        OR f.kategori LIKE ?
        OR f.no_surat LIKE ?
        OR f.tanggal_surat LIKE ?
        OR f.penanda_tangan LIKE ?
        OR f.uraian_kegiatan LIKE ?
        OR fo.nama_folder LIKE ?
        OR s.nama_subfolder LIKE ?
    )";
    $like_search = "%{$search}%";
    for ($i = 0; $i < 11; $i++) {
        $params[] = $like_search;
        $types .= "s";
    }
}

if ($filterYear !== "") {
    $sql .= " AND f.tahun = ?";
    $params[] = $filterYear;
    $types .= "s";
}

$sql .= " ORDER BY fo.nama_folder, s.nama_subfolder, f.nama_file";

// Eksekusi query hanya jika ada pencarian atau filter tahun
$result = null;
if ($search !== "" || $filterYear !== "") {
    $stmt = $conn->prepare($sql);
    if ($types !== "") {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pencarian File</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/feather-icons"></script>
</head>
<body class="flex bg-gray-100">

  <!-- Sidebar -->
  <aside id="sidebar" class="w-64 bg-white shadow-lg flex flex-col transition-all duration-300">
    <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-200">
      <div class="w-10 h-10 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold">DK</div>
      <div>
        <h1 class="text-base font-bold text-gray-800">Dashboard</h1>
        <p class="text-xs text-gray-500">Keuangan</p>
      </div>
    </div>
    <nav class="flex-1 px-4 py-6 space-y-2 text-gray-700">
      <a href="dashboard_keuangan.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-indigo-100">
        <i data-feather="home" class="w-4"></i> Dashboard
      </a>
      <a href="upload.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-indigo-100">
        <i data-feather="upload" class="w-4"></i> Daftar SPM
      </a>
      <a href="rekap_spm.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-indigo-100">
        <i data-feather="bar-chart-2" class="w-4"></i> Rekap SPM
      </a>
      <a href="cetak_spm.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-indigo-100">
        <i data-feather="printer" class="w-4"></i> Cetak SPM
      </a>
      <a href="print_list.php" class="flex items-center gap-2 px-3 py-2 rounded bg-indigo-50 font-semibold text-indigo-700">
        <i data-feather="search" class="w-4"></i> Pencarian
      </a>
    </nav>
  </aside>

  <!-- Konten -->
  <main class="flex-1 p-6 max-w-screen-xl mx-auto">
    <h1 class="text-2xl font-bold mb-4">Pencarian File</h1>

    <div class="bg-white p-6 rounded shadow">
      <!-- Form Pencarian -->
      <form method="get" class="flex flex-wrap items-center gap-2 mb-4">
        <input type="text" name="search" placeholder="Cari kata kunci..."
               value="<?= htmlspecialchars($search) ?>"
               class="border rounded px-3 py-2 flex-grow max-w-md" />

        <select name="tahun" class="border rounded px-3 py-2">
          <option value="">Semua Tahun</option>
          <?php if (!empty($years)): ?>
            <?php foreach ($years as $year): ?>
              <option value="<?= htmlspecialchars($year) ?>" <?= $filterYear === $year ? 'selected' : '' ?>>
                <?= htmlspecialchars($year) ?>
              </option>
            <?php endforeach; ?>
          <?php else: ?>
            <option value="">(Belum ada data tahun)</option>
          <?php endif; ?>
        </select>

        <button type="submit"
                class="px-4 py-2 bg-green-600 text-white rounded shadow hover:bg-green-700">
          Cari
        </button>

        <a href="print_list.php"
           class="px-4 py-2 bg-gray-400 text-white rounded shadow hover:bg-gray-500">
          Reset
        </a>
      </form>

      <!-- Tabel Hasil -->
      <div class="overflow-x-auto max-h-[70vh]">
        <table class="table-fixed w-full border-collapse border border-gray-300 text-sm">
          <thead class="bg-gray-200 sticky top-0">
            <tr>
              <th class="border px-3 py-2 text-left w-24">Folder</th>
              <th class="border px-3 py-2 text-left w-28">Subfolder</th>
              <th class="border px-3 py-2 text-left w-40">Nama Pembayaran</th>
              <th class="border px-3 py-2 text-left w-40">Nama File</th>
              <th class="border px-3 py-2 text-left w-20">Tahun</th>
              <th class="border px-3 py-2 text-left w-28">Jenis File</th>
              <th class="border px-3 py-2 text-left w-28">Kategori</th>
              <th class="border px-3 py-2 text-left w-28">No Surat</th>
              <th class="border px-3 py-2 text-left w-32">Tanggal Surat</th>
              <th class="border px-3 py-2 text-left w-40">Penanda Tangan</th>
              <th class="border px-3 py-2 text-left w-64">Uraian Kegiatan</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
              <?php while ($row = $result->fetch_assoc()): ?>
                <tr class="hover:bg-gray-50">
                  <td class="border px-3 py-2 break-words"><?= htmlspecialchars($row['nama_folder']) ?></td>
                  <td class="border px-3 py-2 break-words"><?= htmlspecialchars($row['nama_subfolder']) ?></td>
                  <td class="border px-3 py-2 break-words"><?= htmlspecialchars($row['nama_pembayaran']) ?></td>
                  <td class="border px-3 py-2 break-words"><?= htmlspecialchars($row['nama_file']) ?></td>
                  <td class="border px-3 py-2"><?= htmlspecialchars($row['tahun']) ?></td>
                  <td class="border px-3 py-2 break-words"><?= htmlspecialchars($row['jenis_file']) ?></td>
                  <td class="border px-3 py-2 break-words"><?= htmlspecialchars($row['kategori']) ?></td>
                  <td class="border px-3 py-2 break-words"><?= htmlspecialchars($row['no_surat']) ?></td>
                  <td class="border px-3 py-2"><?= htmlspecialchars($row['tanggal_surat']) ?></td>
                  <td class="border px-3 py-2 break-words"><?= htmlspecialchars($row['penanda_tangan']) ?></td>
                  <td class="border px-3 py-2 break-words"><?= htmlspecialchars($row['uraian_kegiatan']) ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="11" class="text-center py-3 text-gray-500">
                  Belum ada data untuk ditampilkan
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <script>feather.replace();</script>
</body>
</html>
