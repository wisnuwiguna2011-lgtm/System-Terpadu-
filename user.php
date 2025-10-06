<?php 
include 'config.php';
session_start();

// 🚫 Hanya Admin yang boleh akses
if($_SESSION['role']!=='admin'){ 
  echo "<div class='alert alert-danger'>Akses ditolak!</div>"; 
  exit; 
}

// Tambah User
if(isset($_POST['add'])){
  $uname = trim($_POST['username']);
  $fname = trim($_POST['full_name']);
  $pass  = password_hash($_POST['password'], PASSWORD_BCRYPT);
  $role  = $_POST['role'];
  $stmt = $mysqli->prepare("INSERT INTO users(username,password,full_name,role) VALUES(?,?,?,?)");
  $stmt->bind_param("ssss",$uname,$pass,$fname,$role);
  $stmt->execute();
  echo "<div class='alert alert-success'>User berhasil ditambahkan</div>";
}

// Update User
if(isset($_POST['update'])){
  $id    = intval($_POST['id']);
  $fname = trim($_POST['full_name']);
  $role  = $_POST['role'];

  if(!empty($_POST['password'])){
    $pass = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $stmt = $mysqli->prepare("UPDATE users SET full_name=?, role=?, password=? WHERE id=?");
    $stmt->bind_param("sssi",$fname,$role,$pass,$id);
  } else {
    $stmt = $mysqli->prepare("UPDATE users SET full_name=?, role=? WHERE id=?");
    $stmt->bind_param("ssi",$fname,$role,$id);
  }
  $stmt->execute();
  echo "<div class='alert alert-info'>User berhasil diupdate</div>";
}

// Hapus User
if(isset($_GET['del'])){
  $id = intval($_GET['del']);
  if($id!=1){ // Jangan hapus admin utama
    $mysqli->query("DELETE FROM users WHERE id=$id");
    echo "<div class='alert alert-warning'>User berhasil dihapus</div>";
  }
}

// Ambil daftar user
$res = $mysqli->query("SELECT id,username,full_name,role,created_at FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Kelola Pengguna</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <h3 class="mb-4"><i class="fa-solid fa-users me-2"></i>Kelola Pengguna</h3>

  <!-- Form Tambah User -->
  <form method="post" class="row g-2 mb-4">
    <div class="col-md-3">
      <input class="form-control" name="username" placeholder="Username" required>
    </div>
    <div class="col-md-3">
      <input class="form-control" name="full_name" placeholder="Nama Lengkap" required>
    </div>
    <div class="col-md-2">
      <input class="form-control" type="password" name="password" placeholder="Password" required>
    </div>
    <div class="col-md-2">
      <select class="form-select" name="role">
        <option value="user">User</option>
        <option value="admin">Admin</option>
      </select>
    </div>
    <div class="col-md-2">
      <button class="btn btn-dark w-100" name="add">
        <i class="fa-solid fa-plus me-1"></i>Tambah
      </button>
    </div>
  </form>

  <!-- Tabel Daftar User -->
  <table class="table table-striped table-hover">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Username</th>
        <th>Nama Lengkap</th>
        <th>Role</th>
        <th>Dibuat</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
    <?php while($u=$res->fetch_assoc()): ?>
      <tr>
        <td><?= $u['id'] ?></td>
        <td><?= htmlspecialchars($u['username']) ?></td>
        <td><?= htmlspecialchars($u['full_name']) ?></td>
        <td><?= htmlspecialchars($u['role']) ?></td>
        <td><?= $u['created_at'] ?></td>
        <td>
          <!-- Tombol Edit (Modal) -->
          <button class="btn btn-sm btn-outline-primary" 
                  data-bs-toggle="modal" 
                  data-bs-target="#editUser<?= $u['id'] ?>">
            <i class="fa-solid fa-edit"></i>
          </button>
          <?php if($u['id']!=1): ?>
            <a href="users.php?del=<?= $u['id'] ?>" 
               class="btn btn-sm btn-outline-danger" 
               onclick="return confirm('Hapus pengguna ini?')">
              <i class="fa-solid fa-trash"></i>
            </a>
          <?php endif; ?>
        </td>
      </tr>

      <!-- Modal Edit User -->
      <div class="modal fade" id="editUser<?= $u['id'] ?>" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <form method="post">
              <div class="modal-header">
                <h5 class="modal-title">Edit User: <?= htmlspecialchars($u['username']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <div class="mb-3">
                  <label>Nama Lengkap</label>
                  <input class="form-control" name="full_name" value="<?= htmlspecialchars($u['full_name']) ?>" required>
                </div>
                <div class="mb-3">
                  <label>Password (kosongkan jika tidak diganti)</label>
                  <input type="password" class="form-control" name="password">
                </div>
                <div class="mb-3">
                  <label>Role</label>
                  <select class="form-select" name="role">
                    <option value="user" <?= $u['role']=='user'?'selected':'' ?>>User</option>
                    <option value="admin" <?= $u['role']=='admin'?'selected':'' ?>>Admin</option>
                  </select>
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" name="update" class="btn btn-primary">
                  <i class="fa fa-save"></i> Simpan
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
