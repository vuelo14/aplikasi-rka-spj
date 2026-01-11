<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/lib/Database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
Auth::start();

if (!Auth::check()) {
  header('Location: ' . APP_BASE_URL . 'login.php');
  exit;
}

$page = $_GET['page'] ?? 'dashboard';
$valid = [
  'dashboard',
  'rka',
  'subkegiatan',
  'komponen',
  'penyerapan',
  'spj',
  'realisasi',
  'laporan_sub',
  'spj_nota',
  'spj_nota_cetak',
  'spj_kwitansi_cetak'
];
if (!in_array($page, $valid)) $page = 'dashboard';

$user = Auth::user();
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <title>Aplikasi RKA & SPJ</title>

  <!-- Bootstrap 5 CSS (CDN resmi) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
    rel="stylesheet"
    integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
    crossorigin="anonymous">

  <!-- Bootstrap Icons (opsional) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css"
    rel="stylesheet">

  <!-- Style tambahan lokal -->
  <link rel="stylesheet" href="css/style.css">

  <style>
    /* ====== Layout Fixes (Bootstrap murni) ====== */
    body {
      background: #f8fafc;
    }

    /* Header fixed-top, beri ruang agar konten tidak ketutup */
    .content-area {
      padding-top: 4rem;
    }

    /* navbar ~56px */

    /* Sidebar (desktop): sticky di bawah header, tinggi penuh, scroll jika panjang */
    @media (min-width: 992px) {

      /* lg ke atas */
      .sidebar-wrapper {
        position: sticky;
        top: 4.5rem;
        /* tepat di bawah header fixed */
        height: calc(100vh - 5rem);
        overflow-y: auto;
        border-right: 1px solid #e5e7eb;
        background: #fff;
        border-radius: .5rem;
      }
    }

    .sidebar-nav .nav-link {
      color: #334155;
    }

    .sidebar-nav .nav-link.active {
      color: #0d6efd;
      font-weight: 600;
    }

    .app-footer {
      padding: .75rem 0 1.25rem;
      color: #6b7280;
    }
  </style>
</head>

