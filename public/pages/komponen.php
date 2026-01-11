<?php
Auth::requireRole(['ADMIN', 'PPTK']);
$pdo = Database::getConnection();

$msg = '';
$err = '';

// Ambil data Sub Kegiatan untuk dropdown
// $subs = $pdo->query('SELECT id, kode_sub, nama_sub FROM subkegiatan ORDER BY id DESC')->fetchAll();

$subs = $pdo->query("
    SELECT s.id, s.kode_sub, s.nama_sub,
           r.tahun
    FROM subkegiatan s
    LEFT JOIN rka r ON r.id = s.rka_id
    ORDER BY s.id DESC
")->fetchAll();


/* ======================================================
   CREATE Komponen
   ====================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {

  $sid   = (int)($_POST['subkegiatan_id'] ?? 0);
  $kode  = trim($_POST['kode_komponen'] ?? '');
  $akun  = trim($_POST['akun_belanja'] ?? '');
  $uraian = trim($_POST['uraian'] ?? '');
  $vol   = (float)($_POST['volume'] ?? 1);
  $sat   = trim($_POST['satuan'] ?? '');
  $harga = (float)($_POST['harga_satuan'] ?? 0);

  if ($sid && $kode && $uraian && $sat) {
    $total = $vol * $harga;
    $pdo->prepare('INSERT INTO komponen 
      (subkegiatan_id,kode_komponen,akun_belanja,uraian,volume,satuan,harga_satuan,total)
      VALUES (?,?,?,?,?,?,?,?)')
      ->execute([$sid, $kode, $akun, $uraian, $vol, $sat, $harga, $total]);

    $msg = 'Komponen berhasil ditambahkan.';
  } else {
    $err = 'Lengkapi data komponen.';
  }
}

/* ======================================================
   UPDATE (EDIT) Komponen
   ====================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {

  $id    = (int)($_POST['id'] ?? 0);
  $sid   = (int)($_POST['subkegiatan_id'] ?? 0);
  $kode  = trim($_POST['kode_komponen'] ?? '');
  $akun  = trim($_POST['akun_belanja'] ?? '');
  $uraian = trim($_POST['uraian'] ?? '');
  $vol   = (float)($_POST['volume'] ?? 1);
  $sat   = trim($_POST['satuan'] ?? '');
  $harga = (float)($_POST['harga_satuan'] ?? 0);
  $total = $vol * $harga;

  if ($id && $sid && $kode && $uraian && $sat) {
    $pdo->prepare('UPDATE komponen SET 
        subkegiatan_id=?, kode_komponen=?, akun_belanja=?, uraian=?, 
        volume=?, satuan=?, harga_satuan=?, total=? 
      WHERE id=?')
      ->execute([$sid, $kode, $akun, $uraian, $vol, $sat, $harga, $total, $id]);

    $msg = 'Komponen berhasil diperbarui.';
  } else {
    $err = 'Lengkapi data komponen.';
  }
}

/* ======================================================
   DELETE Komponen
   ====================================================== */
if (isset($_GET['del'])) {
  $did = (int)$_GET['del'];
  $pdo->prepare('DELETE FROM komponen WHERE id=?')->execute([$did]);
  $msg = 'Komponen dihapus.';
}

/* ======================================================
   Ambil data untuk form edit
   ====================================================== */
$editRow = null;
if (isset($_GET['edit'])) {
  $eid = (int)$_GET['edit'];
  $stmt = $pdo->prepare('SELECT * FROM komponen WHERE id=?');
  $stmt->execute([$eid]);
  $editRow = $stmt->fetch();
}

/* ======================================================
   Ambil seluruh komponen untuk tabel
   ====================================================== */

$rows = $pdo->query('
  SELECT 
      k.*, 
      s.kode_sub, 
      s.nama_sub,
      r.tahun
  FROM komponen k
  JOIN subkegiatan s ON k.subkegiatan_id = s.id
  LEFT JOIN rka r ON r.id = s.rka_id
  ORDER BY k.id ASC, r.tahun DESC
')->fetchAll();


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

  <!-- FORM CREATE / EDIT -->
  <div class="card mb-3">
    <div class="card-header">
      <strong><?= $editRow ? 'Edit Komponen RKA' : 'Tambah Komponen RKA' ?></strong>
    </div>

    <div class="card-body">
      <form method="post" class="row g-3">

        <?php if ($editRow): ?>
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" value="<?= $editRow['id'] ?>">
        <?php else: ?>
          <input type="hidden" name="action" value="create">
        <?php endif; ?>

        <div class="col-12 col-md-6">
          <label class="form-label">Sub Kegiatan</label>
          <select name="subkegiatan_id" class="form-select" required>
            <option value="">-- Pilih Sub Kegiatan --</option>
            <?php foreach ($subs as $s): ?>
              <option value="<?= $s['id'] ?>"
                <?= $editRow && $editRow['subkegiatan_id'] == $s['id'] ? 'selected' : '' ?>>
                [<?= htmlspecialchars($s['tahun']) ?>] <?= $s['kode_sub'] . " - " . $s['nama_sub'] ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12 col-md-3">
          <label class="form-label">Kode Komponen</label>
          <input type="text" name="kode_komponen" class="form-control" required
            value="<?= $editRow ? $editRow['kode_komponen'] : '' ?>">
        </div>

        <div class="col-12 col-md-3">
          <label class="form-label">Akun Belanja</label>
          <input type="text" name="akun_belanja" class="form-control"
            value="<?= $editRow ? $editRow['akun_belanja'] : '' ?>">
        </div>

        <div class="col-12">
          <label class="form-label">Uraian</label>
          <input type="text" name="uraian" class="form-control" required
            value="<?= $editRow ? $editRow['uraian'] : '' ?>">
        </div>

        <div class="col-6 col-md-2">
          <label class="form-label">Volume</label>
          <input type="number" step="0.01" name="volume" class="form-control"
            value="<?= $editRow ? $editRow['volume'] : '1' ?>">
        </div>

        <div class="col-6 col-md-2">
          <label class="form-label">Satuan</label>
          <input type="text" name="satuan" class="form-control" required
            value="<?= $editRow ? $editRow['satuan'] : '' ?>">
        </div>

        <div class="col-12 col-md-3">
          <label class="form-label">Harga Satuan</label>
          <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input type="number" step="0.01" name="harga_satuan"
              class="form-control"
              value="<?= $editRow ? $editRow['harga_satuan'] : '' ?>">
          </div>
        </div>

        <div class="col-12 d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <?= $editRow ? 'Simpan Perubahan' : 'Tambah Komponen' ?>
          </button>

          <?php if ($editRow): ?>
            <a href="index.php?page=komponen" class="btn btn-secondary">Batal</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <!-- TABEL RESPONSIF -->
  <div class="card">
    <div class="card-header"><strong>Data Komponen</strong></div>
    <div class="card-body">

      <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Sub Kegiatan</th>
              <th>Kode</th>
              <th>Akun</th>
              <th>Uraian</th>
              <th>Vol</th>
              <th>Satuan</th>
              <th>Harga</th>
              <th>Total</th>
              <th>Aksi</th>
            </tr>
          </thead>

          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td>
                  <div class="badge bg-primary"><?= $r['tahun'] ?></div>
                  <div class="small text-muted"><?= $r['kode_sub'] ?></div>
                  <div><?= $r['nama_sub'] ?></div>
                </td>
                <td><?= htmlspecialchars($r['kode_komponen']) ?></td>
                <td><?= htmlspecialchars($r['akun_belanja']) ?></td>
                <td><?= htmlspecialchars($r['uraian']) ?></td>
                <td><?= htmlspecialchars($r['volume']) ?></td>
                <td><?= htmlspecialchars($r['satuan']) ?></td>
                <td>Rp <?= number_format($r['harga_satuan'], 2, ',', '.') ?></td>
                <td>Rp <?= number_format($r['total'], 2, ',', '.') ?></td>
                <td>
                  <a href="index.php?page=komponen&edit=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                  <a href="index.php?page=komponen&del=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger"
                    onclick="return confirm('Hapus komponen ini?')">Hapus</a>
                </td>
              </tr>
            <?php endforeach; ?>

            <?php if (empty($rows)): ?>
              <tr>
                <td colspan="9" class="text-center text-muted">Belum ada data komponen.</td>
              </tr>
            <?php endif; ?>
          </tbody>

        </table>
      </div>
    </div>
  </div>

</div>