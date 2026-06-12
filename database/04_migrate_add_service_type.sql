-- Migrasi aman untuk menambahkan jenis layanan eksplisit pada database lama.
-- Backup disarankan sebelum migrasi:
-- mariadb-dump -h HOST -P PORT -u USER -p DATABASE > backup_sebelum_jenis_layanan.sql
--
-- Setelah file ini berhasil, impor ulang database/01_functions_procedures_triggers.sql
-- agar signature procedure dan trigger mendapatkan definisi terbaru.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

SET @has_service_column = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'Registrasi'
      AND COLUMN_NAME = 'jenis_layanan'
);
SET @add_service_column_sql = IF(
    @has_service_column = 0,
    'ALTER TABLE Registrasi ADD COLUMN jenis_layanan VARCHAR(20) NULL DEFAULT ''Rawat Jalan'' AFTER status_registrasi',
    'SELECT 1'
);
PREPARE stmt FROM @add_service_column_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE Registrasi r
LEFT JOIN Rawat_Inap ri ON ri.Registrasi_id_registrasi = r.id_registrasi
SET r.jenis_layanan = CASE
    WHEN ri.id_rawat_inap IS NOT NULL THEN 'Rawat Inap'
    ELSE 'Rawat Jalan'
END;

ALTER TABLE Registrasi
    MODIFY COLUMN jenis_layanan VARCHAR(20) NOT NULL DEFAULT 'Rawat Jalan'
    AFTER status_registrasi;

SET @has_service_check = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'Registrasi'
      AND CONSTRAINT_NAME = 'chk_registrasi_jenis_layanan'
);
SET @add_service_check_sql = IF(
    @has_service_check = 0,
    'ALTER TABLE Registrasi ADD CONSTRAINT chk_registrasi_jenis_layanan CHECK (jenis_layanan IN (''Rawat Jalan'', ''Rawat Inap''))',
    'SELECT 1'
);
PREPARE stmt FROM @add_service_check_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_service_index = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'Registrasi'
      AND INDEX_NAME = 'idx_registrasi_jenis_layanan'
);
SET @add_service_index_sql = IF(
    @has_service_index = 0,
    'CREATE INDEX idx_registrasi_jenis_layanan ON Registrasi(jenis_layanan)',
    'SELECT 1'
);
PREPARE stmt FROM @add_service_index_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT jenis_layanan, COUNT(*) AS jumlah_registrasi
FROM Registrasi
GROUP BY jenis_layanan;
