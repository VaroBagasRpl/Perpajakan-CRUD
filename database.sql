-- ========================================================
-- SISTEM INFORMASI PERPAJAKAN
-- Database Schema & Sample Data
-- Berdasarkan Dokumen Struktur Database Perpajakan
-- ========================================================

CREATE DATABASE IF NOT EXISTS `perpajakan` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `perpajakan`;

-- 1. Tabel Wajib Pajak
DROP TABLE IF EXISTS `pembayaran`;
DROP TABLE IF EXISTS `tagihan`;
DROP TABLE IF EXISTS `spt`;
DROP TABLE IF EXISTS `objek_pajak`;
DROP TABLE IF EXISTS `tarif_pajak`;
DROP TABLE IF EXISTS `masa_pajak`;
DROP TABLE IF EXISTS `jenis_pajak`;
DROP TABLE IF EXISTS `petugas`;
DROP TABLE IF EXISTS `wajib_pajak`;

CREATE TABLE `wajib_pajak` (
  `id_wajib_pajak` INT NOT NULL AUTO_INCREMENT,
  `npwp` VARCHAR(25) NOT NULL,
  `nama` VARCHAR(100) NOT NULL,
  `nik` VARCHAR(20) DEFAULT NULL,
  `alamat` TEXT DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `no_telepon` VARCHAR(20) DEFAULT NULL,
  `tanggal_daftar` DATE DEFAULT NULL,
  `status` ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
  PRIMARY KEY (`id_wajib_pajak`),
  UNIQUE KEY `uk_npwp` (`npwp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabel Petugas
CREATE TABLE `petugas` (
  `id_petugas` INT NOT NULL AUTO_INCREMENT,
  `nama_petugas` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `jabatan` VARCHAR(50) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`id_petugas`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabel Jenis Pajak
CREATE TABLE `jenis_pajak` (
  `id_jenis_pajak` INT NOT NULL AUTO_INCREMENT,
  `kode_pajak` VARCHAR(20) DEFAULT NULL,
  `nama_pajak` VARCHAR(100) NOT NULL,
  `keterangan` TEXT DEFAULT NULL,
  PRIMARY KEY (`id_jenis_pajak`),
  UNIQUE KEY `uk_kode_pajak` (`kode_pajak`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabel Tarif Pajak
CREATE TABLE `tarif_pajak` (
  `id_tarif` INT NOT NULL AUTO_INCREMENT,
  `id_jenis_pajak` INT NOT NULL,
  `tarif` DECIMAL(8,2) NOT NULL,
  `tanggal_mulai` DATE DEFAULT NULL,
  `tanggal_akhir` DATE DEFAULT NULL,
  PRIMARY KEY (`id_tarif`),
  KEY `fk_tarif_jenis` (`id_jenis_pajak`),
  CONSTRAINT `fk_tarif_jenis` FOREIGN KEY (`id_jenis_pajak`) REFERENCES `jenis_pajak` (`id_jenis_pajak`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Tabel Objek Pajak
CREATE TABLE `objek_pajak` (
  `id_objek_pajak` INT NOT NULL AUTO_INCREMENT,
  `id_wajib_pajak` INT NOT NULL,
  `id_jenis_pajak` INT NOT NULL,
  `nama_objek` VARCHAR(100) DEFAULT NULL,
  `alamat_objek` TEXT DEFAULT NULL,
  `nilai_objek` DECIMAL(15,2) DEFAULT NULL,
  PRIMARY KEY (`id_objek_pajak`),
  KEY `fk_objek_wp` (`id_wajib_pajak`),
  KEY `fk_objek_jenis` (`id_jenis_pajak`),
  CONSTRAINT `fk_objek_wp` FOREIGN KEY (`id_wajib_pajak`) REFERENCES `wajib_pajak` (`id_wajib_pajak`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_objek_jenis` FOREIGN KEY (`id_jenis_pajak`) REFERENCES `jenis_pajak` (`id_jenis_pajak`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Tabel Masa Pajak
CREATE TABLE `masa_pajak` (
  `id_masa_pajak` INT NOT NULL AUTO_INCREMENT,
  `tahun_pajak` YEAR NOT NULL,
  `bulan_pajak` TINYINT DEFAULT NULL,
  `tanggal_mulai` DATE DEFAULT NULL,
  `tanggal_akhir` DATE DEFAULT NULL,
  `keterangan` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`id_masa_pajak`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Tabel SPT
CREATE TABLE `spt` (
  `id_spt` INT NOT NULL AUTO_INCREMENT,
  `id_wajib_pajak` INT NOT NULL,
  `id_jenis_pajak` INT NOT NULL,
  `id_masa_pajak` INT NOT NULL,
  `id_petugas` INT DEFAULT NULL,
  `tanggal_lapor` DATE DEFAULT NULL,
  `dasar_pengenaan` DECIMAL(15,2) DEFAULT NULL,
  `jumlah_pajak` DECIMAL(15,2) DEFAULT NULL,
  `status` ENUM('Draft','Diajukan','Diverifikasi','Ditolak') NOT NULL DEFAULT 'Draft',
  PRIMARY KEY (`id_spt`),
  KEY `fk_spt_wp` (`id_wajib_pajak`),
  KEY `fk_spt_jenis` (`id_jenis_pajak`),
  KEY `fk_spt_masa` (`id_masa_pajak`),
  KEY `fk_spt_petugas` (`id_petugas`),
  CONSTRAINT `fk_spt_wp` FOREIGN KEY (`id_wajib_pajak`) REFERENCES `wajib_pajak` (`id_wajib_pajak`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_spt_jenis` FOREIGN KEY (`id_jenis_pajak`) REFERENCES `jenis_pajak` (`id_jenis_pajak`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_spt_masa` FOREIGN KEY (`id_masa_pajak`) REFERENCES `masa_pajak` (`id_masa_pajak`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_spt_petugas` FOREIGN KEY (`id_petugas`) REFERENCES `petugas` (`id_petugas`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Tabel Tagihan
CREATE TABLE `tagihan` (
  `id_tagihan` INT NOT NULL AUTO_INCREMENT,
  `id_spt` INT NOT NULL,
  `nomor_tagihan` VARCHAR(50) DEFAULT NULL,
  `tanggal_tagihan` DATE DEFAULT NULL,
  `tanggal_jatuh_tempo` DATE DEFAULT NULL,
  `total_tagihan` DECIMAL(15,2) NOT NULL,
  `status` ENUM('Belum Lunas','Sebagian','Lunas') NOT NULL DEFAULT 'Belum Lunas',
  PRIMARY KEY (`id_tagihan`),
  UNIQUE KEY `uk_tagihan_spt` (`id_spt`),
  UNIQUE KEY `uk_nomor_tagihan` (`nomor_tagihan`),
  CONSTRAINT `fk_tagihan_spt` FOREIGN KEY (`id_spt`) REFERENCES `spt` (`id_spt`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Tabel Pembayaran
CREATE TABLE `pembayaran` (
  `id_pembayaran` INT NOT NULL AUTO_INCREMENT,
  `id_tagihan` INT NOT NULL,
  `tanggal_pembayaran` DATE DEFAULT NULL,
  `jumlah_bayar` DECIMAL(15,2) NOT NULL,
  `metode_pembayaran` VARCHAR(50) DEFAULT NULL,
  `nomor_referensi` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('Berhasil','Menunggu','Gagal') NOT NULL DEFAULT 'Menunggu',
  PRIMARY KEY (`id_pembayaran`),
  KEY `fk_bayar_tagihan` (`id_tagihan`),
  CONSTRAINT `fk_bayar_tagihan` FOREIGN KEY (`id_tagihan`) REFERENCES `tagihan` (`id_tagihan`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- DATA CONTOH (SAMPLE DATA)
-- ========================================================

-- Sample Wajib Pajak
INSERT INTO `wajib_pajak` (`id_wajib_pajak`, `npwp`, `nama`, `nik`, `alamat`, `email`, `no_telepon`, `tanggal_daftar`, `status`) VALUES
(1, '01.234.567.8-012.000', 'Budi Santoso, S.Kom', '3171012304850001', 'Jl. Sudirman No. 45, Jakarta Selatan', 'budi.santoso@email.com', '081234567890', '2024-01-15', 'Aktif'),
(2, '02.345.678.9-023.000', 'Siti Rahmawati, S.E.', '3273024508900003', 'Jl. Merdeka No. 12, Bandung', 'siti.rahmawati@email.com', '081398765432', '2024-02-10', 'Aktif'),
(3, '03.456.789.0-034.000', 'PT Maju Bersama Nusantara', '3174091206780002', 'Kawasan Industri Cikarang Blok B-12, Bekasi', 'finance@majubersama.co.id', '02189912345', '2023-05-20', 'Aktif'),
(4, '04.567.890.1-045.000', 'Ahmad Farhan', '3578011211920004', 'Jl. Pemuda No. 88, Surabaya', 'ahmad.farhan@gmail.com', '085712349876', '2024-03-01', 'Aktif'),
(5, '05.678.901.2-056.000', 'Dewi Lestari, S.Pd', '3374026509890005', 'Jl. Diponegoro No. 19, Semarang', 'dewi.lestari@yahoo.com', '082133445566', '2024-04-12', 'Nonaktif');

-- Sample Petugas (Password default: 'admin123' di-hash dengan password_hash() BCRYPT)
INSERT INTO `petugas` (`id_petugas`, `nama_petugas`, `username`, `password`, `jabatan`, `email`) VALUES
(1, 'Drs. Hendra Gunawan, M.Si', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Fungsional Pemeriksa Pajak', 'hendra.gunawan@kemenkeu.go.id'),
(2, 'Rina Wahyuni, A.Md', 'petugas1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Petugas Pelayanan Terpadu', 'rina.wahyuni@kemenkeu.go.id'),
(3, 'Fajar Pratama, S.Tr.Ak', 'petugas2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Account Representative', 'fajar.pratama@kemenkeu.go.id');

-- Sample Jenis Pajak
INSERT INTO `jenis_pajak` (`id_jenis_pajak`, `kode_pajak`, `nama_pajak`, `keterangan`) VALUES
(1, 'PPH-21', 'PPh Pasal 21', 'Pajak atas penghasilan berupa gaji, upah, honorarium, tunjangan pegawai dan non-pegawai'),
(2, 'PPH-23', 'PPh Pasal 23', 'Pajak atas modal, penyerahan jasa, dividen, bunga, royalti, hadiah atau penghargaan'),
(3, 'PPH-FINAL', 'PPh Final UMKM (PP 55/2022)', 'Pajak penghasilan final dengan peredaran bruto tertentu untuk pelaku usaha mikro & kecil'),
(4, 'PPN', 'Pajak Pertambahan Nilai (PPN)', 'Pajak konsumsi atas barang dan jasa kena pajak di dalam daerah pabean'),
(5, 'PBB-P2', 'PBB Perdesaan dan Perkotaan', 'Pajak atas bumi dan bangunan yang dimiliki, dikuasai, dan/atau dimanfaatkan');

-- Sample Tarif Pajak
INSERT INTO `tarif_pajak` (`id_tarif`, `id_jenis_pajak`, `tarif`, `tanggal_mulai`, `tanggal_akhir`) VALUES
(1, 1, 5.00, '2024-01-01', '2026-12-31'),
(2, 1, 15.00, '2024-01-01', '2026-12-31'),
(3, 2, 2.00, '2024-01-01', '2026-12-31'),
(4, 3, 0.50, '2023-01-01', '2027-12-31'),
(5, 4, 11.00, '2022-04-01', '2026-12-31'),
(6, 5, 0.20, '2023-01-01', '2026-12-31');

-- Sample Objek Pajak
INSERT INTO `objek_pajak` (`id_objek_pajak`, `id_wajib_pajak`, `id_jenis_pajak`, `nama_objek`, `alamat_objek`, `nilai_objek`) VALUES
(1, 1, 1, 'Gaji Pokok & Tunjangan Profesional', 'PT Teknologi Mandiri, Jakarta', 180000000.00),
(2, 2, 3, 'Usaha Butik Busana Muslimah', 'Jl. Cihampelas No. 102, Bandung', 450000000.00),
(3, 3, 4, 'Penyerahan Jasa Perangkat Lunak & Server', 'Kawasan Industri Cikarang Blok B-12', 1250000000.00),
(4, 3, 5, 'Tanah & Bangunan Kantor Pusat', 'Jl. M.H. Thamrin No. 8, Jakarta Pusat', 8500000000.00),
(5, 4, 1, 'Penghasilan Freelance Software Engineer', 'Jl. Pemuda No. 88, Surabaya', 120000000.00);

-- Sample Masa Pajak
INSERT INTO `masa_pajak` (`id_masa_pajak`, `tahun_pajak`, `bulan_pajak`, `tanggal_mulai`, `tanggal_akhir`, `keterangan`) VALUES
(1, 2026, 1, '2026-01-01', '2026-01-31', 'Masa Pajak Januari 2026'),
(2, 2026, 2, '2026-02-01', '2026-02-28', 'Masa Pajak Februari 2026'),
(3, 2026, 3, '2026-03-01', '2026-03-31', 'Masa Pajak Maret 2026'),
(4, 2026, 4, '2026-04-01', '2026-04-30', 'Masa Pajak April 2026'),
(5, 2025, NULL, '2025-01-01', '2025-12-31', 'SPT Tahunan Orang Pribadi & Badan 2025');

-- Sample SPT
INSERT INTO `spt` (`id_spt`, `id_wajib_pajak`, `id_jenis_pajak`, `id_masa_pajak`, `id_petugas`, `tanggal_lapor`, `dasar_pengenaan`, `jumlah_pajak`, `status`) VALUES
(1, 1, 1, 1, 1, '2026-02-08', 15000000.00, 750000.00, 'Diverifikasi'),
(2, 2, 3, 1, 2, '2026-02-10', 37500000.00, 187500.00, 'Diverifikasi'),
(3, 3, 4, 1, 1, '2026-02-14', 120000000.00, 13200000.00, 'Diajukan'),
(4, 4, 1, 2, 2, '2026-03-05', 10000000.00, 500000.00, 'Draft'),
(5, 5, 1, 1, 3, '2026-02-05', 8000000.00, 400000.00, 'Ditolak');

-- Sample Tagihan
INSERT INTO `tagihan` (`id_tagihan`, `id_spt`, `nomor_tagihan`, `tanggal_tagihan`, `tanggal_jatuh_tempo`, `total_tagihan`, `status`) VALUES
(1, 1, 'BILL-202602-0001', '2026-02-08', '2026-02-28', 750000.00, 'Lunas'),
(2, 2, 'BILL-202602-0002', '2026-02-10', '2026-02-28', 187500.00, 'Lunas'),
(3, 3, 'BILL-202602-0003', '2026-02-14', '2026-03-15', 13200000.00, 'Belum Lunas');

-- Sample Pembayaran
INSERT INTO `pembayaran` (`id_pembayaran`, `id_tagihan`, `tanggal_pembayaran`, `jumlah_bayar`, `metode_pembayaran`, `nomor_referensi`, `status`) VALUES
(1, 1, '2026-02-09', 750000.00, 'Bank Mandiri (VA)', 'TRX-MND-894123849', 'Berhasil'),
(2, 2, '2026-02-11', 187500.00, 'Bank BCA (KlikBCA)', 'TRX-BCA-771928341', 'Berhasil');

