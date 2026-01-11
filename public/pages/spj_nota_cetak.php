
<?php
Auth::requireRole(['ADMIN','PPTK','BENDAHARA']);
require_once __DIR__ . '/../../src/lib/Helpers.php';
$pdo = Database::getConnection();
$spj_id = isset($_GET['spj_id']) ? (int)$_GET['spj_id'] : 0;
if (!$spj_id) { echo '<div class="card"><div class="alert error">SPJ Nota tidak ditemukan.</div></div>'; exit; }
$spj = $pdo->prepare('SELECT * FROM spj WHERE id=? AND jenis="nota"'); $spj->execute([$spj_id]); $nota = $spj->fetch(); if (!$nota) { echo '<div class="card"><div class="alert error">Data Nota tidak ada.</div></div>'; exit; }
$rows = $pdo->prepare('SELECT rpi.id, rpi.rencana, rpi.keterangan, k.kode_komponen, k.uraian, k.satuan, k.harga_satuan FROM spj_nota_map m JOIN rencana_penyerapan_item rpi ON m.rencana_item_id = rpi.id JOIN komponen k ON rpi.komponen_id = k.id WHERE m.spj_id=? ORDER BY k.id, rpi.id'); $rows->execute([$spj_id]); $items=$rows->fetchAll(); $total=0; foreach($items as $it){ $total+=(float)$it['rencana']; }
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Cetak Nota</title>
<style>*{box-sizing:border-box;font-family:Arial,Segoe UI,Roboto}.page{width:210mm;min-height:297mm;margin:0 auto;background:#fff;padding:16mm}.header{display:flex;justify-content:space-between;margin-bottom:12mm;border-bottom:2px solid #000;padding-bottom:4mm}.vendor{font-weight:bold;font-size:18px}.small{font-size:12px;color:#444}table{width:100%;border-collapse:collapse;margin-top:6mm}th,td{border:1px solid #000;padding:6px}th{background:#f3f4f6}.footer{margin-top:10mm;display:flex;justify-content:space-between}.print{position:fixed;right:12px;top:12px}@media print {.print{display:none} .page{padding:0}}</style>
</head><body>
<button class="print" onclick="window.print()">🖨️ Cetak</button>
<div class="page"><div class="header"><div><div class="vendor"><?= htmlspecialchars($nota['vendor']) ?></div><div class="small">PENGADAAN BARANG & JASA</div></div><div class="small" style="text-align:right"><div><strong>Nomor Nota:</strong> <?= htmlspecialchars($nota['nomor_spj']) ?></div><div><strong>Tanggal:</strong> <?= Helpers::tanggal_id($nota['tanggal']) ?></div></div></div>
  <table><thead><tr><th style="width:40px">No</th><th>Nama Barang/Jasa</th><th style="width:80px">Satuan</th><th style="width:120px">Harga Satuan</th><th style="width:120px">Banyaknya</th><th style="width:140px">Jumlah (Rp)</th><th>Keterangan</th></tr></thead>
    <tbody><?php $no=1; foreach($items as $it): $qty = ($it['harga_satuan']>0) ? round($it['rencana']/$it['harga_satuan']) : ''; ?>
      <tr><td><?= $no++ ?></td><td><?= htmlspecialchars($it['uraian']) ?></td><td><?= htmlspecialchars($it['satuan'] ?? '-') ?></td><td style="text-align:right"><?= Helpers::rupiah($it['harga_satuan']) ?></td><td style="text-align:right"><?= $qty ?: '-' ?></td><td style="text-align:right"><?= Helpers::rupiah($it['rencana']) ?></td><td><?= htmlspecialchars($it['keterangan'] ?? '') ?></td></tr>
    <?php endforeach; ?></tbody>
    <tfoot><tr><th colspan="5" style="text-align:right">TOTAL</th><th style="text-align:right"><?= Helpers::rupiah($total) ?></th><th></th></tr></tfoot>
  </table>
  <div class="footer small"><div><em>Perhatian: Barang/Jasa yang sudah dibeli tidak dapat ditukar/dikembalikan.</em></div><div><strong>Tanda Terima</strong></div></div>
</div></body></html>
