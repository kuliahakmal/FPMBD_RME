SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- File ini aman diimpor ulang setelah migration untuk memperbarui routine dan trigger.
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

-- 1. TABEL LOG AUDIT

CREATE TABLE IF NOT EXISTS Log_Audit_Rekam_Medis (
    id_log INT AUTO_INCREMENT PRIMARY KEY,
    waktu_perubahan DATETIME NOT NULL,
    user_pelaku VARCHAR(50) NOT NULL,
    id_rekam_medis_lama CHAR(5),
    id_rekam_medis_baru CHAR(5),
    keluhan_lama VARCHAR(150),
    keluhan_baru VARCHAR(150),
    dokter_lama CHAR(5),
    dokter_baru CHAR(5),
    perawat_lama CHAR(5),
    perawat_baru CHAR(5)
);

-- 2. FUNCTIONS

DELIMITER $$

-- 2.1 Function Menghitung Umur Pasien
CREATE FUNCTION hitung_umur_pasien(p_id_pasien CHAR(5))
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_tgl_lahir DATE;
    SELECT tanggal_lahir_pasien INTO v_tgl_lahir 
    FROM Pasien 
    WHERE id_pasien = p_id_pasien;
    
    RETURN TIMESTAMPDIFF(YEAR, v_tgl_lahir, CURDATE());
END$$

