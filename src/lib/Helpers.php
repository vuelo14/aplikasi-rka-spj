
<?php
class Helpers
{
  public static function rupiah($n, $dec = 0)
  {
    return 'Rp ' . number_format((float)$n, $dec, ',', '.');
  }

  // Terbilang sederhana (Indonesia) untuk integer <= triliun; bisa disesuaikan
  public static function terbilang($nilai)
  {
    $nilai = (int)round($nilai);
    $huruf = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
    if ($nilai < 12) return $huruf[$nilai];
    if ($nilai < 20) return self::terbilang($nilai - 10) . " Belas";
    if ($nilai < 100) return self::terbilang(intval($nilai / 10)) . " Puluh " . self::terbilang($nilai % 10);
    if ($nilai < 200) return "Seratus " . self::terbilang($nilai - 100);
    if ($nilai < 1000) return self::terbilang(intval($nilai / 100)) . " Ratus " . self::terbilang($nilai % 100);
    if ($nilai < 2000) return "Seribu " . self::terbilang($nilai - 1000);
    if ($nilai < 1000000) return self::terbilang(intval($nilai / 1000)) . " Ribu " . self::terbilang($nilai % 1000);
    if ($nilai < 1000000000) return self::terbilang(intval($nilai / 1000000)) . " Juta " . self::terbilang($nilai % 1000000);
    if ($nilai < 1000000000000) return self::terbilang(intval($nilai / 1000000000)) . " Miliar " . self::terbilang($nilai % 1000000000);
    return self::terbilang(intval($nilai / 1000000000000)) . " Triliun " . self::terbilang($nilai % 1000000000000);
  }

  public static function tanggal_id($date)
  {
    // $date: 'YYYY-MM-DD' → 'DD/MM/YYYY'
    if (!$date) return '-';
    $p = explode('-', $date);
    if (count($p) === 3) return $p[2] . '/' . $p[1] . '/' . $p[0];
    return $date;
  }
}
