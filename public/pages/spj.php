<?php
Auth::requireRole(['ADMIN', 'PPTK', 'BENDAHARA']);
$pdo = Database::getConnection();

$msg = '';
$err = '';

// Ambil data komponen & SPJ
$komp = $pdo->query('SELECT id, kode_komponen, uraian FROM komponen ORDER BY id DESC')->fetchAll();

/* ================================
   CREATE SPJ
   ================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
  $kid     = (int)($_POST['komponen_id'] ?? 0);
  $nomor   = trim($_POST['nomor_spj'] ?? '');
  $jenis   = trim($_POST['jenis'] ?? '');
  $tanggal = trim($_POST['tanggal'] ?? '');
  $vendor  = trim($_POST['vendor'] ?? '');
  $uraian  = trim($_POST['uraian'] ?? '');
  $amount  = (float)($_POST['amount'] ?? 0);

  if ($jenis && $tanggal) {
    $stmt = $pdo->prepare(
      'INSERT INTO spj (komponen_id,nomor_spj,jenis,tanggal,vendor,uraian,amount,file_path)
       VALUES (?,?,?,?,?,?,?, "")'
    );
    $stmt->execute([$kid, $nomor, $jenis, $tanggal, $vendor, $uraian, $amount]);
    $msg = 'SPJ berhasil ditambahkan.';
  } else {
    $err = 'Jenis dan tanggal SPJ wajib diisi.';
  }
}

/* ================================
   UPDATE / EDIT SPJ
   ================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
  $id      = (int)($_POST['id'] ?? 0);
  $kid     = (int)($_POST['komponen_id'] ?? 0);
  $nomor   = trim($_POST['nomor_spj'] ?? '');
  $jenis   = trim($_POST['jenis'] ?? '');
  $tanggal = trim($_POST['tanggal'] ?? '');
  $vendor  = trim($_POST['vendor'] ?? '');
  $uraian  = trim($_POST['uraian'] ?? '');
  $amount  = (float)($_POST['amount'] ?? 0);

  if ($id && $jenis && $tanggal) {
    $stmt = $pdo->prepare('UPDATE spj
      SET komponen_id=?, nomor_spj=?, jenis=?, tanggal=?, vendor=?, uraian=?, amount=?
      WHERE id=?');
    $stmt->execute([$kid, $nomor, $jenis, $tanggal, $vendor, $uraian, $amount, $id]);
    $msg = 'SPJ berhasil diperbarui.';
  } else {
    $err = 'Lengkapi data edit SPJ.';
  }
}

/* ================================
   DELETE SPJ
   ================================ */
if (isset($_GET['del'])) {
  $id = (int)$_GET['del'];
  $pdo->prepare('DELETE FROM spj WHERE id=?')->execute([$id]);
  $msg = 'SPJ dihapus.';
}


