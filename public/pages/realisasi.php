<?php
Auth::requireRole(['ADMIN', 'PPTK', 'BENDAHARA']);
$pdo = Database::getConnection();
$tahun_aktif = Auth::user()['tahun'];

// Ambil komponen & SPJ untuk dropdown
$komp = $pdo->prepare('SELECT k.id, k.kode_komponen, k.uraian, r.tahun FROM komponen k
INNER JOIN subkegiatan s ON k.subkegiatan_id = s.id
INNER JOIN rka r ON s.rka_id = r.id
  WHERE r.tahun = ?
ORDER BY k.id DESC');
$komp->execute([$tahun_aktif]);
$komp = $komp->fetchAll();
$spj  = $pdo->query('SELECT id, nomor_spj, jenis FROM spj ORDER BY id DESC')->fetchAll();

$msg = '';
$err = '';

// === INSERT realisasi ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $komp_id  = (int)($_POST['komponen_id'] ?? 0);
  $spj_id   = ($_POST['spj_id'] ?? '') !== '' ? (int)$_POST['spj_id'] : null;
  $tanggal  = trim($_POST['tanggal'] ?? '');
  $jumlah   = (float)($_POST['jumlah'] ?? 0);
  $ket      = trim($_POST['keterangan'] ?? '');

  if ($komp_id && $tanggal && $jumlah > 0) {
    $stmt = $pdo->prepare("INSERT INTO realisasi (komponen_id, spj_id, tanggal, jumlah, keterangan)
                           VALUES (?,?,?,?,?)");
    $stmt->execute([$komp_id, $spj_id, $tanggal, $jumlah, $ket]);
    $msg = "Realisasi dicatat.";
  } else {
    $err = "Lengkapi data realisasi (Komponen, Tanggal, Jumlah).";
  }
}

// === DELETE ===
if (isset($_GET['del'])) {
  $id = (int)$_GET['del'];
  $pdo->prepare("DELETE FROM realisasi WHERE id=?")->execute([$id]);
  $msg = "Realisasi dihapus.";
}

// === TABEL REALISASI ===
$rows = $pdo->query("
  SELECT re.*, 
         k.kode_komponen, 
         k.uraian AS nama_komp,
         s.nomor_spj
  FROM realisasi re
  JOIN komponen k ON re.komponen_id=k.id
  LEFT JOIN spj s ON re.spj_id=s.id
  ORDER BY re.id DESC
")->fetchAll();
?>

<div class="container-fluid">

  <!-- Alert -->
  <?php if ($msg): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?= htmlspecialchars($msg) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if ($err): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?= htmlspecialchars($err) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>


  <!-- FORM INPUT REALISASI -->
  <div class="card mb-3">
    <div class="card-header"><strong>Catat Realisasi</strong></div>
    <div class="card-body">
      <form method="post" class="row g-3">

        <div class="col-12 col-md-6">
          <label class="form-label">Komponen</label>
          <select name="komponen_id" class="form-select" required>
            <option value="">-- Pilih Komponen --</option>
            <?php foreach ($komp as $k): ?>
              <option value="<?= $k['id'] ?>">
                <?= htmlspecialchars($k['kode_komponen']) ?> — <?= htmlspecialchars($k['uraian']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12 col-md-6">
          <label class="form-label">SPJ (opsional)</label>
          <select name="spj_id" class="form-select">
            <option value="">-- Tanpa SPJ --</option>
            <?php foreach ($spj as $s): ?>
              <option value="<?= $s['id'] ?>">
                <?= htmlspecialchars($s['nomor_spj']) ?> (<?= $s['jenis'] ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12 col-md-4">
          <label class="form-label">Tanggal</label>
          <input type="date" name="tanggal" class="form-control" required>
        </div>

        <div class="col-12 col-md-4">
          <label class="form-label">Jumlah</label>
          <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input type="number" step="0.01" class="form-control" name="jumlah" required>
          </div>
        </div>

        <div class="col-12 col-md-4">
          <label class="form-label">Keterangan</label>
          <input type="text" name="keterangan" class="form-control">
        </div>

        <div class="col-12">
          <button type="submit" class="btn btn-primary">Simpan Realisasi</button>
        </div>

      </form>
    </div>
  </div>


  <!-- TABEL RESPONSIVE -->
  <div class="card">
    <div class="card-header"><strong>Daftar Realisasi</strong></div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Komponen</th>
              <th>Tanggal</th>
              <th>Jumlah</th>
              <th>SPJ</th>
              <th>Keterangan</th>
              <th style="min-width:130px;">Aksi</th>
            </tr>
          </thead>
          <tbody>

            <?php foreach ($rows as $r): ?>
              <tr>
                <td>
                  <div class="fw-bold"><?= htmlspecialchars($r['kode_komponen']) ?></div>
                  <div class="small text-muted"><?= htmlspecialchars($r['nama_komp']) ?></div>
                </td>

                <td><?= htmlspecialchars($r['tanggal']) ?></td>

                <td>Rp <?= number_format($r['jumlah'], 2, ',', '.') ?></td>

                <td><?= htmlspecialchars($r['nomor_spj'] ?? '-') ?></td>

                <td><?= htmlspecialchars($r['keterangan'] ?? '-') ?></td>

                <td>
                  <a href="index.php?page=realisasi&del=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger"
                    onclick="return confirm('Hapus realisasi ini?')">
                    <i class="bi bi-trash"></i> Hapus
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>

            <?php if (empty($rows)): ?>
              <tr>
                <td colspan="6" class="text-center text-muted">Belum ada realisasi.</td>
              </tr>
            <?php endif; ?>

          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>