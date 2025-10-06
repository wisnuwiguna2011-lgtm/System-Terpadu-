<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include "config.php";

// Proteksi role
if(!isset($_SESSION['role']) || $_SESSION['role']!=='kepegawaian'){
    header("Location: login.php");
    exit;
}

// Ambil ID pegawai dari URL
if(!isset($_GET['id'])){
    die("Pegawai tidak ditemukan.");
}
$pegawai_id = intval($_GET['id']);

// Ambil data pegawai
$stmt = $conn->prepare("SELECT id, nip, nama_lengkap, tmt_gol FROM pegawai WHERE id=?");
$stmt->bind_param("i", $pegawai_id);
$stmt->execute();
$pegawai = $stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$pegawai) die("Pegawai tidak ditemukan.");

// Fungsi hitung KGB berikutnya
function tanggalKGBBerikut($tmt_gol){
    if(empty($tmt_gol)) return null;
    $tgl_awal = new DateTime($tmt_gol);
    $tgl_sekarang = new DateTime();
    while($tgl_awal <= $tgl_sekarang){
        $tgl_awal->modify('+2 years');
    }
    return $tgl_awal;
}
$tgl_kgb = tanggalKGBBerikut($pegawai['tmt_gol']);

// Jika form disubmit
if($_SERVER['REQUEST_METHOD']==='POST'){
    $status = 'selesai';
    $dokumen = null;

    // Upload file
    if(!empty($_FILES['dokumen']['name'])){
        $uploadDir = "uploads/kgb/";
        if(!is_dir($uploadDir)) mkdir($uploadDir,0777,true);
        $fileName = time()."_".basename($_FILES['dokumen']['name']);
        $targetPath = $uploadDir.$fileName;

        if(move_uploaded_file($_FILES['dokumen']['tmp_name'],$targetPath)){
            $dokumen = $fileName;
        }
    }

    // Simpan proses KGB
    $stmt = $conn->prepare("INSERT INTO kgb_proses (pegawai_id,tmt_kgb,dokumen,status) VALUES (?,?,?,?)");
    $tmt_kgb_sql = $tgl_kgb ? $tgl_kgb->format('Y-m-d') : date('Y-m-d');
    $stmt->bind_param("isss",$pegawai_id,$tmt_kgb_sql,$dokumen,$status);
    $stmt->execute();
    $stmt->close();

    // Update notifikasi → dibaca
    $conn->query("UPDATE notifikasi SET status='dibaca' WHERE pegawai_id=$pegawai_id");

    header("Location: proses_kgb.php?id=".$pegawai_id."&success=1");
    exit;
}

// Ambil riwayat proses KGB
$riwayat = [];
$stmt = $conn->prepare("SELECT id, tmt_kgb, dokumen, status, created_at FROM kgb_proses WHERE pegawai_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $pegawai_id);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()){
    $riwayat[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Proses KGB - <?= htmlspecialchars($pegawai['nama_lengkap']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background: #f5f7fa; }
.content { margin-left: 250px; padding: 30px; }
.card-custom {
    border: none;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
.info-title { font-size: 18px; font-weight: 600; }
.label { font-weight: 500; color: #555; }
.table-custom {
    border-radius: 10px;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 3px 10px rgba(0,0,0,0.05);
}
</style>
</head>
<body>
<?php include "sidebar_kepegawaian.php"; ?>

<div class="content">
    <h3 class="mb-4"><i class="bi bi-file-earmark-text"></i> Proses KGB Pegawai</h3>

    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success"><i class="bi bi-check-circle"></i> Proses KGB berhasil disimpan.</div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Informasi Pegawai -->
        <div class="col-md-5">
            <div class="card card-custom p-4">
                <h5 class="info-title mb-3"><i class="bi bi-person-badge"></i> Data Pegawai</h5>
                <p><span class="label">NIP:</span> <br><strong><?= htmlspecialchars($pegawai['nip']) ?></strong></p>
                <p><span class="label">Nama:</span> <br><strong><?= htmlspecialchars($pegawai['nama_lengkap']) ?></strong></p>
                <p><span class="label">TMT Golongan:</span> <br><strong><?= htmlspecialchars($pegawai['tmt_gol']) ?></strong></p>
                <p><span class="label">KGB Berikutnya:</span> <br>
                   <strong class="text-success"><?= $tgl_kgb ? $tgl_kgb->format('d-m-Y') : '-' ?></strong></p>
            </div>
        </div>

        <!-- Form Upload -->
        <div class="col-md-7">
            <div class="card card-custom p-4">
                <h5 class="info-title mb-3"><i class="bi bi-upload"></i> Upload Dokumen SK KGB</h5>
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Pilih File (PDF/JPG/PNG)</label>
                        <input type="file" name="dokumen" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">Maksimal 2MB</small>
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="reminder_kgb.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
                        <button type="submit" class="btn btn-success"><i class="bi bi-check-circle"></i> Simpan Proses KGB</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Riwayat Proses -->
    <div class="mt-5">
        <h5 class="info-title mb-3"><i class="bi bi-clock-history"></i> Riwayat Proses KGB</h5>
        <div class="table-responsive table-custom">
            <table class="table table-striped align-middle mb-0">
                <thead class="table-success">
                    <tr>
                        <th>#</th>
                        <th>TMT KGB</th>
                        <th>Status</th>
                        <th>Dokumen</th>
                        <th>Tanggal Proses</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($riwayat)): ?>
                        <tr><td colspan="5" class="text-center text-muted">Belum ada riwayat proses KGB</td></tr>
                    <?php else: ?>
                        <?php foreach($riwayat as $i => $row): ?>
                        <tr>
                            <td><?= $i+1 ?></td>
                            <td><?= date("d-m-Y", strtotime($row['tmt_kgb'])) ?></td>
                            <td>
                                <?php if($row['status']==='selesai'): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle"></i> Selesai</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> <?= ucfirst($row['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($row['dokumen']): ?>
                                    <a href="uploads/kgb/<?= htmlspecialchars($row['dokumen']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-file-earmark-pdf"></i> Lihat
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date("d-m-Y H:i", strtotime($row['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</body>
</html>
