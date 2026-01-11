<?php
Auth::requireRole(['ADMIN', 'PPTK', 'BENDAHARA']);
$pdo = Database::getConnection();

// Ambil semua sub kegiatan
$subs = $pdo->query('SELECT s.id, s.kode_sub, s.nama_sub, s.pagu_sub, r.tahun, r.nomor_rka 
  FROM subkegiatan s 
  JOIN rka r ON s.rka_id=r.id 
  ORDER BY r.tahun DESC, s.id DESC')->fetchAll();

$selected = isset($_GET['sub_id']) ? (int)$_GET['sub_id'] : 0;

$komponen = [];
$ring = [
  'pagu_sub' => 0,
  'total_komponen' => 0,
  'total_rencana_sub' => 0,
  'total_realisasi_sub' => 0,
  'persen_rencana' => 0,
  'persen_realisasi' => 0
];

if ($selected) {
  // Info sub kegiatan
  $stmt = $pdo->prepare('SELECT s.*, r.tahun, r.nomor_rka 
    FROM subkegiatan s 
    JOIN rka r ON s.rka_id=r.id 
    WHERE s.id=?');
  $stmt->execute([$selected]);
  $subInfo = $stmt->fetch();

  // Komponen
  $komp = $pdo->prepare('SELECT 
      k.id, k.kode_komponen, k.uraian, k.volume, k.satuan, 
      k.harga_satuan, k.total,
      COALESCE((SELECT SUM(rpi.rencana) FROM rencana_penyerapan_item rpi WHERE rpi.komponen_id=k.id),0) AS total_rencana,
      COALESCE((SELECT SUM(re.jumlah) FROM realisasi re WHERE re.komponen_id=k.id),0) AS total_realisasi
    FROM komponen k
    WHERE k.subkegiatan_id=?
    ORDER BY k.id');
  $komp->execute([$selected]);
  $komponen = $komp->fetchAll();

  // Ringkasan
  $ring['pagu_sub'] = (float)$subInfo['pagu_sub'];

  foreach ($komponen as $k) {
    $ring['total_komponen'] += (float) $k['total'];
    $ring['total_rencana_sub'] += (float) $k['total_rencana'];
    $ring['total_realisasi_sub'] += (float) $k['total_realisasi'];
  }

  $base = $ring['pagu_sub'] > 0 ? $ring['pagu_sub'] : ($ring['total_komponen'] ?: 0);
  if ($base > 0) {
    $ring['persen_rencana']   = $ring['total_rencana_sub'] / $base * 100;
    $ring['persen_realisasi'] = $ring['total_realisasi_sub'] / $base * 100;
  }
}
?>

<div class="container-fluid">

  <!-- PILIH SUB KEGIATAN -->
  <div class="card mb-3">
    <div class="card-header"><strong>Pilih Sub Kegiatan</strong></div>
    <div class="card-body">
      <form method="get" class="row g-3">
        <input type="hidden" name="page" value="laporan_sub">
        <div class="col-12 col-md-6">
          <select name="sub_id" class="form-select" onchange="this.form.submit()">
            <option value="">-- Pilih Sub Kegiatan --</option>
            <?php foreach ($subs as $s): ?>
              <option value="<?= $s['id'] ?>"
                <?= $selected == $s['id'] ? 'selected' : '' ?>>
                [<?= htmlspecialchars($s['tahun']) ?>]
                <?= htmlspecialchars($s['nomor_rka']) ?> —
                <?= htmlspecialchars($s['kode_sub']) ?> -
                <?= htmlspecialchars($s['nama_sub']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </form>
    </div>
  </div>

  <!-- RINGKASAN (jika ada pilihan sub) -->
  <?php if ($selected && isset($subInfo)): ?>
    <div class="card mb-3">
      <div class="card-header"><strong>Ringkasan Sub Kegiatan</strong></div>
      <div class="card-body">

        <div class="row g-3">
          <div class="col-12 col-md-6">
            <p><strong>RKA:</strong> [<?= $subInfo['tahun'] ?>] <?= htmlspecialchars($subInfo['nomor_rka']) ?></p>
            <p><strong>Sub Kegiatan:</strong><br>
              <?= htmlspecialchars($subInfo['kode_sub']) ?> —
              <?= htmlspecialchars($subInfo['nama_sub']) ?>
            </p>
          </div>

          <div class="col-12 col-md-6">
            <p><strong>Pagu Sub:</strong> Rp <?= number_format($ring['pagu_sub'], 2, ',', '.') ?></p>
            <p><strong>Total Komponen:</strong> Rp <?= number_format($ring['total_komponen'], 2, ',', '.') ?></p>
            <p><strong>Total Rencana:</strong> Rp <?= number_format($ring['total_rencana_sub'], 2, ',', '.') ?></p>
            <p><strong>Total Realisasi:</strong> Rp <?= number_format($ring['total_realisasi_sub'], 2, ',', '.') ?></p>
            <p>
              <strong>Serapan Rencana:</strong>
              <?= number_format($ring['persen_rencana'], 2) ?>% &nbsp; | &nbsp;
              <strong>Serapan Realisasi:</strong>
              <?= number_format($ring['persen_realisasi'], 2) ?>%
            </p>
          </div>
        </div>

      </div>
    </div>
  <?php endif; ?>

  <!-- TABEL KOMPONEN -->
  <?php if ($selected && $komponen): ?>
    <div class="card">
      <div class="card-header">
        <strong>Detail Komponen (Rencana & Realisasi)</strong>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>Kode</th>
                <th>Uraian</th>
                <th>Vol</th>
                <th>Satuan</th>
                <th>Harga</th>
                <th>Pagu Komponen</th>
                <th>Total Rencana</th>
                <th>Total Realisasi</th>
                <th>Serapan Rencana (%)</th>
                <th>Serapan Realisasi (%)</th>
                <th>Sisa Pagu</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($komponen as $k):
                $persenR = $k['total'] > 0 ? ($k['total_rencana'] / $k['total'] * 100) : 0;
                $persenS = $k['total'] > 0 ? ($k['total_realisasi'] / $k['total'] * 100) : 0;
                $sisa = $k['total'] - $k['total_realisasi'];
              ?>
                <tr>
                  <td><?= htmlspecialchars($k['kode_komponen']) ?></td>
                  <td><?= htmlspecialchars($k['uraian']) ?></td>
                  <td><?= htmlspecialchars($k['volume']) ?></td>
                  <td><?= htmlspecialchars($k['satuan']) ?></td>
                  <td>Rp <?= number_format($k['harga_satuan'], 2, ',', '.') ?></td>
                  <td>Rp <?= number_format($k['total'], 2, ',', '.') ?></td>
                  <td>Rp <?= number_format($k['total_rencana'], 2, ',', '.') ?></td>
                  <td>Rp <?= number_format($k['total_realisasi'], 2, ',', '.') ?></td>
                  <td><?= number_format($persenR, 2) ?>%</td>
                  <td><?= number_format($persenS, 2) ?>%</td>
                  <td>Rp <?= number_format($sisa, 2, ',', '.') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

</div>