-- 2.2 Function Menghitung Total Biaya Pembayaran
CREATE FUNCTION hitung_total_biaya(p_id_registrasi CHAR(5))
RETURNS DECIMAL(12,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_total DECIMAL(12,2) DEFAULT 0.00;
    
    SELECT COALESCE(SUM(sub_total), 0.00) INTO v_total
    FROM Detail_Pembayaran dp
    WHERE dp.Rawat_Inap_id_rawat_inap IN (
        SELECT id_rawat_inap FROM Rawat_Inap WHERE Registrasi_id_registrasi = p_id_registrasi
    )
    OR dp.Resep_id_resep IN (
        SELECT r.id_resep 
        FROM Resep r 
        JOIN Rekam_Medis rm ON r.Rekam_Medis_id_rekam_medis = rm.id_rekam_medis 
        WHERE rm.Registrasi_id_registrasi = p_id_registrasi
    )
    OR dp.Tindakan_Medis_id_tindakan_medis IN (
        SELECT tm.id_tindakan_medis 
        FROM Tindakan_Medis tm 
        JOIN Rekam_Medis rm ON tm.Rekam_Medis_id_rekam_medis = rm.id_rekam_medis 
        WHERE rm.Registrasi_id_registrasi = p_id_registrasi
    );
    
    RETURN v_total;
END$$

-- 2.3 Function Mengecek Ketersediaan Kamar
CREATE FUNCTION cek_ketersediaan_kamar(p_id_kamar CHAR(5))
RETURNS VARCHAR(10)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_status VARCHAR(10);
    SELECT status_kamar INTO v_status 
    FROM Kamar 
    WHERE id_kamar = p_id_kamar;
    
    RETURN COALESCE(v_status, 'Tidak Ada');
END$$

-- 2.4 Function Menghitung Total Obat dalam Resep
CREATE FUNCTION hitung_total_obat(p_id_resep CHAR(5))
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_total INT DEFAULT 0;
    
    SELECT COALESCE(dr.jumlah_obat, 0) INTO v_total
    FROM Resep r
    JOIN Detail_Resep dr ON r.Detail_Resep_id_detail_resep = dr.id_detail_resep
    WHERE r.id_resep = p_id_resep;
    
    RETURN v_total;
END$$

-- 2.5 Function Menampilkan Riwayat Alergi Pasien
CREATE FUNCTION riwayat_alergi_pasien(p_id_pasien CHAR(5))
RETURNS VARCHAR(1000)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_riwayat VARCHAR(1000);
    
    SELECT GROUP_CONCAT(
        CONCAT(a.nama_alergi, ' [', ra.status_alergi, '] - ', ra.reaksi_alergi)
        ORDER BY (ra.status_alergi = 'Aktif') DESC, a.nama_alergi
        SEPARATOR '; '
    ) INTO v_riwayat
    FROM Riwayat_Alergi ra
    JOIN Alergi a ON a.id_alergi = ra.Alergi_id_alergi
    WHERE ra.Pasien_id_pasien = p_id_pasien;
    
    RETURN COALESCE(v_riwayat, 'Tidak ada riwayat alergi');
END$$

-- 3. STORED PROCEDURES

-- 3.1 Stored Procedure Registrasi Pasien Baru
CREATE PROCEDURE registrasi_pasien_baru(
    IN p_id_pasien CHAR(5),
    IN p_nama_pasien VARCHAR(50),
    IN p_no_telp VARCHAR(16),
    IN p_alamat VARCHAR(100),
    IN p_tgl_lahir DATE,
    IN p_gender CHAR(1),
    IN p_riwayat_alergi_json LONGTEXT,
    IN p_id_registrasi CHAR(5),
    IN p_id_poliklinik CHAR(5),
    IN p_jenis_layanan VARCHAR(20)
)
BEGIN
    DECLARE v_index INT DEFAULT 0;
    DECLARE v_jumlah INT DEFAULT 0;
    DECLARE v_path VARCHAR(30);
    DECLARE v_id_alergi CHAR(5);
    DECLARE v_reaksi VARCHAR(150);
    DECLARE v_keparahan VARCHAR(10);
    DECLARE v_tanggal DATE;
    DECLARE v_status VARCHAR(12);
    DECLARE v_catatan VARCHAR(250);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    IF p_riwayat_alergi_json IS NULL OR JSON_VALID(p_riwayat_alergi_json) = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Data riwayat alergi harus berupa JSON yang valid';
    END IF;

    IF p_tgl_lahir > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Tanggal lahir pasien tidak boleh di masa depan';
    END IF;

    SET p_jenis_layanan = COALESCE(NULLIF(p_jenis_layanan, ''), 'Rawat Jalan');
    IF p_jenis_layanan NOT IN ('Rawat Jalan', 'Rawat Inap') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Jenis layanan harus Rawat Jalan atau Rawat Inap';
    END IF;
    
    INSERT INTO Pasien (
        id_pasien, nama_pasien, nomor_telepon_pasien, alamat_pasien, 
        tanggal_lahir_pasien, jenis_kelamin_pasien
    ) VALUES (
        p_id_pasien, p_nama_pasien, p_no_telp, p_alamat, 
        p_tgl_lahir, p_gender
    );

    SET v_jumlah = JSON_LENGTH(p_riwayat_alergi_json);
    WHILE v_index < v_jumlah DO
        SET v_path = CONCAT('$[', v_index, ']');
        SET v_id_alergi = JSON_UNQUOTE(JSON_EXTRACT(p_riwayat_alergi_json, CONCAT(v_path, '.id_alergi')));
        SET v_reaksi = JSON_UNQUOTE(JSON_EXTRACT(p_riwayat_alergi_json, CONCAT(v_path, '.reaksi')));
        SET v_keparahan = JSON_UNQUOTE(JSON_EXTRACT(p_riwayat_alergi_json, CONCAT(v_path, '.keparahan')));
        SET v_tanggal = JSON_UNQUOTE(JSON_EXTRACT(p_riwayat_alergi_json, CONCAT(v_path, '.tanggal_diketahui')));
        SET v_status = JSON_UNQUOTE(JSON_EXTRACT(p_riwayat_alergi_json, CONCAT(v_path, '.status')));
        SET v_catatan = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_riwayat_alergi_json, CONCAT(v_path, '.catatan'))), 'null');

        IF v_id_alergi IS NULL OR v_reaksi IS NULL OR v_reaksi = ''
            OR v_keparahan NOT IN ('Ringan', 'Sedang', 'Berat')
            OR v_tanggal IS NULL OR v_tanggal > CURDATE()
            OR v_status NOT IN ('Aktif', 'Tidak Aktif') THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Detail riwayat alergi tidak valid';
        END IF;

        INSERT INTO Riwayat_Alergi (
            Pasien_id_pasien, Alergi_id_alergi, reaksi_alergi,
            tingkat_keparahan, tanggal_diketahui, status_alergi, catatan
        ) VALUES (
            p_id_pasien, v_id_alergi, v_reaksi,
            v_keparahan, v_tanggal, v_status, v_catatan
        );

        SET v_index = v_index + 1;
    END WHILE;
    
    INSERT INTO Registrasi (
        id_registrasi, tanggal_registrasi, status_registrasi, jenis_layanan,
        Pasien_id_pasien, Poliklinik_id_poliklinik
    ) VALUES (
        p_id_registrasi, NOW(), 'Terdaftar', p_jenis_layanan,
        p_id_pasien, p_id_poliklinik
    );
    
    COMMIT;
