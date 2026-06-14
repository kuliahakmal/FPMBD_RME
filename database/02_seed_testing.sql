SET NAMES utf8mb4 COLLATE utf8mb4_0900_ai_ci;

-- 1. Tambah data master pendukung (Alergi, Poliklinik, Dokter, Perawat, Kamar, Obat, Asuransi, Jenis Pembayaran, Shift)
INSERT INTO Alergi (id_alergi, nama_alergi, kategori_alergi, keterangan_alergi) VALUES
('A0001', 'Parasetamol', 'Obat', 'Alergen pada obat yang mengandung parasetamol'),
('A0002', 'Kacang Tanah', 'Makanan', 'Alergen makanan berbahan kacang tanah'),
('A0003', 'Amoxicillin', 'Obat', 'Alergi antibiotik golongan penisilin'),
('A0004', 'Seafood / Udang', 'Makanan', 'Menyebabkan gatal-gatal dan kemerahan pada kulit'),
('A0005', 'Debu dan Tungau', 'Lingkungan', 'Memicu bersin dan asma ringan'),
('A0006', 'Udara Dingin', 'Lingkungan', 'Menyebabkan biduran (urtikaria) saat suhu turun');

INSERT INTO Poliklinik (id_poliklinik, nama_poliklinik, lokasi_poliklinik) VALUES
('P0001', 'Poli Penyakit Dalam', 'Gedung A Lantai 2'),
('P0002', 'Poli Anak', 'Gedung A Lantai 1'),
('P0003', 'Poli Kandungan & Kebidanan', 'Gedung B Lantai 2'),
('P0004', 'Poli Gigi & Mulut', 'Gedung C Lantai 1'),
('P0005', 'Poli Mata', 'Gedung C Lantai 2'),
('P0006', 'Poli Saraf', 'Gedung A Lantai 3');

INSERT INTO Dokter (id_dokter, nama_dokter, nomor_telepon_dokter, spesialisasi_dokter) VALUES
('D0001', 'Dr. Budi Utomo', '08123456789', 'Spesialis Penyakit Dalam').
('D0002', 'Dr. Andi Saputra, Sp.A', '08122334455', 'Spesialis Anak'),
('D0003', 'Dr. Maria Lestari, Sp.OG', '08133445566', 'Spesialis Kandungan (Obgyn)'),
('D0004', 'Drg. Hendra Wijaya', '08144556677', 'Dokter Gigi'),
('D0005', 'Dr. Ratna Sari, Sp.M', '08155667788', 'Spesialis Mata'),
('D0006', 'Dr. Lukman Hakim, Sp.N', '08166778899', 'Spesialis Saraf');

INSERT INTO Perawat (id_perawat, nama_perawat, nomor_telepon_perawat) VALUES
('N0001', 'Suster Siti Aminah', '08987654321'),
('N0002', 'Br. Ahmad Fauzi', '08911223344'),
('N0003', 'Suster Dewi Sartika', '08922334455'),
('N0004', 'Suster Rina Maharani', '08933445566'),
('N0005', 'Br. Doni Setiawan', '08944556677');

INSERT INTO Kamar (id_kamar, nomor_kamar, tipe_kamar, status_kamar) VALUES
('K0001', 101, 'VIP', 'Kosong'),
('K0002', 102, 'VIP', 'Kosong'),
('K0003', 201, 'Kelas 1', 'Kosong'),
('K0004', 202, 'Kelas 1', 'Kosong'),
('K0005', 301, 'Kelas 2', 'Kosong'),
('K0006', 302, 'Kelas 3', 'Kosong');

INSERT INTO Obat (id_obat, nama_obat, stok_obat, harga_obat) VALUES
('O0001', 'Parasetamol 500mg', 100, 5000.00),
('O0002', 'Amoxicillin 500mg', 200, 15000.00),
('O0003', 'Ibuprofen 400mg', 150, 8000.00),
('O0004', 'Omeprazole 20mg', 120, 12000.00),
('O0005', 'Cetirizine 10mg', 80, 6000.00),
('O0006', 'Vitamin C 500mg', 300, 3000.00),
('O0007', 'Asam Mefenamat 500mg', 250, 7500.00);

