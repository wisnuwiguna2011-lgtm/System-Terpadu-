<?php
session_start();
include 'config.php';

// Proteksi login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'keuangan') {
    header("Location: login.php");
    exit;
}

// Pastikan ada folder_id di URL
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
if (!$folder) die("Folder tidak ditemukan.");

// FILTER & SORT
$where = "WHERE f.folder_id = ?";
$params = [$folder_id];
$types  = "i";

if (!empty($_GET['search'])) {
    $where .= " AND (f.nama_file LIKE ? OR f.no_surat LIKE ?)";
    $search = "%" . $_GET['search'] . "%";
    $params[] = $search;
    $params[] = $search;
    $types .= "ss";
}

$sort = $_GET['sort'] ?? "tanggal_desc";
switch ($sort) {
    case "tanggal_asc": $order = "f.uploaded_at ASC"; break;
    case "jenis_asc": $order = "f.jenis_file ASC"; break;
    case "jenis_desc": $order = "f.jenis_file DESC"; break;
    default: $order = "f.uploaded_at DESC";
}

// QUERY FILE
$sql = "SELECT f.* FROM files f $where ORDER BY f.nama_pembayaran ASC, $order";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Group by Nama Pembayaran
$files_by_payment = [];
while ($row = $result->fetch_assoc()) {
    $key = $row['nama_pembayaran'] ?: "Tanpa Nama Pembayaran";
    $files_by_payment[$key][] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Daftar File - <?= htmlspecialchars($folder['nama_folder']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<style>
#modalPreview { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:50; }
#modalContent { background:#fff; max-width:90%; max-height:90%; border-radius:12px; overflow:hidden; position:relative; }
#modalContent iframe { width:100%; height:80vh; }
#closeModal { position:absolute; top:10px; right:10px; background:red; color:#fff; border:none; padding:5px 10px; border-radius:5px; cursor:pointer; }
</style>
</head>
<body class="bg-gradient-to-r from-green-100 via-blue-100 to-purple-100 min-h-screen p-6">

<!-- Modal Preview -->
<div id="modalPreview" class="flex">
    <div id="modalContent">
        <button id="closeModal">✖ Close</button>
        <iframe src="" frameborder="0"></iframe>
    </div>
</div>

<nav class="bg-white shadow-md px-6 py-4 flex justify-between items-center rounded-xl">
    <h1 class="text-xl font-bold text-indigo-700">
        📑 Daftar File - <?= htmlspecialchars($folder['nama_folder']) ?>
        <?= $folder['tahun_kegiatan'] ? "({$folder['tahun_kegiatan']})" : "" ?>
    </h1>
    <div class="space-x-4">
        <a href="upload.php?folder_id=<?= $folder_id ?>" class="bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300">⬅️ Kembali</a>
        <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">🚪 Logout</a>
    </div>
</nav>

<div class="container mx-auto mt-6 p-6 bg-white shadow-xl rounded-2xl">

<form method="get" class="mb-4 flex flex-wrap gap-2 items-center">
    <input type="hidden" name="folder_id" value="<?= $folder_id ?>">
    <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="🔍 Cari nama file / no surat..." class="flex-grow px-3 py-2 border rounded">
    <select name="sort" class="px-3 py-2 border rounded">
        <option value="tanggal_desc" <?= $sort=="tanggal_desc"?"selected":"" ?>>📅 Tanggal (Baru → Lama)</option>
        <option value="tanggal_asc"  <?= $sort=="tanggal_asc" ?"selected":"" ?>>📅 Tanggal (Lama → Baru)</option>
        <option value="jenis_asc"    <?= $sort=="jenis_asc"   ?"selected":"" ?>>📂 Jenis File (A → Z)</option>
        <option value="jenis_desc"   <?= $sort=="jenis_desc"  ?"selected":"" ?>>📂 Jenis File (Z → A)</option>
    </select>
    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Cari</button>
    <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?folder_id=<?= $folder_id ?>" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">🔄 Reset</a>
</form>

<?php if (count($files_by_payment) > 0): ?>
    <?php foreach($files_by_payment as $payment_name => $files): ?>
        <h3 class="text-lg font-bold text-blue-700 mt-6 mb-4">
            💰 Nama Pembayaran: <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded"><?= htmlspecialchars($payment_name) ?></span>
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <?php foreach($files as $f): ?>
            <div class="bg-gray-50 p-4 rounded-xl shadow hover:shadow-md transition">
                <div class="text-sm text-gray-500 mb-1">No Surat: <?= htmlspecialchars($f['no_surat'] ?: '-') ?></div>
                <div class="mb-1">
                    <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded text-xs"><?= htmlspecialchars($f['jenis_file'] ?: '-') ?></span>
                </div>
                <div class="font-semibold truncate" title="<?= htmlspecialchars($f['nama_file']) ?>"><?= htmlspecialchars($f['nama_file']) ?></div>
                <div class="text-xs text-gray-400 mt-1 mb-2">Uploaded: <?= htmlspecialchars($f['uploaded_at']) ?></div>
                <div class="flex flex-wrap gap-1">
                    <button onclick="openPreview('<?= urlencode($f['nama_file']) ?>')" class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-xs">👁️ Preview</button>
                    <a href="uploads/<?= urlencode($f['nama_file']) ?>" target="_blank" class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600 text-xs">⬇️ Download</a>
                    <a href="delete_file.php?id=<?= $f['id'] ?>&folder_id=<?= $folder_id ?>" onclick="return confirm('Yakin hapus file ini?')" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 text-xs">🗑 Hapus</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p class="text-gray-500">Belum ada file di folder/SPM ini.</p>
<?php endif; ?>

</div>

<script>
const modal=document.getElementById('modalPreview');
const iframe=modal.querySelector('iframe');
document.getElementById('closeModal').onclick = () => { modal.style.display='none'; iframe.src=''; };

function openPreview(fileName){
    const ext = fileName.split('.').pop().toLowerCase();
    if(['pdf','jpg','jpeg','png','gif'].includes(ext)){
        iframe.src = 'uploads/' + fileName;
    } else if(['doc','docx','xls','xlsx','ppt','pptx'].includes(ext)){
        const url = encodeURIComponent(location.origin + '/uploads/' + fileName);
        iframe.src = 'https://view.officeapps.live.com/op/embed.aspx?src=' + url;
    } else {
        alert('Preview tidak tersedia untuk jenis file ini.');
        return;
    }
    modal.style.display='flex';
}
</script>
</body>
</html>
