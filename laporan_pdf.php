<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 🚫 Hanya admin boleh akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak!");
}

// ==== Autoload Dompdf ==== //
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php'; // composer install
} elseif (file_exists(__DIR__ . '/dompdf/autoload.inc.php')) {
    require __DIR__ . '/dompdf/autoload.inc.php'; // manual install
} else {
    die("Dompdf belum terinstall. Silakan install via Composer atau upload manual.");
}

use Dompdf\Dompdf;

// Gunakan koneksi
$db = $conn;

// Statistik sederhana
function getCount($db, $table){
    $q = $db->query("SHOW TABLES LIKE '$table'");
    if(!$q || $q->num_rows==0){ return 0; }
    $res = $db->query("SELECT COUNT(*) as jml FROM $table");
    if(!$res){ return 0; }
    $row = $res->fetch_assoc();
    return $row['jml'] ?? 0;
}

$totalUsers     = getCount($db, "users");
$totalDocs      = getCount($db, "documents");
$totalFolders   = getCount($db, "folders");
$totalTransfers = getCount($db, "transfers");

// Buat HTML laporan
$html = "
<h2 style='text-align:center;'>📊 Laporan Statistik Sistem Arsip</h2>
<hr>
<table border='1' cellspacing='0' cellpadding='8' width='100%' style='border-collapse: collapse;'>
  <tr><th align='left'>Total Pengguna</th><td>$totalUsers</td></tr>
  <tr><th align='left'>Total Dokumen</th><td>$totalDocs</td></tr>
  <tr><th align='left'>Total Folder</th><td>$totalFolders</td></tr>
  <tr><th align='left'>Total Transfer</th><td>$totalTransfers</td></tr>
</table>
<p style='margin-top:20px;'>Dicetak pada: ".date('d-m-Y H:i')."</p>
";

// Inisialisasi Dompdf
$dompdf = new Dompdf();
$dompdf->loadHtml($html);

// Set ukuran kertas & orientasi
$dompdf->setPaper('A4', 'portrait');

// Render ke PDF
$dompdf->render();

// Output ke browser (langsung tampil, bukan download)
$dompdf->stream("laporan-arsip.pdf", ["Attachment" => false]);