// Tambah di atas: proses generate realisasi dari Nota
if (isset($_GET['gen_realisasi_spj'])) {
  $spj_id = (int)$_GET['gen_realisasi_spj'];

  // pastikan spj adalah nota
  $cek = $pdo->prepare('SELECT * FROM spj WHERE id=? AND LOWER(jenis)="nota"');
  $cek->execute([$spj_id]);
  $nota = $cek->fetch();

  if ($nota) {
    // Ambil total per komponen dari item rencana yang terikat ke SPJ Nota ini
    $q = $pdo->prepare('
      SELECT rpi.komponen_id, COALESCE(SUM(rpi.rencana),0) total_komp
      FROM spj_nota_map m
      JOIN rencana_penyerapan_item rpi ON m.rencana_item_id = rpi.id
      WHERE m.spj_id=?
      GROUP BY rpi.komponen_id
    ');
    $q->execute([$spj_id]);
    $byKomponen = $q->fetchAll();

    $ins = $pdo->prepare('INSERT INTO realisasi (komponen_id, spj_id, tanggal, jumlah, keterangan) VALUES (?,?,?,?,?)');

    foreach ($byKomponen as $row) {
      $komp_id = (int)$row['komponen_id'];
      $jumlah  = (float)$row['total_komp'];
      // Cegah duplikasi (kalau sudah pernah digenerate)
      $cekDup = $pdo->prepare('SELECT COUNT(*) c FROM realisasi WHERE spj_id=? AND komponen_id=?');
      $cekDup->execute([$spj_id, $komp_id]);
      if ((int)$cekDup->fetch()['c'] === 0 && $jumlah > 0) {
        $ins->execute([$komp_id, $spj_id, $nota['tanggal'], $jumlah, 'Realisasi dari Nota ' . $nota['nomor_spj']]);
      }
    }
    $msg = 'Realisasi berhasil digenerate dari Nota.';
  } else {
    $err = 'SPJ bukan Nota atau tidak ditemukan.';
  }
}


/* ================================
   Ambil data untuk EDIT form
   ================================ */
$editRow = null;
if (isset($_GET['edit'])) {
  $eid = (int)$_GET['edit'];
  $stmt = $pdo->prepare('SELECT * FROM spj WHERE id=?');
  $stmt->execute([$eid]);
  $editRow = $stmt->fetch();
}

/* ================================
   Ambil semua SPJ untuk tabel
   ================================ */
$rows = $pdo->query('SELECT s.*, k.kode_komponen
  FROM spj s
  LEFT JOIN komponen k ON s.komponen_id=k.id
  ORDER BY s.id DESC')->fetchAll();
?>

<div class="container-fluid">

  <!-- Alerts -->
  <?php if ($msg): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?= htmlspecialchars($msg) ?>
      <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if ($err): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?= htmlspecialchars($err) ?>
      <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Form SPJ (responsive) -->
  <div class="card mb-3">
    <div class="card-header">
      <strong><?= $editRow ? 'Edit SPJ' : 'Tambah SPJ' ?></strong>
    </div>
    <div class="card-body">
      <form method="post" class="row g-3">

        <?php if ($editRow): ?>
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" value="<?= $editRow['id'] ?>">
        <?php else: ?>
          <input type="hidden" name="action" value="create">
        <?php endif; ?>

        <div class="col-12 col-md-4">
          <label class="form-label">Komponen (opsional)</label>
          <select name="komponen_id" class="form-select">
            <option value="">--Tanpa Komponen--</option>
            <?php foreach ($komp as $k): ?>
              <option value="<?= $k['id'] ?>"
                <?= $editRow && $editRow['komponen_id'] == $k['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($k['kode_komponen'] . ' - ' . $k['uraian']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12 col-md-4">
          <label class="form-label">Nomor SPJ</label>
          <input type="text" name="nomor_spj" class="form-control"
            value="<?= $editRow ? htmlspecialchars($editRow['nomor_spj']) : '' ?>">
        </div>

        <div class="col-12 col-md-4">
          <label class="form-label">Jenis</label>
          <select name="jenis" class="form-select" required>
            <?php
            $jenisList = ['kuitansi' => 'Kuitansi', 'nota' => 'Nota', 'dokumentasi' => 'Dokumentasi'];
            ?>
            <option value="">--Pilih Jenis--</option>
            <?php foreach ($jenisList as $val => $label): ?>
              <option value="<?= $val ?>"
                <?= $editRow && $editRow['jenis'] == $val ? 'selected' : '' ?>>
                <?= $label ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12 col-md-4">
          <label class="form-label">Tanggal</label>
          <input type="date" name="tanggal" class="form-control" required
            value="<?= $editRow ? $editRow['tanggal'] : '' ?>">
        </div>

        <div class="col-12 col-md-4">
          <label class="form-label">Vendor</label>
          <input type="text" name="vendor" class="form-control"
            value="<?= $editRow ? htmlspecialchars($editRow['vendor']) : '' ?>">
        </div>

        <div class="col-12">
          <label class="form-label">Uraian</label>
          <input type="text" name="uraian" class="form-control"
            value="<?= $editRow ? htmlspecialchars($editRow['uraian']) : '' ?>">
        </div>

        <div class="col-12 col-md-4">
          <label class="form-label">Jumlah</label>
          <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input type="number" name="amount" step="0.01" class="form-control"
              value="<?= $editRow ? $editRow['amount'] : '' ?>">
          </div>
        </div>

        <div class="col-12 d-flex gap-2">
          <button class="btn btn-primary" type="submit">
            <?= $editRow ? 'Simpan Perubahan' : 'Tambah SPJ' ?>
          </button>

          <?php if ($editRow): ?>
            <a href="index.php?page=spj" class="btn btn-secondary">Batal</a>
          <?php endif; ?>
        </div>

      </form>
    </div>
  </div>

  <!-- Tabel SPJ -->
  <div class="card">
    <div class="card-header"><strong>Data SPJ</strong></div>
    <div class="card-body">

      <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Nomor</th>
              <th>Jenis</th>
              <th>Tanggal</th>
              <th>Vendor</th>
              <th>Jumlah</th>
              <th>Komponen</th>
              <th style="min-width:160px;">Aksi</th>
            </tr>
          </thead>

          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><?= htmlspecialchars($r['nomor_spj']) ?></td>
                <td><?= htmlspecialchars(ucfirst($r['jenis'])) ?></td>
                <td><?= htmlspecialchars($r['tanggal']) ?></td>
                <td><?= htmlspecialchars($r['vendor']) ?></td>
                <td>Rp <?= number_format($r['amount'], 2, ',', '.') ?></td>
                <td><?= htmlspecialchars($r['kode_komponen'] ?? '-') ?></td>

                <td class="d-flex flex-wrap gap-2">

                  <!-- Edit -->
                  <a href="index.php?page=spj&edit=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>

                  <!-- Hapus -->
                  <a href="index.php?page=spj&del=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger"
                    onclick="return confirm('Hapus SPJ ini?')">Hapus</a>

                  <!-- Cetak Nota -->

                  <?php if ($r['jenis'] === 'nota'): ?>
                    <a href="index.php?page=spj_nota_cetak&spj_id=<?= $r['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Cetak Nota</a> |
                    <a href="index.php?page=spj_kwitansi_cetak&spj_id=<?= $r['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Cetak Kuitansi</a> |
                    <a href="?page=spj&gen_realisasi_spj=<?= $r['id'] ?>" onclick="return confirm('Generate realisasi dari Nota ini?')" class="btn btn-sm btn-outline-info">Generate Realisasi</a> |
                  <?php endif; ?>


                </td>

              </tr>
            <?php endforeach; ?>

            <?php if (empty($rows)): ?>
              <tr>
                <td colspan="7" class="text-center text-muted">Belum ada data SPJ.</td>
              </tr>
            <?php endif; ?>
          </tbody>

        </table>
      </div>

    </div>
  </div>

</div>