END$$

-- 3.2 Stored Procedure Memperbarui Pasien dan Riwayat Alergi
CREATE PROCEDURE perbarui_pasien_dan_alergi(
    IN p_id_pasien CHAR(5),
    IN p_nama_pasien VARCHAR(50),
    IN p_no_telp VARCHAR(16),
    IN p_alamat VARCHAR(100),
    IN p_tgl_lahir DATE,
    IN p_gender CHAR(1),
    IN p_riwayat_alergi_json LONGTEXT
)
BEGIN
    DECLARE v_index INT DEFAULT 0;
    DECLARE v_jumlah INT DEFAULT 0;
    DECLARE v_path VARCHAR(30);
    DECLARE v_id_alergi CHAR(5);
    DECLARE v_reaksi VARCHAR(150);
    DECLARE v_keparahan VARCHAR(10);
    DECLARE v_tanggal DATE;
    DECLARE v_status VARCHAR(12);
    DECLARE v_catatan VARCHAR(250);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    IF p_riwayat_alergi_json IS NULL OR JSON_VALID(p_riwayat_alergi_json) = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Data riwayat alergi harus berupa JSON yang valid';
    END IF;

    IF p_tgl_lahir > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Tanggal lahir pasien tidak boleh di masa depan';
    END IF;

    UPDATE Pasien
    SET nama_pasien = p_nama_pasien,
        nomor_telepon_pasien = p_no_telp,
        alamat_pasien = p_alamat,
        tanggal_lahir_pasien = p_tgl_lahir,
        jenis_kelamin_pasien = p_gender
    WHERE id_pasien = p_id_pasien;

    -- Data yang dilepas dari form tetap dipertahankan sebagai riwayat tidak aktif.
    UPDATE Riwayat_Alergi
    SET status_alergi = 'Tidak Aktif'
    WHERE Pasien_id_pasien = p_id_pasien;

    SET v_jumlah = JSON_LENGTH(p_riwayat_alergi_json);
    WHILE v_index < v_jumlah DO
        SET v_path = CONCAT('$[', v_index, ']');
        SET v_id_alergi = JSON_UNQUOTE(JSON_EXTRACT(p_riwayat_alergi_json, CONCAT(v_path, '.id_alergi')));
        SET v_reaksi = JSON_UNQUOTE(JSON_EXTRACT(p_riwayat_alergi_json, CONCAT(v_path, '.reaksi')));
        SET v_keparahan = JSON_UNQUOTE(JSON_EXTRACT(p_riwayat_alergi_json, CONCAT(v_path, '.keparahan')));
        SET v_tanggal = JSON_UNQUOTE(JSON_EXTRACT(p_riwayat_alergi_json, CONCAT(v_path, '.tanggal_diketahui')));
        SET v_status = JSON_UNQUOTE(JSON_EXTRACT(p_riwayat_alergi_json, CONCAT(v_path, '.status')));
        SET v_catatan = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_riwayat_alergi_json, CONCAT(v_path, '.catatan'))), 'null');

        IF v_id_alergi IS NULL OR v_reaksi IS NULL OR v_reaksi = ''
            OR v_keparahan NOT IN ('Ringan', 'Sedang', 'Berat')
            OR v_tanggal IS NULL OR v_tanggal > CURDATE()
            OR v_status NOT IN ('Aktif', 'Tidak Aktif') THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Detail riwayat alergi tidak valid';
        END IF;

        INSERT INTO Riwayat_Alergi (
            Pasien_id_pasien, Alergi_id_alergi, reaksi_alergi,
            tingkat_keparahan, tanggal_diketahui, status_alergi, catatan
        ) VALUES (
            p_id_pasien, v_id_alergi, v_reaksi,
            v_keparahan, v_tanggal, v_status, v_catatan
        )
        ON DUPLICATE KEY UPDATE
            reaksi_alergi = VALUES(reaksi_alergi),
            tingkat_keparahan = VALUES(tingkat_keparahan),
            tanggal_diketahui = VALUES(tanggal_diketahui),
            status_alergi = VALUES(status_alergi),
            catatan = VALUES(catatan);

        SET v_index = v_index + 1;
    END WHILE;

    COMMIT;
END$$