INSERT INTO Asuransi (nomor_asuransi, nama_lembaga_asuransi, jenis_asuransi) VALUES
('ASR0000000001', 'BPJS Kesehatan', 'JKN'),
('ASR0000000002', 'Mandiri Inhealth', 'Asuransi Swasta / Karyawan'),
('ASR0000000003', 'Prudential', 'Asuransi Kesehatan Swasta'),
('ASR0000000004', 'Allianz', 'Asuransi Kesehatan Swasta');

INSERT INTO Jenis_Pembayaran (id_jenis_pembayaran, nama_jenis_pembayaran) VALUES
('JP001', 'Non-Tunai (Asuransi)'),
('JP002', 'Tunai / Cash'),
('JP003', 'Kartu Debit'),
('JP004', 'Kartu Kredit'),
('JP005', 'QRIS / E-Wallet');

INSERT INTO Shift (id_shift, Jenis_Shift, Jam_Masuk, Jam_Selesai) VALUES
(1, 'Pagi', '07:00:00', '14:00:00'),
(2, 'Siang', '14:00:00', '21:00:00'),
(3, 'Malam', '21:00:00', '07:00:00');


-- 2. Uji Coba Stored Procedure Registrasi Pasien Baru
CALL registrasi_pasien_baru(
    'PS001', 'Andi Pratama', '081122334455', 'Jl. Merdeka No. 45', '1995-08-17', 'L',
    '[{"id_alergi":"A0001","reaksi":"Gatal-gatal pada kulit","keparahan":"Sedang","tanggal_diketahui":"2024-02-10","status":"Aktif","catatan":"Dikonfirmasi setelah konsumsi obat"}]',
    'R0001', 'P0001', 'Rawat Inap'
);

-- Contoh registrasi Rawat Jalan tanpa data Rawat_Inap.
CALL registrasi_pasien_baru(
    'PS002', 'Sari Lestari', '081133445566', 'Jl. Melati No. 12', '1998-04-21', 'P',
    '[]',
    'R0002', 'P0001', 'Rawat Jalan'
);

-- Cek apakah pasien & registrasi masuk
SELECT * FROM Pasien;
SELECT * FROM Riwayat_Alergi;
SELECT * FROM Registrasi;


-- 3. Uji Coba Function Menghitung Umur Pasien & Menampilkan Riwayat Alergi
SELECT hitung_umur_pasien('PS001') AS Umur_Pasien, riwayat_alergi_pasien('PS001') AS Alergi_Pasien;


-- 4. Uji Coba Stored Procedure Penjadwalan Jaga Dokter & Perawat (dan validasi bentrok)
CALL tambah_jadwal_jaga('J0001', '2026-06-08', 'N0001', 'D0001', 1);

-- Percobaan jadwal bentrok (Akan menghasilkan error SIGNAL SQLSTATE)
-- CALL tambah_jadwal_jaga('J0002', '2026-06-08', 'N0001', 'D0001', 1);


-- 5. Uji Coba Stored Procedure Rawat Inap Pasien (dan validasi ketersediaan kamar)
CALL proses_rawat_inap('RI001', '2026-06-07 10:00:00', 'K0001', 'R0001');

-- Cek status kamar sekarang (Seharusnya terisi oleh trigger/SP)
SELECT cek_ketersediaan_kamar('K0001') AS Status_Kamar;

-- Percobaan rawat inap di kamar yang sama (Akan menghasilkan error)
-- CALL proses_rawat_inap('RI002', '2026-06-07 11:00:00', 'K0001', 'R0001');


-- 6. Uji Coba Stored Procedure Pembuatan Rekam Medis (Dan Trigger Generate ID Rekam Medis)
-- ID Rekam Medis diset 'RM000' agar di-generate otomatis oleh trigger menjadi 'RM001'
CALL buat_rekam_medis(
    'RM000', 'Demam tinggi dan pusing kepala', 'R0001', 'D0001', 'N0001', 'RI001',
    'DG001', 'Demam Dengue', 'Gejala awal demam berdarah',
    'T0001', 'Pemeriksaan Darah Lengkap', 150000.00, 'Trombosit menurun',
    TRUE, 'RS001', 'DR001', 2, '3x1 tablet'
);

