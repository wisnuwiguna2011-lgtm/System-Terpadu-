<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sistem Arsip - Login</title>
  <!-- Font Awesome CDN untuk ikon -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    * {
      margin:0; padding:0; box-sizing:border-box;
      font-family:"Poppins",sans-serif;
    }
    body {
      display:flex; justify-content:center; align-items:center;
      min-height:100vh; background:#f0f2f5;
    }
    .container {
      width:900px; max-width:100%; height:520px;
      background:transparent; border-radius:20px; overflow:hidden;
      display:flex; gap:0;
    }

    /* Kotak login */
    .login-box {
      flex:1; display:flex; justify-content:center; align-items:center;
      background:transparent;
    }
    .login-container {
      width:85%; max-width:380px;
      background:#fff; border-radius:12px;
      padding:40px 35px;
      box-shadow:0 10px 35px rgba(0,0,0,0.15);
    }
    .login-container h2 {
      margin-bottom:25px; font-size:20px;
      font-weight:600; text-align:center;
    }
    .input-box {
      display:flex; align-items:center;
      margin-bottom:15px; border:1px solid #ddd;
      border-radius:8px; padding:10px 12px;
      background:#f9f9f9;
    }
    .input-box i {
      color:#4a90e2; font-size:16px; margin-right:10px;
    }
    .input-box input {
      border:none; outline:none; flex:1;
      background:transparent; font-size:14px;
    }

    /* Custom checkbox */
    .custom-checkbox {
      display:flex; align-items:center; gap:8px;
      cursor:pointer; user-select:none; font-size:14px;
    }
    .custom-checkbox input {
      display:none;
    }
    .checkmark {
      width:18px; height:18px;
      border:2px solid #4a90e2;
      border-radius:4px;
      display:flex; align-items:center; justify-content:center;
      transition:0.3s;
      position:relative;
      overflow:hidden;
    }
    .checkmark i {
      font-size:12px; color:#fff;
      opacity:0; transform:scale(0.5);
      transition:all 0.3s ease;
    }
    .custom-checkbox input:checked + .checkmark {
      background:#4a90e2;
    }
    .custom-checkbox input:checked + .checkmark i {
      opacity:1; transform:scale(1);
    }

    .login-options {
      display:flex; justify-content:space-between; align-items:center;
      margin:10px 0 20px;
    }

    /* Tombol login dengan ripple */
    .btn-login {
      position:relative;
      overflow:hidden;
      background:#4a90e2; color:#fff; border:none;
      padding:12px; width:100%; border-radius:8px;
      font-size:16px; cursor:pointer; transition:.3s;
    }
    .btn-login:hover { background:#357ABD; }
    .btn-login:active { transform:scale(0.97); }

    .btn-login .ripple {
      position:absolute;
      border-radius:50%;
      transform:scale(0);
      animation:ripple 0.6s linear;
      background:rgba(255,255,255,0.7);
      pointer-events:none;
    }
    @keyframes ripple {
      to {
        transform:scale(4);
        opacity:0;
      }
    }

    /* Panel kanan (logo + welcome) */
    .right-container {
      flex:1; background:linear-gradient(135deg,#0d47a1,#1976d2);
      color:#fff; display:flex; flex-direction:column;
      justify-content:center; align-items:center; text-align:center;
      padding:40px;
    }
    .right-container img {
      width:120px; height:auto; margin-bottom:20px;
    }
    .right-container h2 {
      margin-bottom:5px; font-size:26px;
    }
    .right-container p {
      margin-top:10px; font-size:14px;
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Kotak login -->
    <div class="login-box">
      <div class="login-container">
        <h2>Silahkan Login</h2>
        <form action="login.php" method="POST">
          <div class="input-box">
            <i class="fa-solid fa-user"></i>
            <input type="text" name="username" placeholder="Masukkan User" required>
          </div>
          <div class="input-box">
            <i class="fa-solid fa-lock"></i>
            <input type="password" name="password" placeholder="Masukkan Password" required>
          </div>
          <div class="login-options">
            <label class="custom-checkbox">
              <input type="checkbox" name="remember">
              <span class="checkmark"><i class="fa-solid fa-check"></i></span>
              Ingatkan Saya
            </label>
          </div>
          <button type="submit" class="btn-login">LOG IN</button>
        </form>
      </div>
    </div>

    <!-- Panel kanan (Logo + Welcome text) -->
    <div class="right-container">
      <img src="logo_1.png" alt="Logo Sistem Arsip">
      <h2>SISTA</h2>
      <h2>Setditjen Saintek</h2>
      <p>(Sistem Informasi Terintegrasi Arsip)</p>
    </div>
  </div>

  <script>
    // Ripple effect untuk tombol
    const buttons = document.querySelectorAll('.btn-login');
    buttons.forEach(btn => {
      btn.addEventListener('click', function(e) {
        const circle = document.createElement('span');
        circle.classList.add('ripple');
        const rect = this.getBoundingClientRect();
        circle.style.width = circle.style.height = Math.max(rect.width, rect.height) + 'px';
        circle.style.left = e.clientX - rect.left - (circle.offsetWidth / 2) + 'px';
        circle.style.top = e.clientY - rect.top - (circle.offsetHeight / 2) + 'px';

        this.appendChild(circle);

        setTimeout(() => circle.remove(), 600);
      });
    });
  </script>
</body>
</html>
