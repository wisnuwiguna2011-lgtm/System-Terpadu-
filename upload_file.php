<?php
// --- Konfigurasi error ---
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

session_start();
include 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'keuangan') {
    header("Location: login.php");
    exit;
}

$folder_id = intval($_GET['folder_id'] ?? 0);

// Ambil info folder/SPM
$stmt = $conn->prepare("SELECT * FROM folders WHERE id=?");
$stmt->bind_param("i", $folder_id);
$stmt->execute();
$spm = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$spm) die("❌ SPM tidak ditemukan.");

$tahun_spm = $spm['tahun_kegiatan'] ?? date("Y");

// Jenis dokumen yang wajib nomor surat
$jenis_wajib_nosurat = [
    "Surat Undangan Peserta","Surat Tugas Peserta","Undangan Narasumber",
    "Surat Tugas Narasumber","Surat Perintah Membayar (SPM)","SPP","Surat Perintah Pencairan Dana"
];

// === Handler AJAX Upload ===
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ajax_upload'])) {
    header('Content-Type: application/json; charset=utf-8');
    ob_clean();

    $file_name = $_FILES['file']['name'] ?? null;
    $file_tmp  = $_FILES['file']['tmp_name'] ?? null;
    $file_size = $_FILES['file']['size'] ?? 0;
    $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    $kategori = $_POST['kategori'] ?? '';
    $jenis_dokumen = $_POST['jenis_dokumen'] ?? '';
    $no_surat = trim($_POST['no_surat'] ?? '');
    $tanggal_surat = $_POST['tanggal_surat'] ?? '';
    $nama_pembayaran = trim($_POST['nama_pembayaran'] ?? '');

    // Validasi
    if(!$file_name) exit(json_encode(["status"=>"error","message"=>"⚠️ File tidak ditemukan."]));
    if(in_array($jenis_dokumen,$jenis_wajib_nosurat) && ($no_surat===''||$tanggal_surat===''))
        exit(json_encode(["status"=>"error","message"=>"⚠️ Nomor & Tanggal Surat wajib diisi."]));

    $allowed_ext=['pdf','doc','docx','xls','xlsx','jpg','jpeg','png','gif','txt'];
    if(!in_array($file_ext,$allowed_ext))
        exit(json_encode(["status"=>"error","message"=>"⚠️ Format file tidak diizinkan."]));
    if($file_size>10*1024*1024)
        exit(json_encode(["status"=>"error","message"=>"⚠️ Maksimal 10MB."]));

    if(!is_dir(__DIR__."/uploads")) mkdir(__DIR__."/uploads",0777,true);

    $new_name = time()."_".preg_replace("/[^a-zA-Z0-9\._-]/","_",$file_name);
    $target = __DIR__."/uploads/".$new_name;

    if(!move_uploaded_file($file_tmp,$target))
        exit(json_encode(["status"=>"error","message"=>"❌ Gagal memindahkan file."]));

    $user_id = $_SESSION['user_id'] ?? 0;

    $no_surat = $no_surat ?: null;
    $tanggal_surat = $tanggal_surat ?: null;
    $nama_pembayaran = $nama_pembayaran ?: null;
    $tahun_spm = intval($tahun_spm);

    $stmt = $conn->prepare("INSERT INTO files 
        (folder_id,nama_file,jenis_file,kategori,no_surat,tanggal_surat,nama_pembayaran,tahun,user_id,uploaded_at) 
        VALUES (?,?,?,?,?,?,?,?,?,NOW())");

    $stmt->bind_param(
        "issssssis",
        $folder_id,
        $new_name,
        $jenis_dokumen,
        $kategori,
        $no_surat,
        $tanggal_surat,
        $nama_pembayaran,
        $tahun_spm,
        $user_id
    );

    if(!$stmt->execute()){
        error_log("Upload error: ".$stmt->error);
        exit(json_encode(["status"=>"error","message"=>"❌ Gagal menyimpan file ke database: ".$stmt->error]));
    }

    $file_id = $stmt->insert_id;
    $stmt->close();

    $display_name = preg_replace('/^\d+_/','',$new_name);

    echo json_encode([
        "status"=>"success",
        "message"=>"✅ File berhasil diupload.",
        "file"=>[
            "id"=>$file_id,
            "nama_file"=>$new_name,
            "display_name"=>$display_name,
            "jenis_file"=>$jenis_dokumen,
            "kategori"=>$kategori,
            "no_surat"=>$no_surat,
            "tanggal_surat"=>$tanggal_surat,
            "nama_pembayaran"=>$nama_pembayaran,
            "tahun"=>$tahun_spm,
            "uploaded_at"=>date("Y-m-d H:i:s")
        ]
    ]);
    exit;
}