<body>

  <!-- Header: fixed-top -->
  <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom fixed-top">
    <div class="container-fluid">

      <button class="btn btn-outline-secondary me-2 d-lg-none"
        type="button"
        data-bs-toggle="offcanvas"
        data-bs-target="#offcanvasSidebar"
        aria-controls="offcanvasSidebar">
        <i class="bi bi-list"></i>
      </button>

      <button class="btn btn-outline-secondary me-3 d-none d-lg-block" id="btnToggleSidebarDesktop">
        <i class="bi bi-list"></i>
      </button>

      <a class="navbar-brand fw-bold" href="index.php?page=dashboard">
        Aplikasi RKA &amp; SPJ
      </a>

      <div class="ms-auto d-flex align-items-center gap-3">
        <span class="text-secondary d-none d-sm-inline">
          Halo, <strong><?= htmlspecialchars($user['name']) ?></strong>
          (<?= $user['role'] ?> - TA <?= $user['tahun'] ?>)
        </span>
        <a href="logout.php" class="btn btn-outline-danger btn-sm">
          <i class="bi bi-box-arrow-right me-1"></i> Logout
        </a>
      </div>
    </div>
  </nav>

  <div class="content-area container-fluid">
    <div class="row g-3">
      <!-- Sidebar (desktop) -->
      <div class="col-lg-2 d-none d-lg-block" id="sidebarColumn">
        <div class="sidebar-wrapper p-2">
          <nav class="nav flex-column sidebar-nav">
            <a class="nav-link <?= $page === 'dashboard' ? 'active' : '' ?>" href="index.php?page=dashboard"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
            <a class="nav-link <?= $page === 'rka' ? 'active' : '' ?>" href="index.php?page=rka"><i class="bi bi-file-earmark-text me-2"></i>RKA</a>
            <a class="nav-link <?= $page === 'subkegiatan' ? 'active' : '' ?>" href="index.php?page=subkegiatan"><i class="bi bi-list-task me-2"></i>Sub Kegiatan</a>
            <a class="nav-link <?= $page === 'komponen' ? 'active' : '' ?>" href="index.php?page=komponen"><i class="bi bi-diagram-3 me-2"></i>Komponen RKA</a>
            <a class="nav-link <?= $page === 'penyerapan' ? 'active' : '' ?>" href="index.php?page=penyerapan"><i class="bi bi-graph-up-arrow me-2"></i>Rencana Penyerapan</a>
            <a class="nav-link <?= $page === 'spj' ? 'active' : '' ?>" href="index.php?page=spj"><i class="bi bi-receipt-cutoff me-2"></i>SPJ</a>
            <a class="nav-link <?= $page === 'realisasi' ? 'active' : '' ?>" href="index.php?page=realisasi"><i class="bi bi-cash-coin me-2"></i>Realisasi</a>
            <a class="nav-link <?= $page === 'laporan_sub' ? 'active' : '' ?>" href="index.php?page=laporan_sub"><i class="bi bi-file-bar-graph me-2"></i>Laporan Sub</a>
            <a class="nav-link <?= $page === 'spj_nota' ? 'active' : '' ?>" href="index.php?page=spj_nota"><i class="bi bi-file-earmark-ruled me-2"></i>Buat Nota</a>
          </nav>


        </div>
      </div>

      <!-- Sidebar (mobile) sebagai offcanvas -->
      <div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
        <div class="offcanvas-header">
          <h5 class="offcanvas-title" id="offcanvasSidebarLabel">Menu</h5>
          <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
        </div>
        <div class="offcanvas-body">
          <nav class="nav flex-column">
            <a class="nav-link <?= $page === 'dashboard' ? 'active' : '' ?>" href="index.php?page=dashboard"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
            <a class="nav-link <?= $page === 'rka' ? 'active' : '' ?>" href="index.php?page=rka"><i class="bi bi-file-earmark-text me-2"></i>RKA</a>
            <a class="nav-link <?= $page === 'subkegiatan' ? 'active' : '' ?>" href="index.php?page=subkegiatan"><i class="bi bi-list-task me-2"></i>Sub Kegiatan</a>
            <a class="nav-link <?= $page === 'komponen' ? 'active' : '' ?>" href="index.php?page=komponen"><i class="bi bi-diagram-3 me-2"></i>Komponen RKA</a>
            <a class="nav-link <?= $page === 'penyerapan' ? 'active' : '' ?>" href="index.php?page=penyerapan"><i class="bi bi-graph-up-arrow me-2"></i>Rencana Penyerapan</a>
            <a class="nav-link <?= $page === 'spj' ? 'active' : '' ?>" href="index.php?page=spj"><i class="bi bi-receipt-cutoff me-2"></i>SPJ</a>
            <a class="nav-link <?= $page === 'realisasi' ? 'active' : '' ?>" href="index.php?page=realisasi"><i class="bi bi-cash-coin me-2"></i>Realisasi</a>
            <a class="nav-link <?= $page === 'laporan_sub' ? 'active' : '' ?>" href="index.php?page=laporan_sub"><i class="bi bi-file-bar-graph me-2"></i>Laporan Sub</a>
            <a class="nav-link <?= $page === 'spj_nota' ? 'active' : '' ?>" href="index.php?page=spj_nota"><i class="bi bi-file-earmark-ruled me-2"></i>Buat Nota</a>
          </nav>
        </div>
      </div>

      <!-- Konten -->
      <div class="col-12 col-lg-10" id="contentColumn">
        <?php include __DIR__ . '/pages/' . $page . '.php'; ?>

        <!-- Footer -->
        <div class="app-footer">
          © Disnaker — Sistem RKA & SPJ
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap 5 JS Bundle (termasuk Popper) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
    crossorigin="anonymous"></script>


  <script>
    // Logic Toggle Sidebar Desktop
    (function() {
      const btn = document.getElementById('btnToggleSidebarDesktop');
      const sidebarCol = document.getElementById('sidebarColumn'); // Gunakan ID
      const contentCol = document.getElementById('contentColumn');

      if (btn && sidebarCol && contentCol) {
        btn.addEventListener('click', function() {
          // Kita toggle class 'd-lg-block'.
          // Jika class ini HILANG, maka class 'd-none' (yang sudah ada) akan aktif -> Sidebar sembunyi.
          // Jika class ini ADA, maka sidebar tampil -> Sidebar muncul.
          const isVisible = sidebarCol.classList.toggle('d-lg-block');

          if (!isVisible) {
            // Sidebar SEMBUNYI
            contentCol.classList.remove('col-lg-10');
            contentCol.classList.add('col-lg-12');
            // Opsional: Ubah icon jadi full
            btn.innerHTML = '<i class="bi bi-layout-sidebar-inset"></i>';
          } else {
            // Sidebar MUNCUL
            contentCol.classList.remove('col-lg-12');
            contentCol.classList.add('col-lg-10');
            // Opsional: Kembalikan icon list
            btn.innerHTML = '<i class="bi bi-list"></i>';
          }
        });
      }
    })();
  </script>
</body>

</html>