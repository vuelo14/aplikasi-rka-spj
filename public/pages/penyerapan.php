<?php
Auth::requireRole(['ADMIN', 'PPTK']);
$pdo = Database::getConnection();

$msg = '';
$err = '';

// List Sub Kegiatan
$subs = $pdo->query('SELECT s.id, s.kode_sub, s.nama_sub, r.tahun, r.nomor_rka
  FROM subkegiatan s
  JOIN rka r ON s.rka_id=r.id
  ORDER BY r.tahun DESC, s.id DESC')->fetchAll();

$selected_sub = isset($_GET['sub_id']) ? (int)$_GET['sub_id'] : 0;
$komponen_list = [];

// Ambil komponen jika Sub dipilih
if ($selected_sub) {
  $stmt = $pdo->prepare('SELECT k.id, k.kode_komponen, k.uraian, k.total
    FROM komponen k WHERE k.subkegiatan_id=? ORDER BY k.id DESC');
  $stmt->execute([$selected_sub]);
  $komponen_list = $stmt->fetchAll();
}

$selected_kid = isset($_GET['komponen_id']) ? (int)$_GET['komponen_id'] : 0;

/* ================================
   CREATE RENCANA TAHAP
   ================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
  $sub_id = (int)$_POST['subkegiatan_id'];
  $komp_id = (int)$_POST['komponen_id'];
  $rencana = (float)$_POST['rencana'];
  $bulan   = $_POST['bulan_target'] !== '' ? (int)$_POST['bulan_target'] : null;
  $ket     = trim($_POST['keterangan'] ?? '');

  if ($sub_id && $komp_id && $rencana > 0) {

    // Cek pagu komponen
    $st = $pdo->prepare('SELECT total FROM komponen WHERE id=?');
    $st->execute([$komp_id]);
    $komp = $st->fetch();
    $pagu = (float)($komp['total'] ?? 0);

    // Jumlah rencana yang sudah ada
    $sum = $pdo->prepare('SELECT COALESCE(SUM(rencana),0) total FROM rencana_penyerapan_item WHERE komponen_id=?');
    $sum->execute([$komp_id]);
    $total_r = (float)$sum->fetch()['total'];

    if (($total_r + $rencana) > $pagu) {
      $err = "Rencana melebihi pagu. Sisa: Rp " . number_format($pagu - $total_r, 2, ',', '.');
    } else {
      // auto tahap (tahap terakhir + 1)
      $th = $pdo->prepare('SELECT COALESCE(MAX(tahap),0)+1 next FROM rencana_penyerapan_item WHERE komponen_id=?');
      $th->execute([$komp_id]);
      $next = (int)$th->fetch()['next'];

      $ins = $pdo->prepare('INSERT INTO rencana_penyerapan_item (subkegiatan_id,komponen_id,tahap,rencana,bulan_target,keterangan,created_by)
        VALUES (?,?,?,?,?,?,?)');
      $ins->execute([$sub_id, $komp_id, $next, $rencana, $bulan, $ket, Auth::user()['id']]);

      $msg = "Rencana tahap $next ditambahkan.";
      echo "<script>window.location.href='index.php?page=penyerapan&sub_id=" . $sub_id . "&komponen_id=" . $komp_id . "';</script>";
      exit;
    }
  } else {
    $err = 'Isi nilai rencana.';
  }
}

/* ================================
   UPDATE RENCANA TAHAP
   ================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {

  $id     = (int)$_POST['id'];
  $sub_id = (int)$_POST['subkegiatan_id'];
  $komp_id = (int)$_POST['komponen_id'];
  $rencana = (float)$_POST['rencana'];
  $bulan  = $_POST['bulan_target'] !== '' ? (int)$_POST['bulan_target'] : null;
  $ket    = trim($_POST['keterangan']);

  if ($id && $sub_id && $komp_id && $rencana > 0) {

    // cek pagu komponen
    $st = $pdo->prepare('SELECT total FROM komponen WHERE id=?');
    $st->execute([$komp_id]);
    $komp = $st->fetch();
    $pagu = (float)$komp['total'];

    // total rencana lain selain yang sedang diedit
    $sum = $pdo->prepare('SELECT COALESCE(SUM(rencana),0) total 
      FROM rencana_penyerapan_item WHERE komponen_id=? AND id<>?');
    $sum->execute([$komp_id, $id]);
    $total_lain = (float)$sum->fetch()['total'];

    if (($total_lain + $rencana) > $pagu) {
      $err = "Rencana melebihi pagu. Sisa: Rp " . number_format(($pagu - $total_lain), 2, ',', '.');
    } else {
      $upd = $pdo->prepare('UPDATE rencana_penyerapan_item 
        SET rencana=?, bulan_target=?, keterangan=? WHERE id=?');
      $upd->execute([$rencana, $bulan, $ket, $id]);

      $msg = 'Rencana tahap diperbarui.';
      header("Location: index.php?page=penyerapan&sub_id=$sub_id&komponen_id=$komp_id");
      exit;
    }
  } else {
    $err = 'Lengkapi data update.';
  }
}

/* ================================
   DELETE
   ================================ */
if (isset($_GET['del'])) {
  $del = (int)$_GET['del'];

  $ref = $pdo->prepare('SELECT subkegiatan_id, komponen_id FROM rencana_penyerapan_item WHERE id=?');
  $ref->execute([$del]);
  $r = $ref->fetch();

  $pdo->prepare('DELETE FROM rencana_penyerapan_item WHERE id=?')->execute([$del]);
  $msg = 'Rencana tahap dihapus';

  if ($r)
    header("Location: index.php?page=penyerapan&sub_id={$r['subkegiatan_id']}&komponen_id={$r['komponen_id']}");
  exit;
}

/* AMBIL DETAIL UNTUK TAMPILAN */
$komp_summary = null;
$items = [];
$edit_item = null;

if ($selected_kid) {

  $st = $pdo->prepare('SELECT k.*, s.id AS sub_id, s.kode_sub, s.nama_sub
      FROM komponen k
      JOIN subkegiatan s ON k.subkegiatan_id=s.id
      WHERE k.id=?');
  $st->execute([$selected_kid]);
  $komp_summary = $st->fetch();

  $it = $pdo->prepare('SELECT * FROM rencana_penyerapan_item WHERE komponen_id=? ORDER BY tahap ASC, id ASC');
  $it->execute([$selected_kid]);
  $items = $it->fetchAll();

  // total rencana & sisa pagu
  $sum = $pdo->prepare('SELECT COALESCE(SUM(rencana),0) total FROM rencana_penyerapan_item WHERE komponen_id=?');
  $sum->execute([$selected_kid]);
  $total_r = (float)$sum->fetch()['total'];

  $komp_summary['total_rencana'] = $total_r;
  $komp_summary['sisa'] = max(0, $komp_summary['total'] - $total_r);

  // mode Edit
  if (isset($_GET['edit_id'])) {
    $eid = (int)$_GET['edit_id'];
    $e = $pdo->prepare('SELECT * FROM rencana_penyerapan_item WHERE id=? AND komponen_id=?');
    $e->execute([$eid, $selected_kid]);
    $edit_item = $e->fetch();
  }
}
?>

<div class="container-fluid">

  <!-- Alert -->
  <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>

  <!-- PILIH SUB -->
  <div class="card mb-3">
    <div class="card-header"><strong>Pilih Sub Kegiatan</strong></div>
    <div class="card-body">
      <form method="get" class="row g-3">
        <input type="hidden" name="page" value="penyerapan">

        <div class="col-md-6">
          <select name="sub_id" class="form-select" onchange="this.form.submit()">
            <option value="">-- Pilih Sub Kegiatan --</option>
            <?php foreach ($subs as $s): ?>
              <option value="<?= $s['id'] ?>" <?= $selected_sub == $s['id'] ? 'selected' : '' ?>>
                [<?= $s['tahun'] ?>] <?= $s['nomor_rka'] ?> — <?= $s['kode_sub'] ?> - <?= $s['nama_sub'] ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </form>
    </div>
  </div>

  <?php if ($selected_sub): ?>
    <!-- PILIH KOMPONEN -->
    <div class="card mb-3">
      <div class="card-header"><strong>Pilih Komponen</strong></div>
      <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
          <input type="hidden" name="page" value="penyerapan">
          <input type="hidden" name="sub_id" value="<?= $selected_sub ?>">

          <div class="col-md-8">
            <select name="komponen_id" class="form-select">
              <option value="">-- Pilih Komponen --</option>
              <?php foreach ($komponen_list as $k): ?>
                <option value="<?= $k['id'] ?>" <?= $selected_kid == $k['id'] ? 'selected' : '' ?>>
                  <?= $k['kode_komponen'] ?> — <?= $k['uraian'] ?> (Pagu: Rp <?= number_format($k['total'], 2, ',', '.') ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <button class="btn btn-primary w-100">Tampilkan</button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($selected_kid && $komp_summary): ?>
    <!-- RINGKASAN KOMPONEN -->
    <div class="card mb-3">
      <div class="card-header"><strong>Ringkasan Komponen</strong></div>
      <div class="card-body">
        <p><strong>Kode:</strong> <?= $komp_summary['kode_sub'] ?> / <?= $komp_summary['kode_komponen'] ?></p>
        <p><strong>Nama:</strong> <?= $komp_summary['uraian'] ?></p>
        <p><strong>Pagu:</strong> Rp <?= number_format($komp_summary['total'], 2, ',', '.') ?></p>
        <p><strong>Total Rencana:</strong> Rp <?= number_format($komp_summary['total_rencana'], 2, ',', '.') ?></p>
        <p><strong>Sisa:</strong> Rp <?= number_format($komp_summary['sisa'], 2, ',', '.') ?></p>
      </div>
    </div>

    <!-- FORM TAMBAH / EDIT -->
    <div class="card mb-3">
      <div class="card-header"><strong><?= $edit_item ? 'Edit Rencana Tahap' : 'Tambah Rencana Tahap' ?></strong></div>
      <div class="card-body">

        <form method="post" class="row g-3">
          <input type="hidden" name="subkegiatan_id" value="<?= $komp_summary['sub_id'] ?>">
          <input type="hidden" name="komponen_id" value="<?= $komp_summary['id'] ?>">

          <?php if ($edit_item): ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= $edit_item['id'] ?>">
          <?php else: ?>
            <input type="hidden" name="action" value="create">
          <?php endif; ?>

          <div class="col-md-4">
            <label class="form-label">Nilai Rencana (Rp)</label>
            <input type="number" step="0.01" name="rencana" class="form-control" required
              value="<?= $edit_item ? $edit_item['rencana'] : '' ?>">
          </div>

          <div class="col-md-4">
            <label class="form-label">Bulan Target</label>
            <select name="bulan_target" class="form-select">
              <option value="">-- Tidak Ditentukan --</option>
              <?php for ($b = 1; $b <= 12; $b++): ?>
                <option value="<?= $b ?>" <?= $edit_item && $edit_item['bulan_target'] == $b ? 'selected' : '' ?>>Bulan <?= $b ?></option>
              <?php endfor; ?>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Keterangan</label>
            <input type="text" name="keterangan" class="form-control"
              value="<?= $edit_item ? htmlspecialchars($edit_item['keterangan']) : '' ?>">
          </div>

          <div class="col-12">
            <button class="btn btn-primary"><?= $edit_item ? 'Simpan Perubahan' : 'Tambah Rencana' ?></button>

            <?php if ($edit_item): ?>
              <a href="index.php?page=penyerapan&sub_id=<?= $komp_summary['sub_id'] ?>&komponen_id=<?= $komp_summary['id'] ?>" class="btn btn-secondary ms-2">Batal</a>
            <?php endif; ?>
          </div>
        </form>

      </div>
    </div>

    <!-- TABEL RENCANA -->
    <div class="card">
      <div class="card-header"><strong>Daftar Rencana Tahap</strong></div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>Tahap</th>
                <th>Nilai Rencana</th>
                <th>Bulan Target</th>
                <th>Keterangan</th>
                <th>Dibuat</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $it): ?>
                <tr>
                  <td><?= $it['tahap'] ?></td>
                  <td>Rp <?= number_format($it['rencana'], 2, ',', '.') ?></td>
                  <td><?= $it['bulan_target'] ? "Bulan " . $it['bulan_target'] : "-" ?></td>
                  <td><?= htmlspecialchars($it['keterangan']) ?></td>
                  <td><?= $it['created_at'] ?></td>
                  <td class="d-flex flex-wrap gap-2">
                    <a href="index.php?page=penyerapan&sub_id=<?= $komp_summary['sub_id'] ?>&komponen_id=<?= $komp_summary['id'] ?>&edit_id=<?= $it['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                    <a href="index.php?page=penyerapan&del=<?= $it['id'] ?>" class="btn btn-sm btn-outline-danger"
                      onclick="return confirm('Hapus rencana tahap ini?')">Hapus</a>
                  </td>
                </tr>
              <?php endforeach; ?>

              <?php if (empty($items)): ?>
                <tr>
                  <td colspan="6" class="text-center text-muted">Belum ada rencana tahap.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

</div>