-- 3.3 Stored Procedure Pembuatan Rekam Medis
CREATE PROCEDURE buat_rekam_medis(
    IN p_id_rekam_medis CHAR(5),
    IN p_keluhan VARCHAR(150),
    IN p_id_registrasi CHAR(5),
    IN p_id_dokter CHAR(5),
    IN p_id_perawat CHAR(5),
    IN p_id_rawat_inap CHAR(5),
    IN p_id_diagnosa CHAR(5),
    IN p_nama_diagnosa VARCHAR(100),
    IN p_ket_diagnosa VARCHAR(150),
    IN p_id_tindakan CHAR(5),
    IN p_nama_tindakan VARCHAR(100),
    IN p_biaya_tindakan DECIMAL(10,2),
    IN p_hasil_tindakan VARCHAR(100),
    IN p_buat_resep BOOLEAN,
    IN p_id_resep CHAR(5),
    IN p_id_detail_resep CHAR(5),
    IN p_jumlah_obat INT,
    IN p_dosis_obat VARCHAR(50)
)
BEGIN
    DECLARE v_id_rekam_medis CHAR(5);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    INSERT INTO Rekam_Medis (
        id_rekam_medis, tanggal_pemeriksaan, keluhan_pasien, 
        Registrasi_id_registrasi, Dokter_id_dokter, Perawat_id_perawat, Rawat_Inap_id_rawat_inap
    ) VALUES (
        p_id_rekam_medis, NOW(), p_keluhan, 
        p_id_registrasi, p_id_dokter, p_id_perawat, p_id_rawat_inap
    );

    SELECT id_rekam_medis INTO v_id_rekam_medis
    FROM Rekam_Medis
    WHERE Registrasi_id_registrasi = p_id_registrasi
    LIMIT 1;

    INSERT INTO Diagnosa (
        id_diagnosa, nama_diagnosa, keterangan_diagnosa, Rekam_Medis_id_rekam_medis
    ) VALUES (
        p_id_diagnosa, p_nama_diagnosa, p_ket_diagnosa, v_id_rekam_medis
    );

    INSERT INTO Tindakan_Medis (
        id_tindakan_medis, nama_tindakan, biaya_tindakan, hasil_tindakan, Rekam_Medis_id_rekam_medis
    ) VALUES (
        p_id_tindakan, p_nama_tindakan, p_biaya_tindakan, p_hasil_tindakan, v_id_rekam_medis
    );

    IF p_buat_resep THEN
        INSERT INTO Detail_Resep (
            id_detail_resep, jumlah_obat, dosis_obat
        ) VALUES (
            p_id_detail_resep, p_jumlah_obat, p_dosis_obat
        );

        INSERT INTO Resep (
            id_resep, tanggal_resep, Rekam_Medis_id_rekam_medis, Detail_Resep_id_detail_resep
        ) VALUES (
            p_id_resep, NOW(), v_id_rekam_medis, p_id_detail_resep
        );
    END IF;

    COMMIT;
END$$

-- 3.3 Stored Procedure Proses Pembayaran
CREATE PROCEDURE proses_pembayaran(
    IN p_id_pembayaran CHAR(5),
    IN p_id_registrasi CHAR(5),
    IN p_id_jenis_pembayaran CHAR(5),
    IN p_no_asuransi CHAR(13),
    IN p_id_detail_pembayaran CHAR(5)
)
BEGIN
    DECLARE v_total DECIMAL(10,2) DEFAULT 0.00;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- 1. Mengambil detail biaya
    SELECT COALESCE(sub_total, 0.00) INTO v_total
    FROM Detail_Pembayaran
    WHERE id_detail_pembayaran = p_id_detail_pembayaran;

    -- 2. Menyimpan data pembayaran & mengaitkan metode pembayaran (Jenis_Pembayaran)
    INSERT INTO Pembayaran (
        id_pembayaran, total_biaya, tanggal_pembayaran, 
        Registrasi_id_registrasi, Jenis_Pembayaran_id_jenis_pembayaran, 
        Asuransi_nomor_asuransi, Detail_Pembayaran_id_detail_pembayaran
    ) VALUES (
        p_id_pembayaran, v_total, NOW(), 
        p_id_registrasi, p_id_jenis_pembayaran, 
        p_no_asuransi, p_id_detail_pembayaran
    );

    COMMIT;
END$$

