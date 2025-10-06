<?php
session_start();
include "config.php";

// Proteksi role hanya untuk kepegawaian
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kepegawaian') {
    header("Location: login.php");
    exit;
}

// Ambil semua data izin join pegawai
$sql = "SELECT izin_pegawai.*, pegawai.nama_lengkap, pegawai.nip
        FROM izin_pegawai
        JOIN pegawai ON izin_pegawai.pegawai_id = pegawai.id
        ORDER BY izin_pegawai.created_at DESC";
$result = $conn->query($sql);
$izinList = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Riwayat Kehadiran / Izin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background-color: #f8f9fa; }
.content { margin-left: 250px; padding: 20px; }
.card { border: none; border-radius: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
.table thead { background: #e2e8f0; }
.table td, .table th { vertical-align: middle; }
</style>
</head>
<body>
<?php include "sidebar_kepegawaian.php"; ?>

<div class="content">
    <h3 class="fw-bold mb-4"><i class="bi bi-calendar-check"></i> Riwayat Kehadiran / Izin</h3>

    <div class="card">
        <div class="card-header bg-dark text-white fw-bold">
            <i class="bi bi-file-earmark-text"></i> Semua Pengajuan Izin Pegawai
        </div>
        <div class="card-body">
            <?php if ($izinList): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>NIP</th>
                            <th>Nama Pegawai</th>
                            <th>Jenis Izin</th>
                            <th>Mulai</th>
                            <th>Selesai</th>
                            <th>Keterangan</th>
                            <th>File Surat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($izinList as $i): ?>
                        <tr>
                            <td><?= htmlspecialchars($i['nip']) ?></td>
                            <td><?= htmlspecialchars($i['nama_lengkap']) ?></td>
                            <td><span class="badge bg-info"><?= ucfirst(str_replace('_',' ',$i['jenis'])) ?></span></td>
                            <td><?= date("d-m-Y", strtotime($i['tanggal_mulai'])) ?></td>
                            <td><?= date("d-m-Y", strtotime($i['tanggal_selesai'])) ?></td>
                            <td><?= htmlspecialchars($i['keterangan'] ?: '-') ?></td>
                            <td>
                                <?php if ($i['file_surat']): ?>
                                <a href="uploads/izin/<?= htmlspecialchars($i['file_surat']) ?>" 
                                   target="_blank" class="btn btn-sm btn-outline-primary">
                                   <i class="bi bi-paperclip"></i> Lihat
                                </a>
                                <?php else: ?> - <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p class="text-muted">Belum ada pengajuan izin dari pegawai.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
