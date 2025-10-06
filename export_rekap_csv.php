<?php
session_start();
include "config.php";
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pimpinan') { header("Location: login.php"); exit; }

$start = $_GET['start'] ?? '';
$end = $_GET['end'] ?? '';
$pegawai_id = isset($_GET['pegawai_id']) ? intval($_GET['pegawai_id']) : 0;

$sql = "SELECT p.nama_lengkap, p.nip, p.jabatan, COUNT(l.id) AS jumlah_log, IFNULL(AVG(l.nilai_rata),0) AS rata_nilai
        FROM log_harian l
        JOIN pegawai p ON l.pegawai_id = p.id
        WHERE 1=1";
if ($pegawai_id) $sql .= " AND p.id = ".intval($pegawai_id);
if ($start && $end) $sql .= " AND l.tanggal BETWEEN '".$conn->real_escape_string($start)."' AND '".$conn->real_escape_string($end)."'";
$sql .= " GROUP BY p.id, p.nama_lengkap, p.nip, p.jabatan ORDER BY rata_nilai DESC";

$res = $conn->query($sql);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=rekap_penilaian_'.date('Ymd_His').'.csv');
$out = fopen('php://output','w');
fputcsv($out, ['No','Nama Pegawai','NIP','Jabatan','Jumlah Log','Rata-rata Nilai']);

$no=1;
while($r = $res->fetch_assoc()){
    fputcsv($out, [$no++, $r['nama_lengkap'], $r['nip'], $r['jabatan'], $r['jumlah_log'], number_format((float)$r['rata_nilai'],2)]);
}
fclose($out);
exit;
