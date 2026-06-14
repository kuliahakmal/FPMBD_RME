SET NAMES utf8mb4 COLLATE utf8mb4_0900_ai_ci;
SET FOREIGN_KEY_CHECKS = 0;

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

DROP TABLE IF EXISTS Log_Audit_Rekam_Medis;
DROP TABLE IF EXISTS Pembayaran;
DROP TABLE IF EXISTS Detail_Pembayaran;
DROP TABLE IF EXISTS Obat_Resep;
DROP TABLE IF EXISTS Resep;
DROP TABLE IF EXISTS Detail_Resep;
DROP TABLE IF EXISTS Tindakan_Medis;
DROP TABLE IF EXISTS Diagnosa;
DROP TABLE IF EXISTS Rekam_Medis;
DROP TABLE IF EXISTS Rawat_Inap;
DROP TABLE IF EXISTS Jadwal_Jaga;
DROP TABLE IF EXISTS Registrasi;
DROP TABLE IF EXISTS Riwayat_Alergi;
DROP TABLE IF EXISTS Pasien;
DROP TABLE IF EXISTS Shift;
DROP TABLE IF EXISTS Kamar;
DROP TABLE IF EXISTS Obat;
DROP TABLE IF EXISTS Asuransi;
DROP TABLE IF EXISTS Jenis_Pembayaran;
DROP TABLE IF EXISTS Dokter;
DROP TABLE IF EXISTS Perawat;
DROP TABLE IF EXISTS Poliklinik;
DROP TABLE IF EXISTS Alergi;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE Alergi (
    id_alergi CHAR(5) PRIMARY KEY,
    nama_alergi VARCHAR(100) NOT NULL,
    kategori_alergi VARCHAR(30) NOT NULL,
    keterangan_alergi VARCHAR(150) NULL,
    CONSTRAINT uq_alergi_nama UNIQUE (nama_alergi),
    CONSTRAINT chk_kategori_alergi
        CHECK (kategori_alergi IN ('Obat', 'Makanan', 'Lingkungan', 'Lainnya'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE Pasien (
    id_pasien CHAR(5) PRIMARY KEY,
    nama_pasien VARCHAR(50) NOT NULL,
    nomor_telepon_pasien VARCHAR(16) NOT NULL,
    alamat_pasien VARCHAR(100) NOT NULL,
    tanggal_lahir_pasien DATE NOT NULL,
    jenis_kelamin_pasien CHAR(1) NOT NULL,

    CONSTRAINT chk_jenis_kelamin_pasien
        CHECK (jenis_kelamin_pasien IN ('L', 'P'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
    CONSTRAINT chk_keparahan_alergi
        CHECK (tingkat_keparahan IN ('Ringan', 'Sedang', 'Berat')),
    CONSTRAINT chk_status_alergi
        CHECK (status_alergi IN ('Aktif', 'Tidak Aktif'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE Poliklinik (
    id_poliklinik CHAR(5) PRIMARY KEY,
    nama_poliklinik VARCHAR(50) NOT NULL,
    lokasi_poliklinik VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE Dokter (
    id_dokter CHAR(5) PRIMARY KEY,
    nama_dokter VARCHAR(50) NOT NULL,
    nomor_telepon_dokter VARCHAR(16) NOT NULL,
    spesialisasi_dokter VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE Perawat (
    id_perawat CHAR(5) PRIMARY KEY,
    nama_perawat VARCHAR(50) NOT NULL,
    nomor_telepon_perawat VARCHAR(16) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE Registrasi (
    id_registrasi CHAR(5) PRIMARY KEY,
    tanggal_registrasi DATETIME NOT NULL,
    status_registrasi VARCHAR(20) NOT NULL,
    jenis_layanan VARCHAR(20) NOT NULL DEFAULT 'Rawat Jalan',
    Pasien_id_pasien CHAR(5) NOT NULL,
    Poliklinik_id_poliklinik CHAR(5) NOT NULL,
    CONSTRAINT fk_registrasi_pasien FOREIGN KEY (Pasien_id_pasien)
        REFERENCES Pasien(id_pasien)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_registrasi_poliklinik FOREIGN KEY (Poliklinik_id_poliklinik)
        REFERENCES Poliklinik(id_poliklinik)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_registrasi_jenis_layanan
        CHECK (jenis_layanan IN ('Rawat Jalan', 'Rawat Inap'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE Shift (
    id_shift INT PRIMARY KEY,
    Jenis_Shift VARCHAR(20) NOT NULL,
    Jam_Masuk TIME NOT NULL,
    Jam_Selesai TIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE Jadwal_Jaga (
    id_jadwal CHAR(5) PRIMARY KEY,
    tanggal_jaga DATE NOT NULL,
    Perawat_id_perawat CHAR(5) NOT NULL,
    Dokter_id_dokter CHAR(5) NOT NULL,
    Shift_id_shift INT NOT NULL,
    CONSTRAINT fk_jadwal_perawat FOREIGN KEY (Perawat_id_perawat)
        REFERENCES Perawat(id_perawat)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_jadwal_dokter FOREIGN KEY (Dokter_id_dokter)
        REFERENCES Dokter(id_dokter)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_jadwal_shift FOREIGN KEY (Shift_id_shift)
        REFERENCES Shift(id_shift)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE Kamar (
    id_kamar CHAR(5) PRIMARY KEY,
    nomor_kamar INT NOT NULL,
    tipe_kamar VARCHAR(30) NOT NULL,
    status_kamar VARCHAR(10) NOT NULL DEFAULT 'Kosong'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE Rawat_Inap (
    id_rawat_inap CHAR(5) PRIMARY KEY,
    tanggal_masuk DATETIME NOT NULL,
    tanggal_keluar DATETIME NULL,
    Kamar_id_kamar CHAR(5) NOT NULL,
    Registrasi_id_registrasi CHAR(5) NOT NULL,
    CONSTRAINT fk_rawat_kamar FOREIGN KEY (Kamar_id_kamar)
        REFERENCES Kamar(id_kamar)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_rawat_registrasi FOREIGN KEY (Registrasi_id_registrasi)
        REFERENCES Registrasi(id_registrasi)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE Rekam_Medis (
    id_rekam_medis CHAR(5) PRIMARY KEY,
    tanggal_pemeriksaan DATETIME NOT NULL,
    keluhan_pasien VARCHAR(150) NOT NULL,
    Registrasi_id_registrasi CHAR(5) NOT NULL,
    Dokter_id_dokter CHAR(5) NOT NULL,
    Perawat_id_perawat CHAR(5) NOT NULL,
    Rawat_Inap_id_rawat_inap CHAR(5) NULL,
    CONSTRAINT fk_rm_registrasi FOREIGN KEY (Registrasi_id_registrasi)
        REFERENCES Registrasi(id_registrasi)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_rm_dokter FOREIGN KEY (Dokter_id_dokter)
        REFERENCES Dokter(id_dokter)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_rm_perawat FOREIGN KEY (Perawat_id_perawat)
        REFERENCES Perawat(id_perawat)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_rm_rawat FOREIGN KEY (Rawat_Inap_id_rawat_inap)
        REFERENCES Rawat_Inap(id_rawat_inap)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT uq_rm_registrasi UNIQUE (Registrasi_id_registrasi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE Diagnosa (
    id_diagnosa CHAR(5) PRIMARY KEY,
    nama_diagnosa VARCHAR(100) NOT NULL,
    keterangan_diagnosa VARCHAR(150) NOT NULL,
    Rekam_Medis_id_rekam_medis CHAR(5) NOT NULL,
    CONSTRAINT fk_diagnosa_rm FOREIGN KEY (Rekam_Medis_id_rekam_medis)
        REFERENCES Rekam_Medis(id_rekam_medis)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE Tindakan_Medis (
    id_tindakan_medis CHAR(5) PRIMARY KEY,
    nama_tindakan VARCHAR(100) NOT NULL,
    biaya_tindakan DECIMAL(10,2) NOT NULL,
    hasil_tindakan VARCHAR(100) NOT NULL,
    Rekam_Medis_id_rekam_medis CHAR(5) NOT NULL,
    CONSTRAINT fk_tindakan_rm FOREIGN KEY (Rekam_Medis_id_rekam_medis)
        REFERENCES Rekam_Medis(id_rekam_medis)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE Detail_Resep (
    id_detail_resep CHAR(5) PRIMARY KEY,
    jumlah_obat INT NOT NULL,
    dosis_obat VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE Resep (
    id_resep CHAR(5) PRIMARY KEY,
    tanggal_resep DATETIME NOT NULL,
    Rekam_Medis_id_rekam_medis CHAR(5) NOT NULL,
    Detail_Resep_id_detail_resep CHAR(5) NOT NULL,
    CONSTRAINT fk_resep_rm FOREIGN KEY (Rekam_Medis_id_rekam_medis)
        REFERENCES Rekam_Medis(id_rekam_medis)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_resep_detail FOREIGN KEY (Detail_Resep_id_detail_resep)
        REFERENCES Detail_Resep(id_detail_resep)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE Obat (
    id_obat CHAR(5) PRIMARY KEY,
    nama_obat VARCHAR(100) NOT NULL,
    stok_obat INT NOT NULL,
    harga_obat DECIMAL(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE Obat_Resep (
    Obat_id_obat CHAR(5) NOT NULL,
    Resep_id_resep CHAR(5) NOT NULL,
    PRIMARY KEY (Obat_id_obat, Resep_id_resep),
    CONSTRAINT fk_or_obat FOREIGN KEY (Obat_id_obat)
        REFERENCES Obat(id_obat)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_or_resep FOREIGN KEY (Resep_id_resep)
        REFERENCES Resep(id_resep)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE Jenis_Pembayaran (
    id_jenis_pembayaran CHAR(5) PRIMARY KEY,
    nama_jenis_pembayaran VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE Asuransi (
    nomor_asuransi CHAR(13) PRIMARY KEY,
    nama_lembaga_asuransi VARCHAR(50) NOT NULL,
    jenis_asuransi VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE Detail_Pembayaran (
    id_detail_pembayaran CHAR(5) PRIMARY KEY,
    keterangan_biaya VARCHAR(100) NOT NULL,
    sub_total DECIMAL(12,2) NOT NULL,
    Tindakan_Medis_id_tindakan_medis CHAR(5) NULL,
    Rawat_Inap_id_rawat_inap CHAR(5) NULL,
    Resep_id_resep CHAR(5) NULL,
    CONSTRAINT fk_dp_tindakan FOREIGN KEY (Tindakan_Medis_id_tindakan_medis)
        REFERENCES Tindakan_Medis(id_tindakan_medis)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_dp_rawat FOREIGN KEY (Rawat_Inap_id_rawat_inap)
        REFERENCES Rawat_Inap(id_rawat_inap)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_dp_resep FOREIGN KEY (Resep_id_resep)
        REFERENCES Resep(id_resep)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE Pembayaran (
    id_pembayaran CHAR(5) PRIMARY KEY,
    total_biaya DECIMAL(12,2) NOT NULL DEFAULT 0,
    tanggal_pembayaran DATETIME NOT NULL,
    Registrasi_id_registrasi CHAR(5) NOT NULL,
    Jenis_Pembayaran_id_jenis_pembayaran CHAR(5) NOT NULL,
    Asuransi_nomor_asuransi CHAR(13) NULL,
    Detail_Pembayaran_id_detail_pembayaran CHAR(5) NOT NULL,
    CONSTRAINT fk_pembayaran_registrasi FOREIGN KEY (Registrasi_id_registrasi)
        REFERENCES Registrasi(id_registrasi)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pembayaran_jenis FOREIGN KEY (Jenis_Pembayaran_id_jenis_pembayaran)
        REFERENCES Jenis_Pembayaran(id_jenis_pembayaran)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pembayaran_asuransi FOREIGN KEY (Asuransi_nomor_asuransi)
        REFERENCES Asuransi(nomor_asuransi)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_pembayaran_detail FOREIGN KEY (Detail_Pembayaran_id_detail_pembayaran)
        REFERENCES Detail_Pembayaran(id_detail_pembayaran)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT uq_pembayaran_registrasi UNIQUE (Registrasi_id_registrasi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
