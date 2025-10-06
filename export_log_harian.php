<?php
session_start();
include "config.php";

// Proteksi role kepegawaian
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kepegawaian') {
    header("Location: login.php");
    exit;
}

require 'vendor/autoload.php'; // Path autoload PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Ambil filter
$pegawai_id = $_GET['pegawai_id'] ?? 'all';
$bulan      = $_GET['bulan'] ?? ''; // format YYYY-MM

// Ambil data log harian
$sql = "SELECT lh.*, p.nama_lengkap, p.nip 
        FROM log_harian lh
        JOIN pegawai p ON lh.pegawai_id = p.id
        WHERE 1=1";
$params = [];
$types = "";

// Filter pegawai
if ($pegawai_id !== 'all') {
    $sql .= " AND lh.pegawai_id = ?";
    $params[] = $pegawai_id;
    $types .= "i";
}

// Filter bulan
if ($bulan) {
    list($year, $month) = explode("-", $bulan);
    $sql .= " AND YEAR(lh.tanggal) = ? AND MONTH(lh.tanggal) = ?";
    $params[] = $year;
    $params[] = $month;
    $types .= "ii";
}

$sql .= " ORDER BY lh.tanggal ASC, lh.id ASC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Buat Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Judul dan header
$sheet->setCellValue('A1', 'Hasil Log Harian Pegawai');
$sheet->mergeCells('A1:G1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

$sheet->setCellValue('A2', 'No');
$sheet->setCellValue('B2', 'NIP');
$sheet->setCellValue('C2', 'Nama Pegawai');
$sheet->setCellValue('D2', 'Tanggal');
$sheet->setCellValue('E2', 'Jam Masuk');
$sheet->setCellValue('F2', 'Jam Keluar');
$sheet->setCellValue('G2', 'Keterangan');

// Isi data
$row = 3;
foreach ($data as $i => $log) {
    $sheet->setCellValue('A'.$row, $i+1);
    $sheet->setCellValue('B'.$row, $log['nip']);
    $sheet->setCellValue('C'.$row, $log['nama_lengkap']);
    $sheet->setCellValue('D'.$row, $log['tanggal']);
    $sheet->setCellValue('E'.$row, $log['jam_masuk'] ?? '-');
    $sheet->setCellValue('F'.$row, $log['jam_keluar'] ?? '-');
    $sheet->setCellValue('G'.$row, $log['keterangan'] ?? '-');
    $row++;
}

// Styling header
$sheet->getStyle('A2:G2')->getFont()->setBold(true);
$sheet->getStyle('A2:G2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

// Auto size kolom
foreach(range('A','G') as $col){
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Download file
$filename = 'Log_Harian_'.($bulan ?: 'Semua').'.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$filename.'"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
