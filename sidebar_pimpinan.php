<?php
// sidebar_pimpinan.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
  #sidebar {
    width: 250px;
    height: 100vh;
    top: 0;
    left: 0;
    background-color: #1e293b;
    color: #fff;
  }
  #sidebar .nav-link {
    color: #d1d5db;
    border-radius: 8px;
    margin: 4px 0;
    transition: 0.2s;
  }
  #sidebar .nav-link:hover,
  #sidebar .nav-link.active {
    background-color: #334155;
    color: #fff;
  }
  #sidebar .sidebar-header {
    font-size: 1.25rem;
    font-weight: bold;
    color: #ffc107;
  }
</style>

<nav id="sidebar" class="d-flex flex-column flex-shrink-0 p-3 position-fixed">
  <!-- Header -->
  <a href="dashboard_pimpinan.php" class="d-flex align-items-center mb-3 text-decoration-none">
    <i class="bi bi-bar-chart-fill me-2 fs-4 text-warning"></i>
    <span class="sidebar-header">Pimpinan</span>
  </a>
  <hr>

  <!-- Menu -->
  <ul class="nav nav-pills flex-column mb-auto">
    <li>
      <a href="dashboard_pimpinan.php" 
         class="nav-link <?= $current_page=='dashboard_pimpinan.php' ? 'active' : '' ?>">
        <i class="bi bi-speedometer2 me-2"></i> Dashboard
      </a>
    </li>
    <li>
      <a href="penilaian_pegawai.php" 
         class="nav-link <?= $current_page=='penilaian_pegawai.php' ? 'active' : '' ?>">
        <i class="bi bi-clipboard-check me-2"></i> Penilaian Harian
      </a>
    </li>
    <li>
      <a href="penilaian_bulanan.php" 
         class="nav-link <?= $current_page=='penilaian_bulanan.php' ? 'active' : '' ?>">
        <i class="bi bi-calendar-check me-2"></i> Penilaian Bulanan
      </a>
    </li>
    <li>
      <a href="rekap_penilaian.php" 
         class="nav-link <?= $current_page=='rekap_penilaian.php' ? 'active' : '' ?>">
        <i class="bi bi-folder-check me-2"></i> Rekap Penilaian
      </a>
    </li>
    <li>
      <a href="absensi_asn.php" 
         class="nav-link <?= $current_page=='absensi_asn.php' ? 'active' : '' ?>">
        <i class="bi bi-people-fill me-2"></i> Absensi ASN
      </a>
    </li>
    <li>
      <a href="logout.php" class="nav-link">
        <i class="bi bi-box-arrow-right me-2"></i> Logout
      </a>
    </li>
  </ul>

  <hr>
  <!-- User Info -->
  <div class="dropdown">
    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" 
       id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
      <i class="bi bi-person-circle me-2 fs-4"></i>
      <strong><?= $_SESSION['username'] ?? 'Pimpinan' ?></strong>
    </a>
    <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser">
      <li><a class="dropdown-item" href="profile.php">Profil</a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item" href="logout.php">Logout</a></li>
    </ul>
  </div>
</nav>
