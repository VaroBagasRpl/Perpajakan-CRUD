# SISTEM INFORMASI PERPAJAKAN (SIP PERPAJAKAN)
### Website Landing Page & Aplikasi CRUD Basis Data Relasional MySQL

Sistem Informasi Perpajakan terintegrasi yang dibangun berdasarkan spesifikasi dokumen struktur database perpajakan (9 Tabel Relasi). Dilengkapi dengan landing page modern bertema perpajakan resmi, dashboard analitik, dan modul CRUD lengkap.

---

## 📂 Lokasi Folder Proyek

Folder proyek berada di dalam direktori Laragon Anda:
```
C:\laragon\www\Perpajakan
```

---

## 🚀 Cara Menjalankan di Laragon

1. **Jalankan Laragon** pada komputer Anda.
2. Klik tombol **"Start All"** di jendela Laragon (untuk mengaktifkan Apache dan MySQL).
3. Buka browser (Chrome, Edge, Firefox) dan akses salah satu alamat berikut:
   - `http://localhost/Perpajakan`
   - atau `http://perpajakan.test` (jika fitur Pretty URL Laragon aktif)

---

## 🗄️ Inisialisasi Database MySQL (2 Cara Mudah)

### Cara 1: Inisialisasi Otomatis 1-Klik (Sangat Direkomendasikan)
1. Setelah Laragon berjalan (MySQL aktif), buka alamat:
   ```
   http://localhost/Perpajakan/setup.php
   ```
2. Klik tombol hijau **"Inisialisasi Database Sekarang"**.
3. Database `perpajakan`, 9 tabel, beserta data sampel realistis akan langsung dibuat secara otomatis!

### Cara 2: Import Manual Lewat phpMyAdmin
1. Buka browser ke: `http://localhost/phpmyadmin`
2. Klik menu **"Import"** di bagian atas.
3. Klik **"Choose File"** lalu pilih file:
   ```
   C:\laragon\www\Perpajakan\database.sql
   ```
4. Gulir ke bawah lalu klik tombol **"Kirim" / "Go" / "Import"**.
5. Buka kembali `http://localhost/Perpajakan` di browser Anda.

---

## 📋 Struktur 9 Tabel Database Sesuai Dokumen PDF

| No | Nama Tabel | Deskripsi | Field Kunci |
|---|---|---|---|
| 1 | `wajib_pajak` | Identitas Wajib Pajak Orang Pribadi/Badan | `id_wajib_pajak` (PK), `npwp` (UK), `nama`, `nik`, `alamat`, `email`, `no_telepon`, `tanggal_daftar`, `status` |
| 2 | `petugas` | Data administrator dan fungsional pemeriksa pajak | `id_petugas` (PK), `nama_petugas`, `username` (UK), `password` (Hash BCRYPT), `jabatan`, `email` |
| 3 | `jenis_pajak` | Kategori pajak (PPh 21, PPh 23, PPN, PBB-P2, UMKM) | `id_jenis_pajak` (PK), `kode_pajak` (UK), `nama_pajak`, `keterangan` |
| 4 | `tarif_pajak` | Besaran persentase tarif & masa berlaku tanggal | `id_tarif` (PK), `id_jenis_pajak` (FK), `tarif`, `tanggal_mulai`, `tanggal_akhir` |
| 5 | `objek_pajak` | Objek pajak terkait aset, omzet, dan penghasilan | `id_objek_pajak` (PK), `id_wajib_pajak` (FK), `id_jenis_pajak` (FK), `nama_objek`, `alamat_objek`, `nilai_objek` |
| 6 | `masa_pajak` | Jangka waktu periode pelaporan pajak | `id_masa_pajak` (PK), `tahun_pajak`, `bulan_pajak`, `tanggal_mulai`, `tanggal_akhir`, `keterangan` |
| 7 | `spt` | Surat Pemberitahuan pajak & verifikasi | `id_spt` (PK), `id_wajib_pajak` (FK), `id_jenis_pajak` (FK), `id_masa_pajak` (FK), `id_petugas` (FK), `tanggal_lapor`, `dasar_pengenaan`, `jumlah_pajak`, `status` |
| 8 | `tagihan` | Kewajiban billing pembayaran pajak | `id_tagihan` (PK), `id_spt` (FK, UK), `nomor_tagihan` (UK), `tanggal_tagihan`, `tanggal_jatuh_tempo`, `total_tagihan`, `status` |
| 9 | `pembayaran` | Transaksi setoran kas dan nomor referensi bank | `id_pembayaran` (PK), `id_tagihan` (FK), `tanggal_pembayaran`, `jumlah_bayar`, `metode_pembayaran`, `nomor_referensi`, `status` |

---

## ✨ Fitur-Fitur Sistem

1. **Halaman Landing Page Modern**:
   - Tema warna instansi perpajakan resmi: *Deep Forest Green & Gold (#1b4332 / #d4af37)*.
   - Ringkasan KPI Real-Time (Wajib Pajak aktif, total SPT, tagihan lunas, total penerimaan negara).
   - Penjelasan layanan perpajakan terpadu.
   - **Kalkulator Simulasi Hitung Pajak Interaktif** (PPh 21, PPh UMKM 0,5%, PPN 11%, PBB).
   - Tampilan visual rincian 9 struktur tabel database dari dokumen PDF.

2. **Dashboard CRUD Terintegrasi**:
   - Statistik kartu ringkasan real-time.
   - Pintasan cepat modul & transaksi terbaru.
   - Notifikasi status koneksi basis data MySQL.

3. **Modul CRUD Penuh untuk Semua 9 Tabel**:
   - Tambah, Ubah, Hapus (dengan konfirmasi pencegahan), dan Pencarian langsung (*live search*).
   - Relasi Foreign Key otomatis dalam bentuk dropdown pilihan nama dan kode (bukan ID mentah).
   - Format mata uang Rupiah otomatis (`Rp`).
   - Keamanan hashing password petugas menggunakan `password_hash()` BCRYPT.

4. **Fitur Cetak Dokumen Resmi**:
   - **Cetak Bukti Penerimaan Elektronik (BPE / SPT)** dengan format kop resmi.
   - **Cetak Surat Setoran Pajak (SSP / Billing Tagihan)** lengkap dengan histori pembayaran dan sisa tagihan.

---

## 🔐 Akun Petugas Default (Data Sampel)

| Username | Password | Jabatan |
|---|---|---|
| `admin` | `admin123` | Fungsional Pemeriksa Pajak |
| `petugas1` | `admin123` | Petugas Pelayanan Terpadu |
| `petugas2` | `admin123` | Account Representative |

---

*Dikembangkan untuk Laragon Windows &bull; PHP 8.x & MySQL 5.7/8.x*

