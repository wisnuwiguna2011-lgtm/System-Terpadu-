<?php
session_start();
include "config.php";

if(!isset($_SESSION['role']) || $_SESSION['role']!=='pegawai'){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Tandai semua notifikasi baru sebagai dibaca
$conn->query("UPDATE notifikasi SET status='dibaca' WHERE (user_id IS NULL OR user_id=$user_id) AND status='baru'");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Notifikasi Pegawai</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { font-family:"Segoe UI", sans-serif; background:#f5f7fa;}
.content { margin-left:240px; padding:20px; }
.table td, .table th { vertical-align: middle; }
</style>
</head>
<body>
<?php include "sidebar_pegawai.php"; ?>

<div class="content">
<h3 class="mb-4"><i class="bi bi-bell-fill text-warning"></i> Notifikasi Saya</h3>

<div class="card shadow-sm">
  <div class="card-body">
    <table class="table table-bordered table-striped" id="table-notifikasi">
      <thead class="table-dark text-center">
        <tr>
          <th>#</th>
          <th>Pesan</th>
          <th>Status</th>
          <th>Tanggal</th>
        </tr>
      </thead>
      <tbody>
        <!-- Akan diisi AJAX -->
      </tbody>
    </table>
  </div>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
function loadNotifikasi() {
    $.getJSON('get_notifikasi.php', function(data){
        let tbody='';
        data.list.forEach(function(n,i){
            tbody+=`<tr>
                <td class="text-center">${i+1}</td>
                <td>${n.pesan}</td>
                <td class="text-center">${n.status=='baru'?'<span class="badge bg-warning">Baru</span>':'<span class="badge bg-success">Dibaca</span>'}</td>
                <td class="text-center">${n.waktu}</td>
            </tr>`;
        });
        if(data.list.length==0) tbody='<tr><td colspan="4" class="text-center">Belum ada notifikasi.</td></tr>';
        $('#table-notifikasi tbody').html(tbody);

        // Dot merah di sidebar
        if(data.jml_baru>0){
            $('.sidebar .notif-dot').show();
        } else {
            $('.sidebar .notif-dot').hide();
        }
    });
}
loadNotifikasi();
setInterval(loadNotifikasi,5000);
</script>
</body>
</html>
