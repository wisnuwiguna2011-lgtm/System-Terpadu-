<?php
session_start();
include 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'keuangan') {
    http_response_code(403);
    echo "Akses ditolak";
    exit;
}

// Ambil parameter
$folder_id = intval($_GET['folder_id'] ?? 0);
$year = trim($_GET['year'] ?? '');
$username = $_SESSION['username'] ?? 'User Keuangan';
$datetime_now = date("d-m-Y H:i:s");

if (!$folder_id) {
    echo "Folder tidak ditemukan.";
    exit;
}

// Ambil info folder
$stmt = $conn->prepare("SELECT * FROM folders WHERE id=?");
$stmt->bind_param("i", $folder_id);
$stmt->execute();
$folder_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$folder_info) {
    echo "Folder tidak ditemukan.";
    exit;
}

// Hitung jumlah file dengan fallback aman
if ($year) {
    $stmt_count = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM files
        WHERE folder_id=? AND (YEAR(uploaded_at)=? OR LEFT(uploaded_at,4)=?)
    ");
    $stmt_count->bind_param("iis", $folder_id, $year, $year);
} else {
    $stmt_count = $conn->prepare("SELECT COUNT(*) AS total FROM files WHERE folder_id=?");
    $stmt_count->bind_param("i", $folder_id);
}
$stmt_count->execute();
$result = $stmt_count->get_result();
$files_count = $result->fetch_assoc()['total'] ?? 0;
$stmt_count->close();

// Ambil daftar file
if ($year) {
    $stmt_files = $conn->prepare("
        SELECT * FROM files 
        WHERE folder_id=? AND (YEAR(uploaded_at)=? OR LEFT(uploaded_at,4)=?)
        ORDER BY uploaded_at ASC
    ");
    $stmt_files->bind_param("iis", $folder_id, $year, $year);
} else {
    $stmt_files = $conn->prepare("SELECT * FROM files WHERE folder_id=? ORDER BY uploaded_at ASC");
    $stmt_files->bind_param("i", $folder_id);
}
$stmt_files->execute();
$files_result = $stmt_files->get_result();
$stmt_files->close();

if (empty($folder_info['tahun_kegiatan'])) $folder_info['tahun_kegiatan'] = date("Y");
?>

<div class="page-container mt-4">
    <div class="header-logo-text">
        <img id="logoKemendikbud" src="logo_1.png" alt="Logo Kemendikbud">
        <span>Direktorat Jenderal Sains dan Teknologi</span>
    </div>

    <div class="text-lg font-bold mb-2 text-center">Daftar Berkas</div>

    <table class="print-table">
        <tr><th>Nomor SPM</th><td><?= htmlspecialchars($folder_info['nama_folder'] ?? '-') ?></td></tr>
        <tr><th>Tahun</th><td><?= htmlspecialchars($year ?: $folder_info['tahun_kegiatan']) ?></td></tr>
        <tr><th>Jumlah Berkas</th><td><?= $files_count ?> file</td></tr>
        <tr><th>Dicetak oleh</th><td><?= htmlspecialchars($username) ?> (<?= $datetime_now ?>)</td></tr>
    </table>

    <?php if($files_count > 0): ?>
    <table class="isi-table mt-4">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama File</th>
                <th>Tanggal Upload</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
        <?php $i=1; while($row = $files_result->fetch_assoc()): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($row['file_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['uploaded_at'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['description'] ?? '-') ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p class="mt-2 text-center font-semibold">Tidak ada berkas untuk tahun ini.</p>
    <?php endif; ?>
</div>
