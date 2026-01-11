<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/lib/Database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
Auth::start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  if (Auth::login($email, $password)) {
    header('Location: index.php');
    exit;
  } else {
    $error = 'Email atau password salah.';
  }
}
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Login - Aplikasi RKA & SPJ</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body {
      background: #f8fafc;
    }

    .split-container {
      display: flex;
      min-height: 100vh;
    }

    .left-panel {
      flex: 1;
      background: linear-gradient(135deg, #0052D4, #4364F7, #6FB1FC);
      color: white;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 40px;
    }

    .left-panel h1 {
      font-size: 36px;
      margin-bottom: 15px;
    }

    .left-panel p {
      font-size: 18px;
      opacity: 0.9;
    }

    .left-panel svg {
      width: 180px;
      height: 180px;
    }

    .right-panel {
      flex: 1;
      background: #f7f7f7;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 40px;
    }

    .login-box {
      width: 100%;
      max-width: 380px;
      padding: 35px;
    }

    .login-box h2 {
      text-align: center;
      margin-bottom: 25px;
    }
  </style>
</head>

<body>

  <div class="container-fluid split-container">
    <div class="row g-0 w-100">

      <!-- LEFT PANEL (SVG ICON + BRAND) -->
      <div class="col-md-6 d-none d-md-flex left-panel">
        <div class="text-center">

          <!-- SVG Icon -->
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="white" stroke-width="1.5" viewBox="0 0 24 24">
            <path d="M3 3h18v18H3z" />
            <path d="M7 7h10v4H7zM7 13h6v4H7z" />
          </svg>

          <h2 class="mt-3 fw-bold">Aplikasi RKA & SPJ</h2>
          </p>
        </div>
      </div>

      <!-- RIGHT PANEL (FORM LOGIN) -->
      <div class="col-md-6 col-12 right-panel">
        <div class="login-box">

          <h3 class="mb-4 fw-bold text-center">Masuk</h3>

          <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

          <form method="post" class="vstack gap-3">

            <div>
              <label class="form-label">Email</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control" required>
              </div>
            </div>

            <div>
              <label class="form-label">Password</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" class="form-control" required>
              </div>
            </div>

            <button class="btn btn-primary w-100 mt-2">Masuk</button>

          </form>

          <p class="text-center mt-4 text-muted">
            <a href="setup_admin.php">Belum ada Admin? Buat Admin</a>
          </p>

        </div>
      </div>

    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>