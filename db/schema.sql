
-- Schema untuk Aplikasi RKA & SPJ
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('ADMIN','PPTK','BENDAHARA') NOT NULL DEFAULT 'PPTK',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rka (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tahun INT NOT NULL,
  nomor_rka VARCHAR(50) NOT NULL,
  nama_kegiatan VARCHAR(255) NOT NULL,
  pagu_total DECIMAL(18,2) NOT NULL DEFAULT 0,
  created_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS subkegiatan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  rka_id INT NOT NULL,
  kode_sub VARCHAR(50) NOT NULL,
  nama_sub VARCHAR(255) NOT NULL,
  pagu_sub DECIMAL(18,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (rka_id) REFERENCES rka(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS komponen (
  id INT AUTO_INCREMENT PRIMARY KEY,
  subkegiatan_id INT NOT NULL,
  kode_komponen VARCHAR(50) NOT NULL,
  akun_belanja VARCHAR(50),
  uraian TEXT NOT NULL,
  volume DECIMAL(18,2) NOT NULL DEFAULT 1,
  satuan VARCHAR(50) NOT NULL,
  harga_satuan DECIMAL(18,2) NOT NULL DEFAULT 0,
  total DECIMAL(18,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (subkegiatan_id) REFERENCES subkegiatan(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Model rencana penyerapan bertahap per komponen di sub kegiatan
CREATE TABLE IF NOT EXISTS rencana_penyerapan_item (
  id INT AUTO_INCREMENT PRIMARY KEY,
  subkegiatan_id INT NOT NULL,
  komponen_id INT NOT NULL,
  tahap INT NOT NULL DEFAULT 1,
  rencana DECIMAL(18,2) NOT NULL DEFAULT 0,
  bulan_target TINYINT NULL,
  keterangan VARCHAR(255) NULL,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (subkegiatan_id) REFERENCES subkegiatan(id) ON DELETE CASCADE,
  FOREIGN KEY (komponen_id) REFERENCES komponen(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_sub_komp (subkegiatan_id, komponen_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS spj (
  id INT AUTO_INCREMENT PRIMARY KEY,
  komponen_id INT,
  nomor_spj VARCHAR(100),
  jenis ENUM('kuitansi','nota','dokumentasi') NOT NULL,
  tanggal DATE NOT NULL,
  vendor VARCHAR(150),
  uraian TEXT,
  amount DECIMAL(18,2) DEFAULT 0,
  file_path VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (komponen_id) REFERENCES komponen(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS realisasi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  komponen_id INT NOT NULL,
  spj_id INT,
  tanggal DATE NOT NULL,
  jumlah DECIMAL(18,2) NOT NULL DEFAULT 0,
  keterangan TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (komponen_id) REFERENCES komponen(id) ON DELETE CASCADE,
  FOREIGN KEY (spj_id) REFERENCES spj(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Map Nota ke item rencana serap
CREATE TABLE IF NOT EXISTS spj_nota_map (
  id INT AUTO_INCREMENT PRIMARY KEY,
  spj_id INT NOT NULL,
  rencana_item_id INT NOT NULL,
  FOREIGN KEY (spj_id) REFERENCES spj(id) ON DELETE CASCADE,
  FOREIGN KEY (rencana_item_id) REFERENCES rencana_penyerapan_item(id) ON DELETE CASCADE,
  UNIQUE KEY uniq (spj_id, rencana_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS=1;
