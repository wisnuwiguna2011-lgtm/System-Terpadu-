<?php if($_SESSION['role']=='admin'): ?>
<div class="col-md-3 mt-4">
  <a href="users.php" class="btn btn-lg btn-primary w-100">
    <i class="fa fa-users"></i> Kelola Pengguna
  </a>
</div>
<?php endif; ?>

<?php 
if(session_status()===PHP_SESSION_NONE) session_start(); 
require_once 'config.php'; 
if(!isset($_SESSION['uid'])){ header("Location:index.php"); exit; }
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Dashboard DMS Keuangan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php"><i class="fa-solid fa-vault me-2"></i>DMS Keuangan</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="scan.php"><i class="fa fa-print me-1"></i>Scan</a></li>
        <li class="nav-item"><a class="nav-link" href="upload.php"><i class="fa fa-upload me-1"></i>Upload</a></li>
        <li class="nav-item"><a class="nav-link" href="folders.php"><i class="fa fa-folder me-1"></i>Folder</a></li>
        <li class="nav-item"><a class="nav-link" href="search.php"><i class="fa fa-search me-1"></i>Cari</a></li>
        <li class="nav-item"><a class="nav-link" href="transfer.php"><i class="fa fa-share me-1"></i>Transfer</a></li>

        <?php if($_SESSION['role']=='admin'): ?>
        <li class="nav-item"><a class="nav-link" href="users.php"><i class="fa fa-users me-1"></i>Pengguna</a></li>
        <?php endif; ?>
      </ul>
      <span class="navbar-text text-white me-3">
        Halo, <?= e($_SESSION['name']) ?> (<?= $_SESSION['role'] ?>)
      </span>
      <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
  </div>
</nav>
<div class="container py-4">
