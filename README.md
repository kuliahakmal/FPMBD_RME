# RME Web PHP

Starter web untuk Final Project MBD: Electronic Medical Records System.

Stack yang dipakai:

- PHP native + PDO
- MariaDB atau MySQL
- CSS lokal tanpa framework eksternal
- Router sederhana lewat `public/index.php`

Alasan stack ini dipilih:

- Cocok untuk tugas database karena stored procedure, function, trigger, dan index tetap terlihat jelas.
- Tidak perlu setup berat seperti Laravel atau React.
- Bisa langsung dijalankan dengan `php -S`, cocok untuk demo praktikum.
- Struktur tetap rapi karena dipisah menjadi config, pages, assets, dan database.

## Cara menjalankan

1. Buat database kosong di MariaDB/MySQL.

```sql
CREATE DATABASE rme_db;
```

2. Import SQL secara berurutan.

```bash
mysql -u root -p rme_db < database/00_schema.sql
mysql -u root -p rme_db < database/01b_indexes.sql
mysql -u root -p rme_db < database/01_functions_procedures_triggers.sql
mysql -u root -p rme_db < database/02_seed_testing.sql
```

3. Salin file environment.

```bash
cp .env.example .env
```

4. Edit `.env` sesuai user database kamu.

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=rme_db
DB_USER=root
DB_PASS=
```

5. Jalankan server lokal.

```bash
php -S localhost:8000 -t public
```

6. Buka browser.

```text
http://localhost:8000
```

## Fitur utama

- Dashboard ringkasan data rumah sakit.
- CRUD pasien sekaligus registrasi, jenis layanan, dan banyak riwayat alergi lewat stored procedure `registrasi_pasien_baru` dan `perbarui_pasien_dan_alergi`.
- Pembuatan rekam medis lewat stored procedure `buat_rekam_medis`.
- Proses rawat inap lewat stored procedure `proses_rawat_inap`.
- Pembayaran lewat stored procedure `proses_pembayaran`.
- Master data dokter, perawat, poliklinik, kamar, obat, jenis alergi, asuransi, jenis pembayaran, dan shift.
- Jadwal jaga dengan validasi bentrok lewat stored procedure `tambah_jadwal_jaga`.
- Log audit perubahan rekam medis dari trigger `trg_audit_rekam_medis`.

## Catatan penting

File `00_schema.sql` dibuat agar web ini bisa berdiri sendiri. Kalau database kamu sudah punya DDL final dari Data Modeler, boleh pakai DDL milikmu, lalu tetap import `01_functions_procedures_triggers.sql`, `01b_indexes.sql`, dan `02_seed_testing.sql`.

Relasi pasien dan alergi sudah dinormalisasi: `Alergi` menyimpan master alergen, sedangkan `Riwayat_Alergi` menyimpan relasi banyak-ke-banyak beserta reaksi, keparahan, tanggal diketahui, status, dan catatan. Untuk menaikkan database versi lama tanpa menghapus data, jalankan `database/03_migrate_normalize_patient_allergy.sql`, lalu import ulang `database/01_functions_procedures_triggers.sql`.

## Jenis layanan per registrasi

`Registrasi.jenis_layanan` menyimpan jenis layanan setiap kunjungan dengan nilai `Rawat Jalan` atau `Rawat Inap`. Nilai default-nya adalah `Rawat Jalan`; jenis layanan tidak disimpan pada `Pasien` karena satu pasien dapat memiliki jenis layanan yang berbeda pada setiap kunjungan.

- Rawat jalan tidak membutuhkan tabel tambahan. Registrasi rawat jalan tetap dapat memiliki `Rekam_Medis`, `Diagnosa`, `Tindakan_Medis`, `Resep`, dan `Pembayaran`.
- `Rawat_Inap` hanya dibuat ketika pasien benar-benar menjalani rawat inap. `proses_rawat_inap` dan trigger insert `Rawat_Inap` akan menyinkronkan `Registrasi.jenis_layanan` menjadi `Rawat Inap`.
- Registrasi boleh ditandai `Rawat Inap` sebelum data `Rawat_Inap` dibuat karena keputusan dan penempatan kamar dapat dilakukan setelah pemeriksaan.
- `Rekam_Medis.Rawat_Inap_id_rawat_inap` tetap opsional dan bernilai `NULL` untuk rawat jalan.

## Migration jenis layanan

Backup database lama terlebih dahulu:

```bash
mariadb-dump -h HOST -P PORT -u USER -p DATABASE > backup_sebelum_jenis_layanan.sql
```

Lalu jalankan migration dan pasang ulang routine/trigger terbaru:

```bash
mariadb -h HOST -P PORT -u USER -p DATABASE < database/04_migrate_add_service_type.sql
mariadb -h HOST -P PORT -u USER -p DATABASE < database/01_functions_procedures_triggers.sql
```

Migration mengisi data lama sebagai `Rawat Inap` bila registrasinya memiliki relasi `Rawat_Inap`; registrasi lainnya menjadi `Rawat Jalan`. Migration tidak menghapus data existing dan aman dijalankan ulang.

## Verifikasi jenis layanan dan active database

```sql
SELECT jenis_layanan, COUNT(*) AS jumlah
FROM Registrasi
GROUP BY jenis_layanan;

SELECT r.id_registrasi, r.jenis_layanan, ri.id_rawat_inap,
       rm.id_rekam_medis, rm.Rawat_Inap_id_rawat_inap
FROM Registrasi r
LEFT JOIN Rawat_Inap ri ON ri.Registrasi_id_registrasi = r.id_registrasi
LEFT JOIN Rekam_Medis rm ON rm.Registrasi_id_registrasi = r.id_registrasi
ORDER BY r.id_registrasi;

SELECT ri.id_rawat_inap, ri.tanggal_keluar, k.status_kamar
FROM Rawat_Inap ri
JOIN Kamar k ON k.id_kamar = ri.Kamar_id_kamar;

SELECT COUNT(*) AS jumlah_audit FROM Log_Audit_Rekam_Medis;
SELECT id_obat, stok_obat FROM Obat;

SELECT py.id_pembayaran, py.total_biaya, dp.sub_total
FROM Pembayaran py
JOIN Detail_Pembayaran dp
  ON dp.id_detail_pembayaran = py.Detail_Pembayaran_id_detail_pembayaran;
```
