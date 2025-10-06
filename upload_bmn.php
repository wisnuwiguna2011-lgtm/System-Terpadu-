<?php
session_start();
include 'config.php';

// Debugging sementara
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Proteksi login BMN
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'bmn') {
    header("Location: login.php");
    exit;
}

$msg = "";

// ==============================
// Buat tabel kategori_dokumen jika belum ada
// ==============================
$conn->query("
CREATE TABLE IF NOT EXISTS kategori_dokumen (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL
) ENGINE=InnoDB;
");

// Masukkan kategori jika kosong
$result = $conn->query("SELECT COUNT(*) as total FROM kategori_dokumen");
$row = $result->fetch_assoc();
if($row['total'] == 0){
    $kategori_list = [
        'Surat Permintaan Belanja Barang',
        'Perjanjian Kerjasama (PKS)',
        'Peminjaman Kendaraan Dinas',
        'BA Invent BMN',
        'BA Stock Opname',
        'BAST TKTM',
        'Perjanjian Pinjam Pakai (SIP)',
        'BA Pengembalian BMN',
        'Dokumen Lainnya'
    ];
    $stmt = $conn->prepare("INSERT INTO kategori_dokumen (nama_kategori) VALUES (?)");
    foreach($kategori_list as $k){
        $stmt->bind_param("s",$k);
        $stmt->execute();
    }
}

// ==============================
// Buat tabel dokumen_bmn jika belum ada
// ==============================
$conn->query("
CREATE TABLE IF NOT EXISTS dokumen_bmn (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kategori INT(11) UNSIGNED NOT NULL,
    nomor_surat VARCHAR(100) DEFAULT NULL,
    tahun INT(4) NOT NULL,
    file VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori) REFERENCES kategori_dokumen(id) ON DELETE CASCADE
) ENGINE=InnoDB;
");

// ==============================
// Proses Upload Dokumen
// ==============================
if(isset($_POST['upload'])) {
    $kategori = $_POST['kategori'] ?? '';
    $nomor_surat = trim($_POST['nomor_surat'] ?? '');
    $tahun = $_POST['tahun'] ?? '';

    // Dokumen wajib nomor surat
    $wajib_nomor = [
        'Surat Permintaan Belanja Barang',
        'Peminjaman Kendaraan Dinas',
        'BA Invent BMN',
        'BA Stock Opname',
        'BAST TKTM',
        'Perjanjian Pinjam Pakai (SIP)',
        'BA Pengembalian BMN'
    ];
    $kategori_result = $conn->query("SELECT nama_kategori FROM kategori_dokumen WHERE id=$kategori");
    $row_k = $kategori_result->fetch_assoc();
    $kategori_nama = $row_k['nama_kategori'] ?? '';

    if($kategori=='' || $tahun==''){
        $msg = "Kategori dan Tahun harus diisi!";
    } elseif(in_array($kategori_nama, $wajib_nomor) && $nomor_surat==''){
        $msg = "Nomor surat wajib diisi untuk dokumen ini!";
    } else {
        $file = $_FILES['file_dokumen'];
        $filename = $file['name'];
        $tmpname = $file['tmp_name'];
        $fileext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowed = ['pdf','doc','docx','xls','xlsx'];

        if(!in_array($fileext, $allowed)){
            $msg = "Format file tidak diperbolehkan!";
        } else {
            $upload_dir = "uploads/bmn/";
            if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            if(move_uploaded_file($tmpname, $upload_dir.$filename)){
                $stmt = $conn->prepare("INSERT INTO dokumen_bmn (kategori, nomor_surat, tahun, file) VALUES (?,?,?,?)");
                $stmt->bind_param("isis",$kategori,$nomor_surat,$tahun,$filename);
                if($stmt->execute()) $msg = "Dokumen berhasil diupload!";
                else $msg = "Gagal menyimpan data ke database!";
            } else $msg = "Gagal mengupload file!";
        }
    }
}

// ==============================
// Filter & Query Dokumen
// ==============================
$filter_kategori = $_GET['kategori'] ?? '';
$filter_tahun = $_GET['tahun'] ?? '';
$search = $_GET['search'] ?? '';
$page = intval($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page -1)*$limit;

// Ambil semua kategori untuk dropdown
$kategori_result = $conn->query("SELECT * FROM kategori_dokumen");

// Query dokumen
$query = "SELECT db.id, db.nomor_surat, db.tahun, db.file, k.nama_kategori
          FROM dokumen_bmn db
          LEFT JOIN kategori_dokumen k ON db.kategori = k.id
          WHERE 1=1";
if($filter_kategori != '') $query .= " AND db.kategori = ".$conn->real_escape_string($filter_kategori);
if($filter_tahun != '') $query .= " AND db.tahun = ".$conn->real_escape_string($filter_tahun);
if($search != '') $query .= " AND (db.nomor_surat LIKE '%".$conn->real_escape_string($search)."%' OR k.nama_kategori LIKE '%".$conn->real_escape_string($search)."%')";
$total_result = $conn->query($query);
$total_rows = $total_result->num_rows;
$query .= " ORDER BY db.id DESC LIMIT $offset,$limit";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Upload & Daftar Dokumen BMN</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans p-6">

<div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">📄 Upload & Daftar Dokumen BMN</h1>
    <div class="flex gap-2">
        <a href="dashboard_bmn.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center gap-1">🏠 Dashboard</a>
        <!--<a href="javascript:history.back()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 flex items-center gap-1">🔙 Back</a>-->
    </div>
</div>

<?php if($msg != ""): ?>
    <div class="mb-4 p-2 bg-blue-100 text-blue-800 rounded"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- Form Upload -->
<form method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded shadow-md mb-6 space-y-4">
    <div class="grid md:grid-cols-3 gap-4">
        <div>
            <label class="block mb-1 font-medium">Kategori Dokumen</label>
            <select name="kategori" required class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-400">
                <option value="">-- Pilih Kategori --</option>
                <?php 
                $kategori_result->data_seek(0);
                while($row = $kategori_result->fetch_assoc()): ?>
                    <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nama_kategori']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label class="block mb-1 font-medium">Nomor Surat</label>
            <input type="text" name="nomor_surat" class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-400" placeholder="Opsional kecuali wajib">
        </div>
        <div>
            <label class="block mb-1 font-medium">Tahun</label>
            <select name="tahun" class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-400">
                <?php for($y=date('Y'); $y>=2000; $y--): ?>
                    <option value="<?= $y ?>"><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
    </div>
    <div>
        <label class="block mb-1 font-medium">File Dokumen</label>
        <input type="file" name="file_dokumen" required class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-400">
        <p class="text-sm text-gray-500 mt-1">Format: pdf, doc, docx, xls, xlsx</p>
    </div>
    <button type="submit" name="upload" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 w-full md:w-auto">Upload Dokumen</button>
</form>

<!-- Filter & Tabel -->
<form method="GET" class="mb-4 flex flex-wrap gap-4 items-end">
    <div>
        <label class="block mb-1">Kategori Dokumen</label>
        <select name="kategori" class="p-2 border rounded focus:ring-2 focus:ring-blue-400">
            <option value="">-- Semua --</option>
            <?php 
            $kategori_result->data_seek(0);
            while($row = $kategori_result->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>" <?= ($filter_kategori==$row['id'])?'selected':'' ?>><?= htmlspecialchars($row['nama_kategori']) ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div>
        <label class="block mb-1">Tahun</label>
        <select name="tahun" class="p-2 border rounded focus:ring-2 focus:ring-blue-400">
            <option value="">-- Semua --</option>
            <?php for($y=date('Y'); $y>=2000; $y--): ?>
                <option value="<?= $y ?>" <?= ($filter_tahun==$y)?'selected':'' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div>
        <label class="block mb-1">Cari</label>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari..." class="p-2 border rounded focus:ring-2 focus:ring-blue-400">
    </div>
    <div class="flex gap-2">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Filter</button>
        <a href="upload_bmn.php" class="bg-gray-400 text-white px-4 py-2 rounded hover:bg-gray-500">Reset</a>
    </div>
</form>

<div class="overflow-x-auto bg-white shadow rounded">
    <table class="min-w-full table-auto">
        <thead class="bg-gray-200">
            <tr>
                <th class="px-4 py-2 text-left">No</th>
                <th class="px-4 py-2 text-left">Nomor Surat</th>
                <th class="px-4 py-2 text-left">Kategori Dokumen</th>
                <th class="px-4 py-2 text-left">Tahun</th>
                <th class="px-4 py-2 text-left">Nama File</th>
                <th class="px-4 py-2 text-left">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if($result->num_rows>0): $no=$offset+1; while($row=$result->fetch_assoc()): ?>
            <tr class="border-b hover:bg-gray-50">
                <td class="px-4 py-2"><?= $no++ ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($row['nomor_surat'] ?? '-') ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($row['nama_kategori']) ?></td>
                <td class="px-4 py-2"><?= $row['tahun'] ?></td>
                <td class="px-4 py-2">
                    <a href="uploads/bmn/<?= $row['file'] ?>" target="_blank" class="text-blue-600 hover:underline"><?= htmlspecialchars($row['file']) ?></a>
                </td>
                <td class="px-4 py-2 flex gap-2">
                    <a href="edit_bmn.php?id=<?= $row['id'] ?>" class="bg-yellow-500 text-white px-2 py-1 rounded hover:bg-yellow-600">Edit</a>
                    <a href="hapus_bmn.php?id=<?= $row['id'] ?>" onclick="return confirm('Yakin hapus dokumen?')" class="bg-red-600 text-white px-2 py-1 rounded hover:bg-red-700">Hapus</a>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr>
                <td colspan="6" class="px-4 py-2 text-center text-gray-500">Tidak ada dokumen ditemukan.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
