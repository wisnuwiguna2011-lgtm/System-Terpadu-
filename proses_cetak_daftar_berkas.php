<?php
session_start();
include 'config.php';
require('fpdf/fpdf.php'); // pastikan sudah ada library fpdf

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'keuangan') {
    header("Location: login.php");
    exit;
}

$username        = $_SESSION['username'] ?? 'User Keuangan';
$selected_year   = $_GET['year'] ?? '';
$selected_folder = $_GET['folder_id'] ?? '';
$datetime_now    = date("d-m-Y H:i:s");

// Validasi input
if ($selected_year !== '' && !preg_match('/^\d{4}$/', $selected_year)) die("Tahun tidak valid");
$selected_folder = intval($selected_folder);

// Ambil info folder
$stmt = $conn->prepare("SELECT * FROM folders WHERE id=?");
$stmt->bind_param("i", $selected_folder);
$stmt->execute();
$folder_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$folder_info) die("Folder tidak ditemukan");

// Hitung jumlah file
$stmt_count = $conn->prepare("SELECT COUNT(*) AS total FROM files WHERE folder_id=?");
$stmt_count->bind_param("i", $selected_folder);
$stmt_count->execute();
$files_count = $stmt_count->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_count->close();

// Buat PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',14);

// Judul
$pdf->Cell(0,10,'DAFTAR BERKAS',0,1,'C');
$pdf->Ln(5);

// Info SPM
$pdf->SetFont('Arial','',12);
$pdf->Cell(50,8,'Nomor SPM',1);
$pdf->Cell(0,8,$folder_info['nama_folder'],1,1);

$pdf->Cell(50,8,'Tahun',1);
$pdf->Cell(0,8,$selected_year,1,1);

$pdf->Cell(50,8,'Jumlah Berkas',1);
$pdf->Cell(0,8,$files_count.' file',1,1);

$pdf->Cell(50,8,'Dicetak oleh',1);
$pdf->Cell(0,8,$username.' ('.$datetime_now.')',1,1);

// Output PDF
$pdf->Output('I','Daftar_Berkas_'.$folder_info['nama_folder'].'.pdf');
exit;
?>
