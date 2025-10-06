<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
function isActive($pages) {
    global $current_page;
    if (is_array($pages)) {
        return in_array($current_page, $pages) ? 'active' : '';
    }
    return ($current_page == $pages) ? 'active' : '';
}
?>

<style>
.sidebar {
    width: 250px;
    height: 100vh;
    overflow-y: auto;
    background: linear-gradient(180deg,#2c3e50,#34495e);
    color: white;
    position: fixed;
    top: 0;
    left: 0;
    padding-top: 1rem;
}
.sidebar h4 { color:#ffc107; padding-left:20px; margin-bottom:20px; }
.sidebar a {
    color:white;
    text-decoration:none;
    display:block;
    padding:10px 20px;
    transition:0.2s;
    cursor:pointer;
}
.sidebar a:hover, .sidebar a.active { background:#1abc9c; font-weight:bold; border-radius:5px; }
.submenu { display:none; padding-left:20px; background: rgba(255,255,255,0.05); }
.submenu a { padding:8px 20px; font-size:0.9rem; }
.toggle-btn::after { content: "\25BC"; float:right; transition: transform 0.3s; }
.toggle-btn.open::after { transform: rotate(-180deg); }
.content { margin-left:250px; padding:20px; }
</style>

<div class="sidebar">
    <h4><i class="bi bi-people-fill"></i> Kepegawaian</h4>

    <a href="dashboard_kepegawaian.php" class="<?= isActive('dashboard_kepegawaian.php') ?>">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <a class="toggle-btn <?= isActive(['daftar_pegawai.php']) ?>">
        <i class="bi bi-person-lines-fill"></i> Data Pegawai
    </a>
    <div class="submenu">
        <a href="daftar_pegawai.php" class="<?= isActive('daftar_pegawai.php') ?>">Daftar Pegawai</a>
    </div>

    <a class="toggle-btn <?= isActive(['pangkat_golongan.php','gaji.php','jabatan.php']) ?>">
        <i class="bi bi-bar-chart-line"></i> Pangkat & Golongan
    </a>
    <div class="submenu">
        <a href="pangkat_golongan.php" class="<?= isActive('pangkat_golongan.php') ?>">Pangkat & Golongan</a>
        <a href="gaji.php" class="<?= isActive('gaji.php') ?>">Gaji</a>
        <a href="jabatan.php" class="<?= isActive('jabatan.php') ?>">Jabatan</a>
    </div>

    <a class="toggle-btn <?= isActive(['skp_tahunan.php','hasil_log_harian.php','kehadiran_kepegawaian.php']) ?>">
        <i class="bi bi-card-checklist"></i> Kinerja & Penilaian
    </a>
    <div class="submenu">
        <a href="skp_tahunan.php" class="<?= isActive('skp_tahunan.php') ?>">SKP Tahunan</a>
        <a href="hasil_log_harian.php" class="<?= isActive('hasil_log_harian.php') ?>">Hasil Log Harian Pegawai</a>
        <a href="kehadiran_kepegawaian.php" class="<?= isActive('kehadiran_kepegawaian.php') ?>">Kehadiran / Izin</a>
    </div>

    <a class="toggle-btn <?= isActive(['reminder_kgb.php','reminder_pangkat.php','notifikasi_promosi.php']) ?>">
        <i class="bi bi-clock-history"></i> Kenaikan Otomatis
    </a>
    <div class="submenu">
        <a href="reminder_kgb.php" class="<?= isActive('reminder_kgb.php') ?>">Reminder KGB</a>
        <a href="reminder_pangkat.php" class="<?= isActive('reminder_pangkat.php') ?>">Reminder Pangkat</a>
        <a href="notifikasi_promosi.php" class="<?= isActive('notifikasi_promosi.php') ?>">Notifikasi Promosi</a>
    </div>

    <a class="toggle-btn <?= isActive(['riwayat_diklat.php','persyaratan_jabatan.php']) ?>">
        <i class="bi bi-journal-bookmark"></i> Diklat & Sertifikasi
    </a>
    <div class="submenu">
        <a href="riwayat_diklat.php" class="<?= isActive('riwayat_diklat.php') ?>">Riwayat Diklat</a>
        <a href="persyaratan_jabatan.php" class="<?= isActive('persyaratan_jabatan.php') ?>">Persyaratan Jabatan</a>
    </div>

    <a class="toggle-btn <?= isActive(['satyalancana.php','catatan_disiplin.php']) ?>">
        <i class="bi bi-award"></i> Penghargaan & Disiplin
    </a>
    <div class="submenu">
        <a href="satyalancana.php" class="<?= isActive('satyalancana.php') ?>">Satyalancana</a>
        <a href="catatan_disiplin.php" class="<?= isActive('catatan_disiplin.php') ?>">Catatan Disiplin</a>
    </div>

    <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>

<script>
document.querySelectorAll('.toggle-btn').forEach(btn=>{
    btn.addEventListener('click',()=>{
        btn.classList.toggle('open');
        let submenu = btn.nextElementSibling;
        submenu.style.display = (submenu.style.display==="block")?"none":"block";
    });
});

// Auto buka submenu jika ada halaman aktif
document.querySelectorAll('.submenu').forEach(sub=>{
    if(sub.querySelector('.active')){
        sub.style.display='block';
        sub.previousElementSibling.classList.add('open');
    }
});
</script>
