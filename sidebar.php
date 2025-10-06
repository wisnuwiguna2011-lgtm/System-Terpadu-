<?php
// sidebar.php
session_start();
$role = $_SESSION['role'] ?? '';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
  body { margin:0; font-family: "Segoe UI", sans-serif; }
  .sidebar {
    width:250px;
    min-height:100vh;
    background:linear-gradient(180deg,#2c3e50,#34495e);
    color:white;
    position:fixed;
    top:0; left:0;
    overflow-y:auto;
  }
  .sidebar h4 {
    padding:20px;
    margin:0;
    background:rgba(0,0,0,0.2);
    font-weight:600;
  }
  .sidebar a {
    display:block;
    color:white;
    text-decoration:none;
    padding:12px 20px;
    transition:0.2s;
  }
  .sidebar a:hover { background:rgba(255,255,255,0.1); }
  .sidebar a.active { background:#1abc9c; font-weight:bold; }
  .content-wrapper { margin-left:250px; padding:30px; }
  @media(max-width:768px){
    .sidebar { width:200px; }
    .content-wrapper { margin-left:200px; padding:15px; }
  }
</style>

<div class="sidebar">
  <?php if($role === 'pimpinan'): ?>
    <h4><i class="bi bi-person-badge-fill"></i> Pimpinan</h4>
    <a href="dashboard_pimpinan.php" class="<?= $current_page=='dashboard_pimpinan.php'?'active':'' ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="absensi_asn.php" class="<?= $current_page=='absensi_asn.php'?'active':'' ?>"><i class="bi bi-calendar-check"></i> Absensi ASN</a>
    <a href="notifikasi.php" class="<?= $current_page=='notifikasi.php'?'active':'' ?>"><i class="bi bi-bell-fill"></i> Notifikasi</a>
    <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>

  <?php elseif($role === 'kepegawaian'): ?>
    <h4><i class="bi bi-people-fill"></i> Kepegawaian</h4>
    <a href="dashboard_kepegawaian.php" class="<?= $current_page=='dashboard_kepegawaian.php'?'active':'' ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="daftar_pegawai.php" class="<?= $current_page=='daftar_pegawai.php'?'active':'' ?>"><i class="bi bi-person-lines-fill"></i> Data Pegawai</a>
    <a href="tambah_pegawai.php" class="<?= $current_page=='tambah_pegawai.php'?'active':'' ?>"><i class="bi bi-person-plus-fill"></i> Tambah Pegawai</a>
    <a href="jadwal_kenaikan.php" class="<?= $current_page=='jadwal_kenaikan.php'?'active':'' ?>"><i class="bi bi-calendar-check"></i> Jadwal Kenaikan</a>
    <a href="absensi_asn.php" class="<?= $current_page=='absensi_asn.php'?'active':'' ?>"><i class="bi bi-calendar-check"></i> Absensi ASN</a>
    <a href="notifikasi.php" class="<?= $current_page=='notifikasi.php'?'active':'' ?>"><i class="bi bi-bell-fill"></i> Notifikasi</a>
    <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
  <?php endif; ?>
</div>
