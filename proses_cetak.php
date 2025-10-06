<?php
session_start();
include 'config.php';

// Proteksi role keuangan
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'keuangan') {
    header("Location: login.php");
    exit;
}

$username        = $_SESSION['username'] ?? 'User Keuangan';
$selected_year   = $_GET['year'] ?? '';
$selected_folder = $_GET['folder_id'] ?? '';
$action          = $_GET['action'] ?? ''; // daftar_isi
$datetime_now    = date("d-m-Y H:i:s");
$current_page    = basename($_SERVER['PHP_SELF']);

// Validasi input
if ($selected_year !== '' && !preg_match('/^\d{4}$/', $selected_year)) $selected_year = '';
$selected_folder = intval($selected_folder);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cetak Stiker & Daftar Isi Berkas</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="h-screen flex bg-gray-100">

  <!-- Sidebar -->
  <aside id="sidebar" class="w-64 bg-white shadow-lg flex flex-col transition-all duration-300">
    <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-200">
      <div class="w-10 h-10 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold">DK</div>
      <div>
        <h1 class="text-base font-bold text-gray-800">Dashboard</h1>
        <p class="text-xs text-gray-500">Keuangan</p>
      </div>
    </div>

    <nav class="flex-1 px-3 py-6 overflow-y-auto">
      <p class="text-xs font-semibold text-gray-400 uppercase mb-3 px-2">Main Menu</p>
      <ul class="space-y-1">
        <li>
          <a href="dashboard_keuangan.php" class="flex items-center gap-3 px-4 py-2 rounded-lg <?= $current_page=='dashboard_keuangan.php'?'bg-indigo-100 text-indigo-700 font-semibold':'text-gray-700 hover:bg-indigo-50 hover:text-indigo-700' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2 7-7 7 7m-9 2v8m-4 0h8"/>
            </svg>
            <span class="text-sm">Dashboard</span>
          </a>
        </li>
        <li>
          <a href="upload.php" class="flex items-center gap-3 px-4 py-2 rounded-lg <?= $current_page=='upload.php'?'bg-indigo-100 text-indigo-700 font-semibold':'text-gray-700 hover:bg-indigo-50 hover:text-indigo-700' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span class="text-sm">Daftar SPM</span>
          </a>
        </li>
        <li>
          <a href="rekap_keuangan.php" class="flex items-center gap-3 px-4 py-2 rounded-lg <?= $current_page=='rekap_keuangan.php'?'bg-indigo-100 text-indigo-700 font-semibold':'text-gray-700 hover:bg-indigo-50 hover:text-indigo-700' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6h6v6m2 4H7a2 2 0 01-2-2V7a2 2 0 012-2h10a2 2 0 012 2z"/>
            </svg>
            <span class="text-sm">Rekap SPM</span>
          </a>
        </li>
        <li>
          <a href="cetak_stiker_folder.php" class="flex items-center gap-3 px-4 py-2 rounded-lg <?= $current_page=='cetak_stiker_folder.php'?'bg-indigo-100 text-indigo-700 font-semibold':'text-gray-700 hover:bg-indigo-50 hover:text-indigo-700' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18h12v4H6v-4z"/>
            </svg>
            <span class="text-sm">Cetak SPM</span>
          </a>
        </li>
        <li>
          <a href="print_list.php" class="flex items-center gap-3 px-4 py-2 rounded-lg <?= $current_page=='print_list.php'?'bg-indigo-100 text-indigo-700 font-semibold':'text-gray-700 hover:bg-indigo-50 hover:text-indigo-700' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4-4m0 0A7 7 0 1110 4a7 7 0 017 13z"/>
            </svg>
            <span class="text-sm">Pencarian</span>
          </a>
        </li>
      </ul>
    </nav>

    <div class="p-4 border-t border-gray-200">
      <a href="logout.php" class="block text-center px-4 py-2 rounded-lg bg-red-500 hover:bg-red-600 text-white text-sm font-medium">Logout</a>
    </div>
  </aside>

  <!-- Konten Utama -->
  <main class="flex-1 p-6 overflow-y-auto">
    <header class="mb-8">
      <h2 class="text-2xl font-bold text-gray-800">Cetak Stiker & Daftar Isi Berkas</h2>
      <p class="text-gray-500">Silakan pilih tahun & nomor SPM</p>
    </header>

    <!-- Pilihan Tahun & Nomor SPM -->
    <div class="mb-6 flex flex-wrap items-center gap-4">
      <div>
        <label class="block font-semibold mb-1">Pilih Tahun:</label>
        <select id="yearSelect" class="border p-2 rounded">
          <option value="">-- Pilih Tahun --</option>
          <?php 
          $years_result = $conn->query("SELECT DISTINCT tahun_kegiatan FROM folders ORDER BY tahun_kegiatan DESC");
          while($y = $years_result->fetch_assoc()):
              $year_val = $y['tahun_kegiatan'];
          ?>
            <option value="<?= $year_val ?>" <?= ($selected_year==$year_val)?'selected':'' ?>><?= $year_val ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div>
        <label class="block font-semibold mb-1">Pilih Nomor SPM:</label>
        <select id="folderSelect" class="border p-2 rounded">
          <option value="">-- Pilih SPM --</option>
        </select>
      </div>
    </div>

    <?php
    if($selected_year && $selected_folder){
        $stmt = $conn->prepare("SELECT * FROM folders WHERE id=?");
        $stmt->bind_param("i", $selected_folder);
        $stmt->execute();
        $folder_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if($folder_info):
            if($action === 'daftar_isi'){
                $stmt_files = $conn->prepare("SELECT * FROM files WHERE folder_id=? ORDER BY uploaded_at ASC, id ASC");
                $stmt_files->bind_param("i", $selected_folder);
                $stmt_files->execute();
                $files_result = $stmt_files->get_result();
                $stmt_files->close();
    ?>
    <!-- Tampilan Daftar Isi -->
    <div class="bg-white p-6 rounded-lg shadow mb-6">
      <table class="min-w-full border border-gray-300 text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-3 py-2 border">No</th>
            <th class="px-3 py-2 border">Nama File</th>
            <th class="px-3 py-2 border">Tahun</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $no = 1;
        while($file = $files_result->fetch_assoc()):
            $nama_file = $file['nama_file'] ?? '-';
            $tahun_file = $file['tahun'] ?: $selected_year;
        ?>
          <tr>
            <td class="px-3 py-2 border"><?= $no++ ?></td>
            <td class="px-3 py-2 border"><?= htmlspecialchars($nama_file) ?></td>
            <td class="px-3 py-2 border"><?= htmlspecialchars($tahun_file) ?></td>
          </tr>
        <?php endwhile; ?>
        <?php if($no==1): ?>
          <tr><td colspan="3" class="px-3 py-2 border text-center">Tidak ada file</td></tr>
        <?php endif; ?>
        </tbody>
      </table>

      <!-- Tombol Cetak Daftar Isi -->
      <div class="mt-4 flex gap-3">
        <a href="proses_cetak.php?type=daftar_isi&folder_id=<?= $selected_folder ?>&year=<?= $selected_year ?>" target="_blank"
           class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">
          Cetak Daftar Isi Berkas
        </a>
      </div>
    </div>
    <?php
            } else {
                $stmt_count = $conn->prepare("SELECT COUNT(*) AS total FROM files WHERE folder_id=?");
                $stmt_count->bind_param("i", $selected_folder);
                $stmt_count->execute();
                $files_count = $stmt_count->get_result()->fetch_assoc()['total'] ?? 0;
                $stmt_count->close();
    ?>
    <!-- Tampilan Ringkasan Berkas -->
    <div class="bg-white p-6 rounded-lg shadow mb-6">
      <table class="min-w-full border border-gray-300 text-sm">
        <tr><th class="px-3 py-2 border">Nomor SPM</th><td class="px-3 py-2 border"><?= htmlspecialchars($folder_info['nama_folder']) ?></td></tr>
        <tr><th class="px-3 py-2 border">Tahun</th><td class="px-3 py-2 border"><?= htmlspecialchars($selected_year) ?></td></tr>
        <tr><th class="px-3 py-2 border">Jumlah Berkas</th><td class="px-3 py-2 border"><?= $files_count ?> file</td></tr>
        <tr><th class="px-3 py-2 border">Dicetak oleh</th><td class="px-3 py-2 border"><?= htmlspecialchars($username) ?> (<?= $datetime_now ?>)</td></tr>
      </table>

      <!-- Tombol Cetak Daftar Berkas & Lihat Daftar Isi -->
      <div class="mt-4 flex gap-3">
        <a href="proses_cetak.php?type=daftar_berkas&folder_id=<?= $selected_folder ?>&year=<?= $selected_year ?>" target="_blank"
           class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium">
          Cetak Daftar Berkas
        </a>
        <a href="?year=<?= $selected_year ?>&folder_id=<?= $selected_folder ?>&action=daftar_isi"
           class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">
          Lihat Daftar Isi Berkas
        </a>
      </div>
    </div>
    <?php
            }
        endif;
    }
    ?>
  </main>

  <script>
    // Load SPM berdasarkan tahun
    function loadSPM(year, selectedFolder=''){
      if(!year){
        $('#folderSelect').html('<option value="">-- Pilih SPM --</option>');
        return;
      }
      $.get('get_spm_by_year.php', {year: year}, function(data){
        let html = '<option value="">-- Pilih SPM --</option>';
        data.forEach(spm=>{
          html += `<option value="${spm.id}" ${spm.id==selectedFolder?'selected':''}>${spm.nama_folder}</option>`;
        });
        $('#folderSelect').html(html);
      }, 'json');
    }

    $(document).ready(function(){
      let year = $('#yearSelect').val();
      let selectedFolder = '<?= $selected_folder ?>';
      if(year) loadSPM(year, selectedFolder);

      $('#yearSelect').change(function(){
        let y = $(this).val();
        loadSPM(y);
      });

      $('#folderSelect').change(function(){
        let y = $('#yearSelect').val();
        let f = $(this).val();
        if(y && f){
          window.location.href = `?year=${y}&folder_id=${f}`;
        }
      });
    });
  </script>
</body>
</html>