-- 3.4 Stored Procedure Penjadwalan Jaga Dokter dan Perawat
CREATE PROCEDURE tambah_jadwal_jaga(
    IN p_id_jadwal CHAR(5),
    IN p_tanggal DATE,
    IN p_id_perawat CHAR(5),
    IN p_id_dokter CHAR(5),
    IN p_id_shift INT
)
BEGIN
    DECLARE v_dokter_conflict INT DEFAULT 0;
    DECLARE v_perawat_conflict INT DEFAULT 0;

    -- Cek jika dokter sudah memiliki jadwal jaga pada hari dan shift tersebut
    SELECT COUNT(*) INTO v_dokter_conflict
    FROM Jadwal_Jaga
    WHERE tanggal_jaga = p_tanggal 
      AND Dokter_id_dokter = p_id_dokter 
      AND Shift_id_shift = p_id_shift;

    -- Cek jika perawat sudah memiliki jadwal jaga pada hari dan shift tersebut
    SELECT COUNT(*) INTO v_perawat_conflict
    FROM Jadwal_Jaga
    WHERE tanggal_jaga = p_tanggal 
      AND Perawat_id_perawat = p_id_perawat 
      AND Shift_id_shift = p_id_shift;

    IF v_dokter_conflict > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: Dokter sudah memiliki jadwal jaga pada shift tersebut!';
    ELSEIF v_perawat_conflict > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: Perawat sudah memiliki jadwal jaga pada shift tersebut!';
    ELSE
        INSERT INTO Jadwal_Jaga (id_jadwal, tanggal_jaga, Perawat_id_perawat, Dokter_id_dokter, Shift_id_shift)
        VALUES (p_id_jadwal, p_tanggal, p_id_perawat, p_id_dokter, p_id_shift);
    END IF;
END$$

-- 3.5 Stored Procedure Rawat Inap Pasien
CREATE PROCEDURE proses_rawat_inap(
    IN p_id_rawat_inap CHAR(5),
    IN p_tanggal_masuk DATETIME,
    IN p_id_kamar CHAR(5),
    IN p_id_registrasi CHAR(5)
)
BEGIN
    DECLARE v_status VARCHAR(10);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- 1. Mengecek status kamar
    SELECT status_kamar INTO v_status FROM Kamar WHERE id_kamar = p_id_kamar;

    IF v_status = 'Terisi' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: Kamar sudah terisi!';
    ELSE
        -- 2. Membuat data rawat inap
        INSERT INTO Rawat_Inap (id_rawat_inap, tanggal_masuk, tanggal_keluar, Kamar_id_kamar, Registrasi_id_registrasi)
        VALUES (p_id_rawat_inap, p_tanggal_masuk, NULL, p_id_kamar, p_id_registrasi);

        -- 3. Menandai jenis layanan registrasi. Status kamar diubah oleh trigger.
        UPDATE Registrasi
        SET jenis_layanan = 'Rawat Inap'
        WHERE id_registrasi = p_id_registrasi;
    END IF;

    COMMIT;
END$$

-- 4. TRIGGERS

-- 4.1 Trigger Generate Otomatis ID Rekam Medis
CREATE TRIGGER trg_generate_id_rekam_medis
BEFORE INSERT ON Rekam_Medis
FOR EACH ROW
BEGIN
    DECLARE v_max_id VARCHAR(5);
    DECLARE v_num INT;
    DECLARE v_new_id VARCHAR(5);

    IF NEW.id_rekam_medis IS NULL OR NEW.id_rekam_medis = '' OR NEW.id_rekam_medis = 'RM000' THEN
        SELECT MAX(id_rekam_medis) INTO v_max_id FROM Rekam_Medis;

        IF v_max_id IS NULL THEN
            SET v_new_id = 'RM001';
        ELSE
            SET v_num = CAST(SUBSTRING(v_max_id, 3, 3) AS UNSIGNED) + 1;
            SET v_new_id = CONCAT('RM', LPAD(v_num, 3, '0'));
        END IF;

        SET NEW.id_rekam_medis = v_new_id;
    END IF;
END$$

