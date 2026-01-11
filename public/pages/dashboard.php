
<?php
$pdo = Database::getConnection();
$rka = $pdo->query('SELECT r.id, r.tahun, r.nomor_rka, r.nama_kegiatan, r.pagu_total,
  COALESCE((SELECT SUM(jumlah) FROM realisasi re JOIN komponen k ON re.komponen_id=k.id JOIN subkegiatan s ON k.subkegiatan_id=s.id WHERE s.rka_id=r.id),0) AS total_realisasi
  FROM rka r ORDER BY tahun DESC, id DESC')->fetchAll();
?>
<div class="card">
  <h2>Ringkasan RKA</h2>
  <table>
    <thead><tr><th>Tahun</th><th>Nomor</th><th>Nama Kegiatan</th><th>Pagu</th><th>Realisasi</th><th>Serapan (%)</th></tr></thead>
    <tbody>
      <?php foreach($rka as $row): $persen = $row['pagu_total']>0 ? ($row['total_realisasi']/$row['pagu_total']*100) : 0; ?>
      <tr>
        <td><?= htmlspecialchars($row['tahun']) ?></td>
        <td><?= htmlspecialchars($row['nomor_rka']) ?></td>
        <td><?= htmlspecialchars($row['nama_kegiatan']) ?></td>
        <td>Rp <?= number_format($row['pagu_total'],2,',','.') ?></td>
        <td>Rp <?= number_format($row['total_realisasi'],2,',','.') ?></td>
        <td><?= number_format($persen,2) ?>%</td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
