-- Migrasi satu kali dari skema lama (satu alergi per pasien) ke skema ternormalisasi.
-- Jalankan hanya pada database versi lama, lalu import ulang 01_functions_procedures_triggers.sql.

SET FOREIGN_KEY_CHECKS = 0;

RENAME TABLE Riwayat_Alergi TO Alergi;
ALTER TABLE Alergi
    CHANGE id_riwayat_alergi id_alergi CHAR(5) NOT NULL,
    ADD kategori_alergi VARCHAR(30) NOT NULL DEFAULT 'Lainnya' AFTER nama_alergi,
    MODIFY keterangan_alergi VARCHAR(150) NULL,
    ADD CONSTRAINT uq_alergi_nama UNIQUE (nama_alergi),
    ADD CONSTRAINT chk_kategori_alergi
        CHECK (kategori_alergi IN ('Obat', 'Makanan', 'Lingkungan', 'Lainnya'));

CREATE TABLE Riwayat_Alergi (
    id_riwayat_alergi BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Pasien_id_pasien CHAR(5) NOT NULL,
    Alergi_id_alergi CHAR(5) NOT NULL,
    reaksi_alergi VARCHAR(150) NOT NULL,
    tingkat_keparahan VARCHAR(10) NOT NULL,
    tanggal_diketahui DATE NOT NULL,
    status_alergi VARCHAR(12) NOT NULL DEFAULT 'Aktif',
    catatan VARCHAR(250) NULL,
    CONSTRAINT fk_riwayat_alergi_pasien FOREIGN KEY (Pasien_id_pasien)
        REFERENCES Pasien(id_pasien)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_riwayat_alergi_master FOREIGN KEY (Alergi_id_alergi)
        REFERENCES Alergi(id_alergi)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT uq_riwayat_alergi_pasien UNIQUE (Pasien_id_pasien, Alergi_id_alergi),
    CONSTRAINT chk_keparahan_alergi CHECK (tingkat_keparahan IN ('Ringan', 'Sedang', 'Berat')),
    CONSTRAINT chk_status_alergi CHECK (status_alergi IN ('Aktif', 'Tidak Aktif'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO Riwayat_Alergi (
    Pasien_id_pasien, Alergi_id_alergi, reaksi_alergi,
    tingkat_keparahan, tanggal_diketahui, status_alergi, catatan
)
SELECT
    p.id_pasien,
    p.Riwayat_Alergi_id_riwayat_alergi,
    COALESCE(a.keterangan_alergi, 'Reaksi belum dicatat'),
    'Sedang',
    CURDATE(),
    'Aktif',
    'Data hasil migrasi; perlu dikonfirmasi ulang dengan pasien'
FROM Pasien p
JOIN Alergi a ON a.id_alergi = p.Riwayat_Alergi_id_riwayat_alergi
WHERE LOWER(a.nama_alergi) NOT LIKE 'tidak ada%';

ALTER TABLE Pasien
    DROP FOREIGN KEY fk_pasien_alergi,
    DROP COLUMN Riwayat_Alergi_id_riwayat_alergi;

DELETE FROM Alergi WHERE LOWER(nama_alergi) LIKE 'tidak ada%';

SET FOREIGN_KEY_CHECKS = 1;

CREATE INDEX idx_riwayat_alergi_pasien ON Riwayat_Alergi(Pasien_id_pasien);
CREATE INDEX idx_riwayat_alergi_master ON Riwayat_Alergi(Alergi_id_alergi);
CREATE INDEX idx_riwayat_alergi_status ON Riwayat_Alergi(status_alergi);

-- Bersihkan objek lama agar file 01 dapat membangun ulang seluruh routine
-- dan trigger dengan definisi terbaru tanpa bentrok nama.
DROP TRIGGER IF EXISTS trg_generate_id_rekam_medis;
DROP TRIGGER IF EXISTS trg_kurang_stok_obat;
DROP TRIGGER IF EXISTS trg_kamar_terisi;
DROP TRIGGER IF EXISTS trg_kamar_kosong;
DROP TRIGGER IF EXISTS trg_validasi_stok_obat;
DROP TRIGGER IF EXISTS trg_set_total_pembayaran;
DROP TRIGGER IF EXISTS trg_update_pembayaran_upd;
DROP TRIGGER IF EXISTS trg_audit_rekam_medis;

DROP PROCEDURE IF EXISTS registrasi_pasien_baru;
DROP PROCEDURE IF EXISTS perbarui_pasien_dan_alergi;
DROP PROCEDURE IF EXISTS buat_rekam_medis;
DROP PROCEDURE IF EXISTS proses_pembayaran;
DROP PROCEDURE IF EXISTS tambah_jadwal_jaga;
DROP PROCEDURE IF EXISTS proses_rawat_inap;

DROP FUNCTION IF EXISTS hitung_umur_pasien;
DROP FUNCTION IF EXISTS hitung_total_biaya;
DROP FUNCTION IF EXISTS cek_ketersediaan_kamar;
DROP FUNCTION IF EXISTS hitung_total_obat;
DROP FUNCTION IF EXISTS riwayat_alergi_pasien;