SELECT * FROM Rekam_Medis;
SELECT * FROM Diagnosa;
SELECT * FROM Tindakan_Medis;
SELECT * FROM Detail_Resep;
SELECT * FROM Resep;

-- Rawat Jalan tetap dapat memiliki rekam medis, diagnosa, tindakan, dan resep.
CALL buat_rekam_medis(
    'RM000', 'Batuk ringan selama tiga hari', 'R0002', 'D0001', 'N0001', NULL,
    'DG002', 'Infeksi Saluran Pernapasan Atas', 'Gejala ringan tanpa rawat inap',
    'T0002', 'Pemeriksaan Umum', 75000.00, 'Kondisi stabil',
    TRUE, 'RS002', 'DR002', 3, '2x1 tablet'
);


-- 7. Uji Coba Trigger Mengurangi Stok Obat & Validasi Stok Obat
SELECT stok_obat FROM Obat WHERE id_obat = 'O0001'; -- Stok awal 100

-- Kaitkan obat dengan resep (Trigger validation & reduction berjalan)
INSERT INTO Obat_Resep (Obat_id_obat, Resep_id_resep) VALUES ('O0001', 'RS001');
INSERT INTO Obat_Resep (Obat_id_obat, Resep_id_resep) VALUES ('O0001', 'RS002');

SELECT stok_obat FROM Obat WHERE id_obat = 'O0001'; -- Seharusnya berkurang 5 menjadi 95


-- 8. Uji Coba Function hitung_total_obat
SELECT hitung_total_obat('RS001') AS Total_Obat_Resep;


-- 9. Uji Coba Stored Procedure Pembayaran & Trigger Sinkronisasi Total
-- Buat detail pembayaran terlebih dahulu
INSERT INTO Detail_Pembayaran (id_detail_pembayaran, keterangan_biaya, sub_total, Tindakan_Medis_id_tindakan_medis, Rawat_Inap_id_rawat_inap, Resep_id_resep) VALUES
('DP001', 'Biaya Tindakan, Rawat Inap & Obat', 250000.00, 'T0001', 'RI001', 'RS001'),
('DP002', 'Biaya Rawat Jalan & Obat', 100000.00, 'T0002', NULL, 'RS002');

-- Proses Pembayaran
CALL proses_pembayaran('PY001', 'R0001', 'JP001', 'ASR0000000001', 'DP001');
CALL proses_pembayaran('PY002', 'R0002', 'JP001', NULL, 'DP002');

-- Cek pembayaran (total_biaya seharusnya otomatis sinkron dengan sub_total DP001)
SELECT * FROM Pembayaran;

-- Update sub_total pada Detail_Pembayaran (Seharusnya mengupdate total_biaya di Pembayaran lewat trigger)
UPDATE Detail_Pembayaran SET sub_total = 275000.00 WHERE id_detail_pembayaran = 'DP001';
SELECT * FROM Pembayaran; -- Seharusnya total_biaya berubah menjadi 275000.00


-- 10. Uji Coba Trigger Audit Rekam Medis
-- Update keluhan rekam medis
UPDATE Rekam_Medis SET keluhan_pasien = 'Demam tinggi disertai mual' WHERE id_rekam_medis = 'RM001';

-- Cek Log Audit
SELECT * FROM Log_Audit_Rekam_Medis;


-- 11. Selesai Rawat Inap & Uji Coba Trigger Mengubah Status Kamar Menjadi Kosong
UPDATE Rawat_Inap SET tanggal_keluar = '2026-06-12 12:00:00' WHERE id_rawat_inap = 'RI001';

-- Cek status kamar sekarang (Seharusnya kembali 'Kosong' karena trigger)
SELECT cek_ketersediaan_kamar('K0001') AS Status_Kamar;

