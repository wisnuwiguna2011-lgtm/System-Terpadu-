<!-- sidebar_pegawai.php -->
<div class="sidebar d-flex flex-column">
  <h4><i class="bi bi-person-badge"></i> Pegawai</h4>
  
  <a href="dashboard_pegawai.php" class="<?= basename($_SERVER['PHP_SELF'])=='dashboard_pegawai.php' ? 'active' : '' ?>">
    <i class="bi bi-house-door"></i> Beranda
  </a>
  
  <a href="profil_pegawai.php" class="<?= basename($_SERVER['PHP_SELF'])=='profil_pegawai.php' ? 'active' : '' ?>">
    <i class="bi bi-person-circle"></i> Profil Saya
  </a>
  
  <a href="kehadiran.php" class="<?= basename($_SERVER['PHP_SELF'])=='kehadiran.php' ? 'active' : '' ?>">
    <i class="bi bi-calendar-check"></i> Kehadiran / Izin
  </a>
  
  <a href="log_harian.php" class="<?= basename($_SERVER['PHP_SELF'])=='log_harian.php' ? 'active' : '' ?>">
    <i class="bi bi-journal-text"></i> Log Harian
  </a>
  
  <a href="notifikasi_pegawai.php" class="<?= basename($_SERVER['PHP_SELF'])=='notifikasi_pegawai.php' ? 'active' : '' ?>">
    <i class="bi bi-bell-fill"></i> Notifikasi
    <span class="notif-dot"></span>
  </a>
  
  <a href="bantuan.php"><i class="bi bi-question-circle"></i> Bantuan</a>
  <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>

<style>
.sidebar {
    width: 240px; min-height: 100vh; background: #1e293b; color: white; position: fixed; top:0; left:0; padding-top:1rem;
}
.sidebar h4 { color:#ffc107; padding-left:20px; margin-bottom:20px; }
.sidebar a { color:white; text-decoration:none; display:block; padding:12px 20px; transition:0.2s; position:relative; }
.sidebar a:hover, .sidebar a.active { background:#334155; border-radius:8px; }
.content { margin-left:240px; padding:20px; }
.sidebar .notif-dot {
    position: absolute; top:12px; right:25px; width:10px; height:10px; background:red; border-radius:50%; display:none;
}
</style>
