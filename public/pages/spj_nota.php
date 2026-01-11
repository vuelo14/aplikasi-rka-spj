<?php
Auth::requireRole(['ADMIN', 'PPTK', 'BENDAHARA']);
require_once __DIR__ . '/../../src/lib/Helpers.php';
$pdo = Database::getConnection();

$msg = '';
$err = '';

/* =====================================
   Ambil daftar Sub Kegiatan
===================================== */
$subs = $pdo->query('
  SELECT s.id, s.kode_sub, s.nama_sub, r.tahun, r.nomor_rka
  FROM subkegiatan s 
  JOIN rka r ON s.rka_id=r.id
  ORDER BY r.tahun DESC, s.id DESC
')->fetchAll();

$sub_id = isset($_GET['sub_id']) ? (int)$_GET['sub_id'] : 0;
$akun   = isset($_GET['akun']) ? trim($_GET['akun']) : '';
$tahap  = isset($_GET['tahap']) ? (int)$_GET['tahap'] : 0;

/* =====================================
   Ambil daftar akun sesuai sub kegiatan
===================================== */
$akuns = [];
if ($sub_id) {
  $st = $pdo->prepare('SELECT DISTINCT akun_belanja FROM komponen WHERE subkegiatan_id=? ORDER BY akun_belanja');
  $st->execute([$sub_id]);
  $akuns = array_column($st->fetchAll(), 'akun_belanja');
}

/* =====================================
   Ambil daftar tahap
===================================== */
$tahaps = [];
if ($sub_id && $akun) {
  $st = $pdo->prepare('
    SELECT DISTINCT tahap 
    FROM rencana_penyerapan_item rpi
    JOIN komponen k ON rpi.komponen_id=k.id
    WHERE rpi.subkegiatan_id=? AND k.akun_belanja=?
    ORDER BY tahap
  ');
  $st->execute([$sub_id, $akun]);
  $tahaps = array_column($st->fetchAll(), 'tahap');
}

/* =====================================
   Preview item rencana untuk Nota
===================================== */
$preview_items = [];
$preview_total = 0;

if ($sub_id && $akun && $tahap) {
  $st = $pdo->prepare('
    SELECT rpi.id AS rpi_id, rpi.rencana, rpi.keterangan,
           k.uraian, k.satuan, k.harga_satuan
    FROM rencana_penyerapan_item rpi
    JOIN komponen k ON rpi.komponen_id=k.id
    WHERE rpi.subkegiatan_id=? AND k.akun_belanja=? AND rpi.tahap=?
    ORDER BY k.id, rpi.id
  ');
  $st->execute([$sub_id, $akun, $tahap]);
  $preview_items = $st->fetchAll();

  foreach ($preview_items as $p) $preview_total += (float)$p['rencana'];
}

/* =====================================
   MODE EDIT NOTA (jika ?edit=ID)
===================================== */
$editRow = null;
if (isset($_GET['edit'])) {
  $eid = (int)$_GET['edit'];
  $st = $pdo->prepare('SELECT * FROM spj WHERE id=? AND jenis="nota"');
  $st->execute([$eid]);
  $editRow = $st->fetch();
}

/* =====================================
   UPDATE NOTA
===================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_nota') {

  $id      = (int)$_POST['id'];
  $nomor   = trim($_POST['nomor_spj'] ?? '');
  $vendor  = trim($_POST['vendor'] ?? '');
  $tanggal = trim($_POST['tanggal'] ?? '');
  $uraian  = trim($_POST['uraian'] ?? '');

  if ($id && $nomor && $vendor && $tanggal) {
    $pdo->prepare('UPDATE spj SET nomor_spj=?, vendor=?, tanggal=?, uraian=? WHERE id=?')
      ->execute([$nomor, $vendor, $tanggal, $uraian, $id]);
    $msg = "Nota diperbarui.";
    $editRow = null;
  } else {
    $err = "Lengkapi semua data Nota saat edit.";
  }
}

/* =====================================
   CREATE NOTA BARU
===================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_nota') {

  $sub_id = (int)$_POST['sub_id'];
  $akun   = trim($_POST['akun']);
  $tahap  = (int)$_POST['tahap'];
  $vendor = trim($_POST['vendor']);
  $nomor  = trim($_POST['nomor_spj']);
  $tanggal = trim($_POST['tanggal']);

  if (!$sub_id || !$akun || !$tahap || !$vendor || !$nomor || !$tanggal) {
    $err = 'Lengkapi semua field Nota.';
  } else {
    // Ambil item rencana
    $st = $pdo->prepare('
      SELECT rpi.id AS rpi_id, rpi.rencana, k.id AS komponen_id
      FROM rencana_penyerapan_item rpi
      JOIN komponen k ON rpi.komponen_id=k.id
      WHERE rpi.subkegiatan_id=? AND k.akun_belanja=? AND rpi.tahap=?
    ');
    $st->execute([$sub_id, $akun, $tahap]);
    $rows = $st->fetchAll();

    if (!$rows) {
      $err = 'Tidak ada item rencana untuk Nota ini.';
    } else {
      $total = 0;
      foreach ($rows as $r) $total += (float)$r['rencana'];

      // Ambil satu komponen_id (untuk FK saja)
      $komp_id = (int)$rows[0]['komponen_id'];

      // Insert SPJ Nota
      $pdo->prepare('
        INSERT INTO spj (komponen_id,nomor_spj,jenis,tanggal,vendor,uraian,amount,file_path)
        VALUES (?,?,?,?,?,?,?, "")
      ')->execute([$komp_id, $nomor, 'nota', $tanggal, $vendor, "Nota — " . $nomor, $total]);

      $spj_id = (int)$pdo->lastInsertId();

      // Mapping item
      $map = $pdo->prepare('INSERT INTO spj_nota_map (spj_id,rencana_item_id) VALUES (?,?)');
      foreach ($rows as $r) {
        $map->execute([$spj_id, $r['rpi_id']]);
      }

      $msg = 'Nota berhasil dibuat.';
      echo "<script>window.location.href='index.php?page=spj_nota_cetak&spj_id=" . $spj_id . "';</script>";
      exit;
    }
  }
}

?>
<!-- ========== START PAGE =========== -->
<div class="container-fluid">

  <!-- ALERT -->
  <?php if ($msg): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= $msg ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if ($err): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= $err ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- ========================================
        FORM EDIT NOTA (Muncul jika ?edit=ID)
  ========================================== -->
  <?php if ($editRow): ?>
    <div class="card mb-4">
      <div class="card-header"><strong>Edit Nota</strong></div>
      <div class="card-body">
        <form method="post" class="row g-3">
          <input type="hidden" name="action" value="update_nota">
          <input type="hidden" name="id" value="<?= $editRow['id'] ?>">

          <div class="col-md-4">
            <label class="form-label">Nomor Nota</label>
            <input type="text" class="form-control" name="nomor_spj"
              value="<?= htmlspecialchars($editRow['nomor_spj']) ?>">
          </div>

          <div class="col-md-4">
            <label class="form-label">Vendor</label>
            <input type="text" class="form-control" name="vendor"
              value="<?= htmlspecialchars($editRow['vendor']) ?>">
          </div>

          <div class="col-md-4">
            <label class="form-label">Tanggal</label>
            <input type="date" class="form-control" name="tanggal"
              value="<?= htmlspecialchars($editRow['tanggal']) ?>">
          </div>

          <div class="col-12">
            <label class="form-label">Uraian</label>
            <input type="text" class="form-control" name="uraian"
              value="<?= htmlspecialchars($editRow['uraian']) ?>">
          </div>

          <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
            <a href="index.php?page=spj_nota" class="btn btn-secondary">Batal</a>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <!-- ========================================
        FORM FILTER SUB/AKUN/TAHAP
  ========================================== -->
  <div class="card mb-4">
    <div class="card-header"><strong>Buat Nota Berdasarkan Rencana</strong></div>
    <div class="card-body">
      <form method="get" class="row g-3">
        <input type="hidden" name="page" value="spj_nota">

        <div class="col-md-4">
          <label class="form-label">Sub Kegiatan</label>
          <select name="sub_id" class="form-select" onchange="this.form.submit()">
            <option value="">-- Pilih Sub Kegiatan --</option>
            <?php foreach ($subs as $s): ?>
              <option value="<?= $s['id'] ?>" <?= $sub_id == $s['id'] ? 'selected' : '' ?>>
                [<?= $s['tahun'] ?>] <?= $s['nomor_rka'] ?> — <?= $s['kode_sub'] ?> (<?= $s['nama_sub'] ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label">Akun Belanja</label>
          <select name="akun" class="form-select" onchange="this.form.submit()">
            <option value="">-- Pilih Akun --</option>
            <?php foreach ($akuns as $a): ?>
              <option value="<?= $a ?>" <?= $akun === $a ? 'selected' : '' ?>><?= $a ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label">Tahap</label>
          <select name="tahap" class="form-select" onchange="this.form.submit()">
            <option value="">-- Pilih Tahap --</option>
            <?php foreach ($tahaps as $t): ?>
              <option value="<?= $t ?>" <?= $tahap == $t ? 'selected' : '' ?>>Tahap <?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </form>
    </div>
  </div>

  <!-- ========================================
        PREVIEW ITEM
  ========================================== -->
  <?php if ($sub_id && $akun && $tahap): ?>
    <div class="card mb-4">
      <div class="card-header"><strong>Preview Item Nota</strong></div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>No</th>
                <th>Nama Barang/Jasa</th>
                <th>Satuan</th>
                <th>Harga Satuan</th>
                <th>Banyaknya</th>
                <th>Jumlah</th>
                <th>Keterangan</th>
              </tr>
            </thead>
            <tbody>
              <?php $no = 1;
              foreach ($preview_items as $p):
                $qty = $p['harga_satuan'] > 0 ? round($p['rencana'] / $p['harga_satuan']) : '-';
              ?>
                <tr>
                  <td><?= $no++ ?></td>
                  <td><?= htmlspecialchars($p['uraian']) ?></td>
                  <td><?= htmlspecialchars($p['satuan'] ?? '-') ?></td>
                  <td><?= Helpers::rupiah($p['harga_satuan']) ?></td>
                  <td><?= $qty ?></td>
                  <td><?= Helpers::rupiah($p['rencana']) ?></td>
                  <td><?= htmlspecialchars($p['keterangan'] ?? '') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr>
                <th colspan="5" class="text-end">TOTAL</th>
                <th><?= Helpers::rupiah($preview_total) ?></th>
                <th></th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- FORM BUAT NOTA -->
    <div class="card mb-4">
      <div class="card-header"><strong>Data Nota</strong></div>
      <div class="card-body">
        <form method="post" class="row g-3">
          <input type="hidden" name="action" value="create_nota">
          <input type="hidden" name="sub_id" value="<?= $sub_id ?>">
          <input type="hidden" name="akun" value="<?= $akun ?>">
          <input type="hidden" name="tahap" value="<?= $tahap ?>">

          <div class="col-md-4">
            <label class="form-label">Vendor</label>
            <input type="text" name="vendor" class="form-control" required>
          </div>

          <div class="col-md-4">
            <label class="form-label">Nomor Nota</label>
            <input type="text" name="nomor_spj" class="form-control" required>
          </div>

          <div class="col-md-4">
            <label class="form-label">Tanggal</label>
            <input type="date" name="tanggal" class="form-control" required>
          </div>

          <div class="col-12">
            <button class="btn btn-primary">Buat Nota & Cetak</button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <!-- ========================================
        TABEL DAFTAR NOTA (edit/hapus)
  ========================================== -->
  <div class="card mb-4">
    <div class="card-header"><strong>Daftar Nota SPJ</strong></div>
    <div class="card-body">

      <?php
      $notaRows = $pdo->query('SELECT * FROM spj WHERE jenis="nota" ORDER BY id DESC')->fetchAll();
      ?>

      <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Nomor</th>
              <th>Vendor</th>
              <th>Tanggal</th>
              <th>Jumlah</th>
              <th style="min-width:180px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($notaRows as $n): ?>
              <tr>
                <td><?= htmlspecialchars($n['nomor_spj']) ?></td>
                <td><?= htmlspecialchars($n['vendor']) ?></td>
                <td><?= htmlspecialchars($n['tanggal']) ?></td>
                <td><?= Helpers::rupiah($n['amount']) ?></td>
                <td class="d-flex flex-wrap gap-2">
                  <a href="index.php?page=spj_nota&edit=<?= $n['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                  <a href="index.php?page=spj_nota_cetak&spj_id=<?= $n['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Cetak Nota</a>
                  <a href="index.php?page=spj_kwitansi_cetak&spj_id=<?= $n['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Cetak Kuitansi</a>
                </td>
              </tr>
            <?php endforeach; ?>

            <?php if (empty($notaRows)): ?>
              <tr>
                <td colspan="5" class="text-center text-muted">Belum ada Nota.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>

</div>