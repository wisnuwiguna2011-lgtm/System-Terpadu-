<?php
include "config.php";

$kegiatan_id = $_GET['kegiatan_id'] ?? 0;
$kegiatan = $conn->query("SELECT * FROM kegiatan WHERE id=$kegiatan_id")->fetch_assoc();

if(!$kegiatan) {
    die("Kegiatan tidak ditemukan!");
}

// daftar jenis file
$jenis_wajib = [
    "Surat Undangan",
    "Surat Tugas",
    "KAK/ TOR/ Design",
    "Daftar hadir",
    "Surat perintah bayar",
    "SPP",
    "Kuitansi",
    "Surat Perintah pencairan",
    "SPPD Kegiatan",
    "Notulensi",
    "Laporan"
];

// proses upload file
if(isset($_POST['upload'])) {
    $jenis_file = $_POST['jenis_file'];
    $nama_file = $_POST['nama_file'];
    $file = $_FILES['file'];

    if($file['error'] == 0) {
        $upload_dir = "uploads/";
        if(!is_dir($upload_dir)) mkdir($upload_dir);

        $filename = time()."_".basename($file['name']);
        $path = $upload_dir.$filename;

        if(move_uploaded_file($file['tmp_name'], $path)) {
            $stmt = $conn->prepare("INSERT INTO files (kegiatan_id, nama_file, jenis_file, nama_asli, path_file) VALUES (?,?,?,?,?)");
            $stmt->bind_param("issss", $kegiatan_id, $nama_file, $jenis_file, $file['name'], $path);
            $stmt->execute();
            echo "<div class='alert alert-success'>File berhasil diupload!</div>";
        } else {
            echo "<div class='alert alert-danger'>Gagal upload file!</div>";
        }
    }
}

// ambil file yang sudah diupload
$files = $conn->query("SELECT * FROM files WHERE kegiatan_id=$kegiatan_id");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Detail Kegiatan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <a href="user_dashboard.php" class="btn btn-secondary mb-3"><i class="bi bi-arrow-left"></i> Kembali</a>
    <h3>📂 Detail Kegiatan</h3>
    <p><strong>Nama:</strong> <?= htmlspecialchars($kegiatan['nama_kegiatan']) ?><br>
       <strong>Tanggal:</strong> <?= date("d-m-Y", strtotime($kegiatan['tanggal'])) ?><br>
       <strong>Jenis:</strong> <?= htmlspecialchars($kegiatan['jenis_kegiatan']) ?></p>

    <!-- Form Upload -->
    <div class="card p-3 mb-4">
        <h5>Upload File</h5>
        <form method="post" enctype="multipart/form-data" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Jenis File</label>
                <select name="jenis_file" class="form-select" required>
                    <option value="">-- Pilih Jenis File --</option>
                    <?php foreach($jenis_wajib as $jw): ?>
                        <option value="<?= $jw ?>"><?= $jw ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Nama File</label>
                <input type="text" name="nama_file" class="form-control" placeholder="Contoh: Undangan Peserta" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Pilih File</label>
                <input type="file" name="file" class="form-control" required>
            </div>
            <div class="col-12">
                <button type="submit" name="upload" class="btn btn-success"><i class="bi bi-upload"></i> Upload</button>
            </div>
        </form>
    </div>

    <!-- Daftar File -->
    <div class="card p-3">
        <h5>File yang sudah diupload</h5>
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>No</th>
                    <th>Nama File</th>
                    <th>Jenis File</th>
                    <th>Tanggal Upload</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no=1; while($f=$files->fetch_assoc()): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($f['nama_file']) ?></td>
                    <td><?= htmlspecialchars($f['jenis_file']) ?></td>
                    <td><?= date("d-m-Y H:i", strtotime($f['tanggal_upload'])) ?></td>
                    <td>
                        <a href="<?= $f['path_file'] ?>" target="_blank" class="btn btn-sm btn-info"><i class="bi bi-eye"></i> Lihat</a>
                        <a href="hapus_file.php?id=<?= $f['id'] ?>&kegiatan_id=<?= $kegiatan_id ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus file ini?')"><i class="bi bi-trash"></i> Hapus</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