-- 4.2 Trigger Mengurangi Stok Obat Setelah Resep Dibuat
CREATE TRIGGER trg_kurang_stok_obat
AFTER INSERT ON Obat_Resep
FOR EACH ROW
BEGIN
    DECLARE v_jumlah INT;

    SELECT dr.jumlah_obat INTO v_jumlah
    FROM Resep r
    JOIN Detail_Resep dr ON r.Detail_Resep_id_detail_resep = dr.id_detail_resep
    WHERE r.id_resep = NEW.Resep_id_resep;

    UPDATE Obat
    SET stok_obat = stok_obat - COALESCE(v_jumlah, 0)
    WHERE id_obat = NEW.Obat_id_obat;
END$$

-- 4.3 Trigger Mengubah Status Kamar Menjadi Terisi
CREATE TRIGGER trg_kamar_terisi
AFTER INSERT ON Rawat_Inap
FOR EACH ROW
BEGIN
    UPDATE Kamar
    SET status_kamar = 'Terisi'
    WHERE id_kamar = NEW.Kamar_id_kamar;

    -- Menjaga konsistensi jika Rawat_Inap dibuat langsung tanpa procedure.
    UPDATE Registrasi
    SET jenis_layanan = 'Rawat Inap'
    WHERE id_registrasi = NEW.Registrasi_id_registrasi;
END$$

-- 4.4 Trigger Mengubah Status Kamar Menjadi Kosong
CREATE TRIGGER trg_kamar_kosong
AFTER UPDATE ON Rawat_Inap
FOR EACH ROW
BEGIN
    IF NEW.tanggal_keluar IS NOT NULL AND OLD.tanggal_keluar IS NULL THEN
        UPDATE Kamar
        SET status_kamar = 'Kosong'
        WHERE id_kamar = NEW.Kamar_id_kamar;
    END IF;
END$$

-- 4.5 Trigger Validasi Stok Obat
CREATE TRIGGER trg_validasi_stok_obat
BEFORE INSERT ON Obat_Resep
FOR EACH ROW
BEGIN
    DECLARE v_stok INT DEFAULT 0;
    DECLARE v_jumlah INT DEFAULT 0;

    SELECT stok_obat INTO v_stok
    FROM Obat
    WHERE id_obat = NEW.Obat_id_obat;

    SELECT dr.jumlah_obat INTO v_jumlah
    FROM Resep r
    JOIN Detail_Resep dr ON r.Detail_Resep_id_detail_resep = dr.id_detail_resep
    WHERE r.id_resep = NEW.Resep_id_resep;

    IF v_stok < v_jumlah THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Stok obat tidak mencukupi untuk memenuhi resep ini!';
    END IF;
END$$

-- 4.6 Trigger Perhitungan Otomatis Total Pembayaran (BEFORE INSERT)
CREATE TRIGGER trg_set_total_pembayaran
BEFORE INSERT ON Pembayaran
FOR EACH ROW
BEGIN
    DECLARE v_sub DECIMAL(12,2) DEFAULT 0.00;
    
    SELECT sub_total INTO v_sub
    FROM Detail_Pembayaran
    WHERE id_detail_pembayaran = NEW.Detail_Pembayaran_id_detail_pembayaran;
    
    SET NEW.total_biaya = COALESCE(v_sub, 0.00);
END$$

-- 4.6 Trigger Perhitungan Otomatis Total Pembayaran (AFTER UPDATE)
CREATE TRIGGER trg_update_pembayaran_upd
AFTER UPDATE ON Detail_Pembayaran
FOR EACH ROW
BEGIN
    UPDATE Pembayaran
    SET total_biaya = NEW.sub_total
    WHERE Detail_Pembayaran_id_detail_pembayaran = NEW.id_detail_pembayaran;
END$$

-- 4.7 Trigger Audit Rekam Medis
CREATE TRIGGER trg_audit_rekam_medis
AFTER UPDATE ON Rekam_Medis
FOR EACH ROW
BEGIN
    INSERT INTO Log_Audit_Rekam_Medis (
        waktu_perubahan, user_pelaku,
        id_rekam_medis_lama, id_rekam_medis_baru,
        keluhan_lama, keluhan_baru,
        dokter_lama, dokter_baru,
        perawat_lama, perawat_baru
    ) VALUES (
        NOW(), USER(),
        OLD.id_rekam_medis, NEW.id_rekam_medis,
        OLD.keluhan_pasien, NEW.keluhan_pasien,
        OLD.Dokter_id_dokter, NEW.Dokter_id_dokter,
        OLD.Perawat_id_perawat, NEW.Perawat_id_perawat
    );
END$$

DELIMITER ;
