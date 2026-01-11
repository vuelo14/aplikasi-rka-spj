<?php
Auth::requireRole(['ADMIN', 'PPTK']);
$pdo = Database::getConnection();

$msg = '';
$err = '';

// Ambil daftar RKA untuk dropdown
$rka = $pdo->query('SELECT id, tahun, nomor_rka, nama_kegiatan FROM rka ORDER BY tahun DESC')->fetchAll();

// === CREATE Sub Kegiatan ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
  $rka_id = (int)($_POST['rka_id'] ?? 0);
  $kode   = trim($_POST['kode_sub'] ?? '');
  $nama   = trim($_POST['nama_sub'] ?? '');
  $pagu   = (float)($_POST['pagu_sub'] ?? 0);
  if ($rka_id && $kode && $nama) {
    $pdo->prepare('INSERT INTO subkegiatan (rka_id,kode_sub,nama_sub,pagu_sub) VALUES (?,?,?,?)')
      ->execute([$rka_id, $kode, $nama, $pagu]);
    $msg = 'Sub Kegiatan ditambahkan';
  } else {
    $err = 'Lengkapi data Sub Kegiatan (RKA, Kode, Nama).';
  }
}

// === UPDATE (EDIT) Sub Kegiatan ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
  $id     = (int)($_POST['id'] ?? 0);
  $rka_id = (int)($_POST['rka_id'] ?? 0);
  $kode   = trim($_POST['kode_sub'] ?? '');
  $nama   = trim($_POST['nama_sub'] ?? '');
  $pagu   = (float)($_POST['pagu_sub'] ?? 0);
  if ($id && $rka_id && $kode && $nama) {
    $pdo->prepare('UPDATE subkegiatan SET rka_id=?, kode_sub=?, nama_sub=?, pagu_sub=? WHERE id=?')
      ->execute([$rka_id, $kode, $nama, $pagu, $id]);
    $msg = 'Sub Kegiatan diperbarui';
  } else {
    $err = 'Lengkapi data saat edit (RKA, Kode, Nama).';
  }
}

// === DELETE Sub Kegiatan ===
if (isset($_GET['del'])) {
  $del_id = (int)$_GET['del'];
  $pdo->prepare('DELETE FROM subkegiatan WHERE id=?')->execute([$del_id]);
  $msg = 'Sub Kegiatan dihapus';
}

// === Ambil data untuk form edit jika ada ?edit=ID ===
$editRow = null;
if (isset($_GET['edit'])) {
  $eid = (int)$_GET['edit'];
  $stmt = $pdo->prepare('SELECT * FROM subkegiatan WHERE id=?');
  $stmt->execute([$eid]);
  $editRow = $stmt->fetch();
}

// Data Sub Kegiatan untuk tabel
$subs = $pdo->query('SELECT s.*, r.tahun, r.nomor_rka, r.nama_kegiatan 
  FROM subkegiatan s 
  JOIN rka r ON s.rka_id=r.id 
  ORDER BY s.id DESC')->fetchAll();
?>

<div class="container-fluid">
  <!-- Alert -->
  <?php if ($msg): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?= htmlspecialchars($msg) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  <?php endif; ?>
  <?php if ($err): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?= htmlspecialchars($err) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  <?php endif; ?>

  <!-- Form Tambah / Edit (Responsive Bootstrap) -->
  <div class="card mb-3">
    <div class="card-header">
      <strong><?= $editRow ? 'Edit Sub Kegiatan' : 'Tambah Sub Kegiatan' ?></strong>
    </div>
    <div class="card-body">
      <form method="post" class="row g-3">
        <?php if ($editRow): ?>
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>">
        <?php else: ?>
          <input type="hidden" name="action" value="create">
        <?php endif; ?>

        <div class="col-12 col-md-6">
          <label class="form-label">RKA</label>
          <select name="rka_id" class="form-select" required>
            <option value="">-- Pilih RKA --</option>
            <?php foreach ($rka as $r): ?>
              <option value="<?= $r['id'] ?>"
                <?= $editRow && $editRow['rka_id'] == $r['id'] ? 'selected' : '' ?>>
                [<?= htmlspecialchars($r['tahun']) ?>] <?= htmlspecialchars($r['nomor_rka']) ?> — <?= htmlspecialchars($r['nama_kegiatan']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12 col-md-3">
          <label class="form-label">Kode Sub</label>
          <input type="text" name="kode_sub" class="form-control" required
            value="<?= $editRow ? htmlspecialchars($editRow['kode_sub']) : '' ?>">
        </div>

        <div class="col-12 col-md-6">
          <label class="form-label">Nama Sub</label>
          <input type="text" name="nama_sub" class="form-control" required
            value="<?= $editRow ? htmlspecialchars($editRow['nama_sub']) : '' ?>">
        </div>

        <div class="col-12 col-md-3">
          <label class="form-label">Pagu Sub</label>
          <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input type="number" step="0.01" name="pagu_sub" class="form-control"
              value="<?= $editRow ? htmlspecialchars($editRow['pagu_sub']) : '' ?>">
          </div>
        </div>

        <div class="col-12 d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <?= $editRow ? 'Simpan Perubahan' : 'Tambah Sub Kegiatan' ?>
          </button>
          <?php if ($editRow): ?>
            <a href="index.php?page=subkegiatan&cancel=1" class="btn btn-secondary">Batal</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <!-- Tabel Responsive -->
  <div class="card">
    <div class="card-header"><strong>Daftar Sub Kegiatan</strong></div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th style="min-width:140px;">RKA</th>
              <th style="min-width:120px;">Kode Sub</th>
              <th>Nama Sub</th>
              <th style="min-width:140px;">Pagu Sub</th>
              <th style="min-width:140px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($subs as $s): ?>
              <tr>
                <td>
                  <div class="small text-muted">[<?= htmlspecialchars($s['tahun']) ?>] <?= htmlspecialchars($s['nomor_rka']) ?></div>
                  <div class="small"><?= htmlspecialchars($s['nama_kegiatan']) ?></div>
                </td>
                <td><?= htmlspecialchars($s['kode_sub']) ?></td>
                <td><?= htmlspecialchars($s['nama_sub']) ?></td>
                <td>Rp <?= number_format($s['pagu_sub'], 2, ',', '.') ?></td>
                <td>
                  <a href="index.php?page=subkegiatan&edit=<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                  <a href="index.php?page=subkegiatan&del=<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline-danger"
                    onclick="return confirm('Hapus sub kegiatan ini?')">Hapus</a>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($subs)): ?>
              <tr>
                <td colspan="5" class="text-center text-muted">Belum ada data Sub Kegiatan.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>