<?php
session_start();
include 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'keuangan') {
    header("Location: login.php");
    exit;
}

$username        = $_SESSION['username'] ?? 'User Keuangan';
$selected_year   = $_GET['year'] ?? '';
$selected_folder = $_GET['folder_id'] ?? '';
$datetime_now    = date("d-m-Y H:i:s");
$current_page    = basename($_SERVER['PHP_SELF']);

if ($selected_year !== '' && !preg_match('/^\d{4}$/', $selected_year)) $selected_year = '';
$selected_folder = intval($selected_folder);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Cetak Daftar Isi Berkas</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="h-screen flex bg-gray-100">

  <!-- Sidebar -->
  <?php include 'sidebar_keuangan.php'; ?>

  <!-- Konten -->
  <main class="flex-1 p-6 overflow-y-auto">
    <h2 class="text-2xl font-bold mb-6">Cetak Daftar Isi Berkas</h2>

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
        $stmt = $conn->prepare("SELECT * FROM files WHERE folder_id=? ORDER BY uploaded_at ASC, id ASC");
        $stmt->bind_param("i", $selected_folder);
        $stmt->execute();
        $files_result = $stmt->get_result();
        $stmt->close();
    ?>
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
        $no=1;
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
    </div>
    <?php } ?>
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
