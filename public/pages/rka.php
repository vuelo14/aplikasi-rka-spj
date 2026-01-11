<?php
Auth::requireRole(['ADMIN', 'PPTK']);
$pdo = Database::getConnection();
$tahun_aktif = Auth::user()['tahun'];
$msg = '';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $tahun = (int)($_POST['tahun'] ?? 0);
  $nomor = trim($_POST['nomor_rka'] ?? '');
  $nama = trim($_POST['nama_kegiatan'] ?? '');
  $pagu = (float)($_POST['pagu_total'] ?? 0);
  if ($tahun && $nomor && $nama) {
    $st = $pdo->prepare('INSERT INTO rka (tahun,nomor_rka,nama_kegiatan,pagu_total,created_by) VALUES (?,?,?,?,?)');
    $st->execute([$tahun, $nomor, $nama, $pagu, Auth::user()['id']]);
    $msg = 'RKA berhasil ditambahkan';
  } else {
    $err = 'Isi semua data RKA';
  }
}

// === UPDATE (EDIT) RKA ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
  $id    = (int)($_POST['id'] ?? 0);
  $tahun = (int)($_POST['tahun'] ?? 0);
  $nomor = trim($_POST['nomor_rka'] ?? '');
  $nama  = trim($_POST['nama_kegiatan'] ?? '');
  $pagu  = (float)($_POST['pagu_total'] ?? 0);

  if ($id && $tahun && $nomor && $nama) {
    $pdo->prepare('UPDATE rka SET tahun=?, nomor_rka=?, nama_kegiatan=?, pagu_total=? WHERE id=?')
      ->execute([$tahun, $nomor, $nama, $pagu, $id]);
    $msg = 'RKA diperbarui.';
  } else {
    $err = 'Lengkapi data saat edit.';
  }
}

if (isset($_GET['del'])) {
  $id = (int)$_GET['del'];
  $pdo->prepare('DELETE FROM rka WHERE id=?')->execute([$id]);
  $msg = 'RKA dihapus';
}

// Ambil data RKA untuk form edit
$editRow = null;
if (isset($_GET['edit'])) {
  $eid = (int)$_GET['edit'];
  $stmt = $pdo->prepare('SELECT * FROM rka WHERE id=?');
  $stmt->execute([$eid]);
  $editRow = $stmt->fetch();
}

$rka = $pdo->prepare('SELECT r.*, u.name as creator FROM rka r LEFT JOIN users u ON r.created_by=u.id WHERE r.tahun = ? ORDER BY id DESC');
$rka->execute([$tahun_aktif]);
$rka = $rka->fetchAll();
?>
<div class="card">
  <div class="card-header">
    Data RKA
  </div>
  <div class="card-body">
    <?php if ($msg): ?><div class="alert success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <form method="post" class="row g-3">

      <?php if ($editRow): ?>
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?= $editRow['id'] ?>">
      <?php else: ?>
        <input type="hidden" name="action" value="create">
      <?php endif; ?>

      <div class="col-12 col-md-3">
        <label class="form-label">Tahun</label>
        <input type="number" name="tahun" class="form-control" required
          value="<?= $editRow['tahun'] ?? '' ?>">
      </div>

      <div class="col-12 col-md-4">
        <label class="form-label">Nomor RKA</label>
        <input type="text" name="nomor_rka" class="form-control" required
          value="<?= $editRow['nomor_rka'] ?? '' ?>">
      </div>

      <div class="col-12">
        <label class="form-label">Nama Kegiatan</label>
        <input type="text" name="nama_kegiatan" class="form-control" required
          value="<?= $editRow['nama_kegiatan'] ?? '' ?>">
      </div>

      <div class="col-12 col-md-4">
        <label class="form-label">Pagu Total</label>
        <div class="input-group">
          <span class="input-group-text">Rp</span>
          <input type="number" step="0.01" name="pagu_total" class="form-control"
            value="<?= $editRow['pagu_total'] ?? '' ?>">
        </div>
      </div>

      <div class="col-12 d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <?= $editRow ? 'Simpan Perubahan' : 'Tambah RKA' ?>
        </button>

        <?php if ($editRow): ?>
          <a href="index.php?page=rka" class="btn btn-secondary">Batal</a>
        <?php endif; ?>
      </div>
    </form>


    <div class="table-responsive">
      <table class="table table-striped table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>Tahun</th>
            <th>Nomor</th>
            <th>Nama Kegiatan</th>
            <th>Pagu</th>
            <th>Dibuat Oleh</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rka as $r): ?>
            <tr>
              <td><?= $r['tahun'] ?></td>
              <td><?= $r['nomor_rka'] ?></td>
              <td><?= $r['nama_kegiatan'] ?></td>
              <td>Rp <?= number_format($r['pagu_total'], 2, ',', '.') ?></td>
              <td><?= $r['creator'] ?? '-' ?></td>
              <td class="d-flex flex-wrap gap-2">
                <a href="index.php?page=rka&edit=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>

                <a href="index.php?page=rka&del=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger"
                  onclick="return confirm('Hapus RKA ini?')">Hapus</a>
              </td>
            </tr>
          <?php endforeach; ?>

          <?php if (empty($rka)): ?>
            <tr>
              <td colspan="6" class="text-center text-muted">Belum ada data RKA</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>