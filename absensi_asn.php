<?php
session_start();
include "config.php";

// Proteksi: hanya pimpinan & kepegawaian
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['pimpinan', 'kepegawaian'])) {
    header("Location: login.php");
    exit;
}

// Ambil data absensi ASN (izin pegawai)
$sql = "
    SELECT i.id, i.jenis, i.tanggal_mulai, i.tanggal_selesai, i.file_surat, i.created_at,
           p.nama_lengkap, p.nip, p.jabatan
    FROM izin_pegawai i
    LEFT JOIN pegawai p ON i.pegawai_id = p.id
    ORDER BY i.created_at DESC
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Absensi ASN</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; }
    .content-wrapper { margin-left: 250px; padding: 20px; }
  </style>
</head>
<body>
  <!-- Sidebar otomatis sesuai role -->
  <?php include "sidebar.php"; ?>

  <div class="content-wrapper">
    <h3 class="mb-3"><i class="bi bi-calendar-check"></i> Absensi ASN (Izin / Kehadiran)</h3>

    <div class="card shadow-sm">
      <div class="card-body">
        <?php if ($result && $result->num_rows > 0): ?>
        <div class="table-responsive">
          <table class="table table-bordered table-striped table-hover align-middle">
            <thead class="table-dark text-center">
              <tr>
                <th>Nama</th>
                <th>NIP</th>
                <th>Jabatan</th>
                <th>Jenis Izin</th>
                <th>Tanggal Mulai</th>
                <th>Tanggal Selesai</th>
                <th>Diajukan</th>
                <th>File</th>
              </tr>
            </thead>
            <tbody>
              <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($row['nama_lengkap'] ?? '-') ?></td>
                  <td><?= htmlspecialchars($row['nip'] ?? '-') ?></td>
                  <td><?= htmlspecialchars($row['jabatan'] ?? '-') ?></td>
                  <td><?= ucfirst(str_replace('_',' ', $row['jenis'])) ?></td>
                  <td><?= date("d-m-Y", strtotime($row['tanggal_mulai'])) ?></td>
                  <td><?= date("d-m-Y", strtotime($row['tanggal_selesai'])) ?></td>
                  <td><?= date("d-m-Y H:i", strtotime($row['created_at'])) ?></td>
                  <td class="text-center">
                    <?php if (!empty($row['file_surat'])): ?>
                      <a href="uploads/izin/<?= htmlspecialchars($row['file_surat']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-paperclip"></i> Lihat
                      </a>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
          <p class="text-muted">Belum ada data absensi ASN.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
