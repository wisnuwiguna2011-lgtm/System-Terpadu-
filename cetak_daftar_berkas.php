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
  <title>Cetak Daftar Berkas</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="h-screen flex bg-gray-100">

  <!-- Sidebar -->
  <?php include 'sidebar_keuangan.php'; ?>

  <!-- Konten Utama -->
  <main class="flex-1 p-6 overflow-y-auto">
    <h2 class="text-2xl font-bold mb-6">Cetak Daftar Berkas</h2>

    <!-- Pilihan Tahun & Nomor SPM -->
    <div class="mb-6 flex flex-wrap gap-4">
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
            $stmt_count = $conn->prepare("SELECT COUNT(*) AS total FROM files WHERE folder_id=?");
            $stmt_count->bind_param("i", $selected_folder);
            $stmt_count->execute();
            $files_count = $stmt_count->get_result()->fetch_assoc()['total'] ?? 0;
            $stmt_count->close();
    ?>
    <div class="bg-white p-6 rounded-lg shadow mb-6">
      <table class="min-w-full border border-gray-300 text-sm">
        <tr><th class="px-3 py-2 border">Nomor SPM</th><td class="px-3 py-2 border"><?= htmlspecialchars($folder_info['nama_folder']) ?></td></tr>
        <tr><th class="px-3 py-2 border">Tahun</th><td class="px-3 py-2 border"><?= htmlspecialchars($selected_year) ?></td></tr>
        <tr><th class="px-3 py-2 border">Jumlah Berkas</th><td class="px-3 py-2 border"><?= $files_count ?> file</td></tr>
        <tr><th class="px-3 py-2 border">Dicetak oleh</th><td class="px-3 py-2 border"><?= htmlspecialchars($username) ?> (<?= $datetime_now ?>)</td></tr>
      </table>
    </div>
    <?php
        endif;
    }
    ?>
  </main>

  <script>
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

    $(function(){
      let year = $('#yearSelect').val();
      let selectedFolder = '<?= $selected_folder ?>';
      if(year) loadSPM(year, selectedFolder);

      $('#yearSelect').change(function(){
        loadSPM($(this).val());
      });

      $('#folderSelect').change(function(){
        let y = $('#yearSelect').val();
        let f = $(this).val();
        if(y && f) window.location.href = `?year=${y}&folder_id=${f}`;
      });
    });
  </script>
</body>
</html>
