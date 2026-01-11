<?php
Auth::requireRole(['ADMIN', 'PPTK', 'BENDAHARA']);
require_once __DIR__ . '/../../src/lib/Helpers.php';
$pdo = Database::getConnection();
$spj_id = isset($_GET['spj_id']) ? (int)$_GET['spj_id'] : 0;
if (!$spj_id) {
  echo '<div class="card"><div class="alert error">SPJ Nota tidak ditemukan.</div></div>';
  exit;
}
$spj = $pdo->prepare('SELECT * FROM spj WHERE id=? AND jenis="nota"');
$spj->execute([$spj_id]);
$nota = $spj->fetch();
if (!$nota) {
  echo '<div class="card"><div class="alert error">Data Nota tidak ada.</div></div>';
  exit;
}
$info = $pdo->prepare('SELECT s.id AS sub_id, s.kode_sub, s.nama_sub, r.tahun, r.nomor_rka, k.akun_belanja FROM spj_nota_map m JOIN rencana_penyerapan_item rpi ON m.rencana_item_id = rpi.id JOIN komponen k ON rpi.komponen_id = k.id JOIN subkegiatan s ON k.subkegiatan_id = s.id JOIN rka r ON s.rka_id = r.id WHERE m.spj_id=? LIMIT 1');
$info->execute([$spj_id]);
$sub = $info->fetch();
$nama_pa = $_GET['pa'] ?? 'Pengguna Anggaran';
$nama_pptk = $_GET['pptk'] ?? 'PPTK';
$nama_bend = $_GET['bendahara'] ?? 'Bendahara Pengeluaran';
$nama_penerima = $_GET['penerima'] ?? $nota['vendor'];
$total = (float)$nota['amount'];
$terbilang = Helpers::terbilang($total) . ' Rupiah';
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <title>Cetak Kuitansi</title>
  <style>
    * {
      box-sizing: border-box;
      font-family: Arial, Segoe UI, Roboto
    }

    .page {
      width: 210mm;
      min-height: 297mm;
      margin: 0 auto;
      background: #fff;
      padding: 16mm
    }

    h1 {
      font-size: 18px;
      text-align: center;
      margin: 0 0 10mm
    }

    table.meta {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 8mm
    }

    table.meta td {
      padding: 4px
    }

    .box {
      border: 1px solid #000;
      padding: 8px;
      margin: 6px 0
    }

    .grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12mm;
      margin-top: 12mm
    }

    .center {
      text-align: center
    }

    .bold {
      font-weight: bold
    }

    .print {
      position: fixed;
      right: 12px;
      top: 12px
    }

    @media print {
      .print {
        display: none
      }

      .page {
        padding: 0
      }
    }
  </style>
</head>

<body>
  <button class="print" onclick="window.print()">🖨️ Cetak</button>
  <div class="page">
    <h1>KUITANSI</h1>
    <table class="meta">
      <tr>
        <td><span class="bold">No.</span> <?= htmlspecialchars($nota['nomor_spj']) ?></td>
      </tr>
      <?php if ($sub): ?>
        <tr>
          <td><span class="bold">Sub Kegiatan</span>: <?= htmlspecialchars($sub['kode_sub']) ?> — <?= htmlspecialchars($sub['nama_sub']) ?></td>
        </tr>
        <tr>
          <td><span class="bold">Kode Rekening</span>: <?= htmlspecialchars($sub['akun_belanja'] ?: '-') ?></td>
        </tr>
      <?php endif; ?>
    </table>
    <div class="box">
      <p><span class="bold">Telah terima dari</span> Bendahara Pengeluaran Dinas Tenaga Kerja Kabupaten Indramayu</p>
      <p><span class="bold">Uang sejumlah</span> <?= Helpers::rupiah($total) ?> (<?= htmlspecialchars($terbilang) ?>)</p>
      <p><span class="bold">Untuk pembayaran</span>: <?= $sub ? 'Pembayaran barang/jasa pada Dinas Tenaga Kerja Kab. Indramayu T.A. ' . htmlspecialchars($sub['tahun']) . ' — Sub Kegiatan ' . htmlspecialchars($sub['kode_sub']) . ' (' . htmlspecialchars($sub['nama_sub']) . ')' : 'Pembayaran barang/jasa' ?>.</p>
      <p><em>Rincian terlampir pada Nota: <?= htmlspecialchars($nota['nomor_spj']) ?>, tanggal <?= Helpers::tanggal_id($nota['tanggal']) ?>.</em></p>
    </div>
    <div class="grid">
      <div class="center">
        <div class="bold">Pengguna Anggaran</div><br><br><br>
        <div><?= htmlspecialchars($nama_pa) ?></div>
      </div>
      <div class="center">
        <div class="bold">PPTK</div><br><br><br>
        <div><?= htmlspecialchars($nama_pptk) ?></div>
      </div>
      <div class="center">
        <div class="bold">Bendahara Pengeluaran</div><br><br><br>
        <div><?= htmlspecialchars($nama_bend) ?></div>
      </div>
    </div>
    <div style="margin-top:16mm" class="center">
      <div class="bold">Yang Menerima</div><br><br><br>
      <div><?= htmlspecialchars($nama_penerima) ?></div>
    </div>
    <p style="margin-top:10mm">Indramayu, <?= Helpers::tanggal_id($nota['tanggal']) ?></p>
  </div>
</body>

</html>