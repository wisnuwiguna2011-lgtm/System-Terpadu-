
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'config.php';


// pastikan login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// jenis file wajib
$jenis_wajib = [
    "Surat Undangan", "Surat Tugas", "KAK/ TOR/ Design", "Daftar hadir",
    "Surat perintah bayar", "SPP", "Kuitansi", "Surat Perintah pencairan",
    "SPPD Kegiatan", "Notulensi", "Laporan"
];

// ambil semua kegiatan
$sql = "SELECT k.id, k.nama_kegiatan, YEAR(k.tanggal) as tahun, u.username 
        FROM kegiatan k
        LEFT JOIN users u ON u.id = k.user_id
        ORDER BY k.tanggal DESC";
$kegiatan = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>User Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body class="bg-gradient-to-r from-blue-100 via-purple-100 to-pink-100 min-h-screen">

  <!-- NAVBAR -->
  <nav class="bg-white shadow-md px-6 py-4 flex justify-between items-center">
    <h1 class="text-xl font-bold text-indigo-700">📊 User Dashboard</h1>
    <div class="space-x-4">
      <a href="upload.php" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">⬆️ Upload File</a>
      <a href="index.php" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-300">⬅️ Back</a>
      <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600">🚪 Logout</a>
    </div>
  </nav>

  <!-- CONTENT -->
  <div class="container mx-auto mt-6 p-6 bg-white shadow-xl rounded-2xl">
    <h2 class="text-2xl font-bold text-indigo-700 mb-4">📂 Daftar Kegiatan & File</h2>

    <table class="w-full border border-gray-300 rounded-lg overflow-hidden">
      <thead class="bg-indigo-600 text-white">
        <tr>
          <th class="px-3 py-2">No</th>
          <th class="px-3 py-2">Nama Kegiatan</th>
          <th class="px-3 py-2">Tahun</th>
          <th class="px-3 py-2">User</th>
          <th class="px-3 py-2">File Wajib</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <?php
        $no = 1;
        while ($row = $kegiatan->fetch_assoc()):
          echo "<tr class='hover:bg-gray-50'>";
          echo "<td class='px-3 py-2 text-center'>{$no}</td>";
          echo "<td class='px-3 py-2 font-medium'>{$row['nama_kegiatan']}</td>";
          echo "<td class='px-3 py-2 text-center'>{$row['tahun']}</td>";
          echo "<td class='px-3 py-2 text-center'>".($row['username'] ?? "-")."</td>";

          // cek file per kegiatan
          echo "<td class='px-3 py-2 flex flex-wrap gap-2'>";
          foreach ($jenis_wajib as $jenis) {
              $qf = $conn->prepare("SELECT id FROM files WHERE kegiatan_id=? AND jenis_file=?");
              $qf->bind_param("is", $row['id'], $jenis);
              $qf->execute();
              $qf->store_result();

              if ($qf->num_rows > 0) {
                  // file sudah ada ✅
                  echo "<span class='px-2 py-1 bg-green-100 text-green-700 text-xs rounded-lg flex items-center'>
                          <i class='ph ph-check-circle mr-1'></i>$jenis
                        </span>";
              } else {
                  // file belum ada ❌
                  echo "<span class='px-2 py-1 bg-red-100 text-red-700 text-xs rounded-lg flex items-center'>
                          <i class='ph ph-x-circle mr-1'></i>$jenis
                        </span>";
              }
              $qf->close();
          }
          echo "</td>";

          echo "</tr>";
          $no++;
        endwhile;
        ?>
      </tbody>
    </table>
  </div>
</body>
</html>