// === Ambil daftar file ===
$stmt=$conn->prepare("SELECT * FROM files WHERE folder_id=? ORDER BY uploaded_at DESC");
$stmt->bind_param("i",$folder_id);
$stmt->execute();
$files_res=$stmt->get_result();
$files=$files_res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Upload File - <?= htmlspecialchars($spm['nama_folder']??'-') ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
.drag-area { border: 2px dashed #4f46e5; border-radius: 12px; padding: 30px; text-align: center; color: #4f46e5; font-weight: 600; cursor: pointer; transition: background 0.2s, border-color 0.2s; }
.drag-area.dragover { background: #eef2ff; border-color: #6366f1; }
.sidebar-link { @apply flex items-center gap-3 px-4 py-2 rounded-lg text-gray-700 hover:bg-indigo-100 hover:text-indigo-700 transition; }
.sidebar-link.active { @apply bg-indigo-100 text-indigo-700 font-semibold; }
</style>
</head>
<body class="bg-gray-100 font-sans">

<div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-lg flex flex-col">
        <div class="px-6 py-5 border-b border-gray-200">
            <h1 class="text-lg font-bold text-indigo-600">💰 Keuangan</h1>
        </div>
        <nav class="flex-1 px-4 py-6">
            <p class="text-xs font-semibold text-gray-400 uppercase mb-3">Main Menu</p>
            <ul class="space-y-1">
                <li><a href="dashboard_keuangan.php" class="sidebar-link">📊 <span>Dashboard</span></a></li>
                <li><a href="upload.php" class="sidebar-link">📂 <span>Daftar SPM</span></a></li>
                <li><a href="rekap_keuangan.php" class="sidebar-link">📑 <span>Rekap SPM</span></a></li>
                <li><a href="cetak_stiker_folder.php" class="sidebar-link">🖨 <span>Cetak SPM</span></a></li>
                <li><a href="print_list.php" class="sidebar-link">🔍 <span>Pencarian</span></a></li>
            </ul>
        </nav>
        <div class="p-4 border-t border-gray-200">
            <a href="logout.php" class="block text-center px-4 py-2 rounded-lg bg-red-500 hover:bg-red-600 text-white text-sm font-medium">Logout</a>
        </div>
    </aside>

    <!-- Main -->
    <main class="flex-1 p-8">
        <h1 class="text-2xl font-bold text-indigo-700 mb-6">
            ⬆️ Upload Dokumen SPM<br>
            <span class="text-lg text-gray-600">SPM: <?= htmlspecialchars($spm['nama_folder']??'-') ?> (<?= $tahun_spm ?>)</span>
        </h1>

        <!-- Upload Form -->
        <form id="uploadForm" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-5 bg-white rounded-2xl shadow-lg p-6">
            <div class="drag-area" id="dragArea">📂 Drag & Drop File atau Klik untuk pilih</div>
            <input type="file" name="file" class="hidden" required>
            <div>
                <label class="block mb-1 font-medium">📊 Kategori</label>
                <select name="kategori" class="w-full border px-3 py-2 rounded-lg" required>
                    <option value="">-- Pilih --</option>
                    <option>Belanja Barang</option>
                    <option>Belanja Modal</option>
                    <option>Belanja Pegawai</option>
                </select>
            </div>
            <div>
                <label class="block mb-1 font-medium">📑 Jenis Dokumen</label>
                <select name="jenis_dokumen" onchange="cekJenisDokumen()" class="w-full border px-3 py-2 rounded-lg" required>
                    <option value="">-- Pilih --</option>
                    <option>Surat Undangan Peserta</option>
                    <option>Surat Tugas Peserta</option>
                    <option>Undangan Narasumber</option>
                    <option>Surat Tugas Narasumber</option>
                    <option>KAK/TOR/Design</option>
                    <option>Daftar Hadir Kegiatan</option>
                    <option>Surat Perintah Membayar (SPM)</option>
                    <option>SPP</option>
                    <option>Kuitansi</option>
                    <option>Surat Perintah Pencairan Dana</option>
                    <option>SPPD Kegiatan</option>
                    <option>Notulensi</option>
                    <option>Laporan</option>
                    <option>Operasional</option>
                </select>
            </div>
            <div>
                <label class="block mb-1 font-medium">💰 Nama Pembayaran</label>
                <input type="text" name="nama_pembayaran" class="w-full border px-3 py-2 rounded-lg">
            </div>
            <div>
                <label class="block mb-1 font-medium">✉️ Nomor Surat</label>
                <input type="text" name="no_surat" class="w-full border px-3 py-2 rounded-lg" disabled>
            </div>
            <div>
                <label class="block mb-1 font-medium">📅 Tanggal Surat</label>
                <input type="date" name="tanggal_surat" class="w-full border px-3 py-2 rounded-lg" disabled>
            </div>
            <div class="md:col-span-2 flex justify-end gap-2 mt-4">
                <a href="upload.php" class="bg-gray-300 text-gray-800 px-5 py-2 rounded-lg hover:bg-gray-400">⬅️ Kembali</a>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700">Upload</button>
            </div>
            <div class="mt-4 h-2 w-full bg-gray-200 rounded overflow-hidden md:col-span-2">
                <div id="progressBar" class="h-2 w-0 bg-indigo-600 rounded"></div>
            </div>
        </form>

        <!-- File Table -->
        <div class="mt-8 bg-white rounded-2xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-700 mb-4">📁 Daftar File SPM</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full border border-gray-200 divide-y divide-gray-200">
                    <thead class="bg-indigo-100 text-indigo-700">
                        <tr>
                            <th class="px-4 py-2 text-left">Nama File</th>
                            <th class="px-4 py-2 text-center">Jenis Dokumen</th>
                            <th class="px-4 py-2 text-center">Kategori</th>
                            <th class="px-4 py-2 text-center">No Surat</th>
                            <th class="px-4 py-2 text-center">Tanggal Surat</th>
                            <th class="px-4 py-2 text-center">Tahun</th>
                            <th class="px-4 py-2 text-center">Tanggal Upload</th>
                            <th class="px-4 py-2 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="fileTableBody">
                        <?php foreach($files as $f): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 break-all"><?= htmlspecialchars(preg_replace('/^\d+_/','',$f['nama_file'])) ?></td>
                                <td class="px-4 py-2 text-center"><?= htmlspecialchars($f['jenis_file']??'-') ?></td>
                                <td class="px-4 py-2 text-center"><?= htmlspecialchars($f['kategori']??'-') ?></td>
                                <td class="px-4 py-2 text-center"><?= htmlspecialchars($f['no_surat']??'-') ?></td>
                                <td class="px-4 py-2 text-center"><?= htmlspecialchars($f['tanggal_surat']??'-') ?></td>
                                <td class="px-4 py-2 text-center"><?= htmlspecialchars($f['tahun']??$tahun_spm) ?></td>
                                <td class="px-4 py-2 text-center"><?= htmlspecialchars($f['uploaded_at']??'-') ?></td>
                                <td class="px-4 py-2 text-center space-x-2">
                                    <button onclick="previewFile('<?= $f['nama_file'] ?>')" class="text-indigo-600 hover:underline">👁️ Preview</button>
                                    <a href="lihat_file.php?file_id=<?= $f['id'] ?>&download=1" class="text-green-600 hover:underline">⬇️ Download</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(count($files)===0): ?>
                            <tr><td colspan="8" class="text-center py-4 text-gray-500">Belum ada file.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
function cekJenisDokumen(){
    const jenis=document.querySelector("select[name='jenis_dokumen']").value;
    const noSurat=document.querySelector("input[name='no_surat']");
    const tglSurat=document.querySelector("input[name='tanggal_surat']");
    const wajib=["Surat Undangan Peserta","Surat Tugas Peserta","Undangan Narasumber","Surat Tugas Narasumber","Surat Perintah Membayar (SPM)","SPP","Surat Perintah Pencairan Dana"];
    if(wajib.includes(jenis)){ noSurat.disabled=false; tglSurat.disabled=false; }
    else{ noSurat.disabled=true; tglSurat.disabled=true; noSurat.value=""; tglSurat.value=""; }
}
function previewFile(fname){ window.open("uploads/"+fname,"_blank"); }
</script>
</body>
</html>
