<?php
$action = $_GET['action'] ?? 'index';

if ($action === 'store') {
    postOnly();
    try {
        $buatResep = isset($_POST['buat_resep']) && $_POST['buat_resep'] === '1';
        $idResep = $buatResep ? $_POST['id_resep'] : null;
        $idDetailResep = $buatResep ? $_POST['id_detail_resep'] : null;
        $jumlahObat = $buatResep ? (int)$_POST['jumlah_obat'] : 0;
        $dosisObat = $buatResep ? $_POST['dosis_obat'] : null;

        execute('CALL buat_rekam_medis(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            $_POST['id_rekam_medis'],
            $_POST['keluhan_pasien'],
            $_POST['id_registrasi'],
            $_POST['id_dokter'],
            $_POST['id_perawat'],
            $_POST['id_rawat_inap'] !== '' ? $_POST['id_rawat_inap'] : null,
            $_POST['id_diagnosa'],
            $_POST['nama_diagnosa'],
            $_POST['keterangan_diagnosa'],
            $_POST['id_tindakan_medis'],
            $_POST['nama_tindakan'],
            $_POST['biaya_tindakan'],
            $_POST['hasil_tindakan'],
            $buatResep ? 1 : 0,
            $idResep,
            $idDetailResep,
            $jumlahObat,
            $dosisObat,
        ]);

        if ($buatResep && ($_POST['id_obat'] ?? '') !== '') {
            execute('INSERT INTO Obat_Resep (Obat_id_obat, Resep_id_resep) VALUES (?, ?)', [$_POST['id_obat'], $idResep]);
        }

        flash('Rekam medis berhasil dibuat.');
        redirect('medical');
    } catch (Throwable $e) {
        flash($e->getMessage(), 'danger');
        redirect('medical', ['action' => 'create']);
    }
}

if ($action === 'update_keluhan') {
    postOnly();
    execute('UPDATE Rekam_Medis SET keluhan_pasien = ? WHERE id_rekam_medis = ?', [$_POST['keluhan_pasien'], $_POST['id_rekam_medis']]);
    flash('Keluhan rekam medis diupdate. Trigger audit akan mencatat perubahan ini.');
    redirect('medical');
}

if ($action === 'create') {
    $registrasi = fetchAll(
        'SELECT r.id_registrasi, p.nama_pasien, pl.nama_poliklinik
         FROM Registrasi r
         JOIN Pasien p ON p.id_pasien = r.Pasien_id_pasien
         JOIN Poliklinik pl ON pl.id_poliklinik = r.Poliklinik_id_poliklinik
         ORDER BY r.tanggal_registrasi DESC'
    );
    $dokter = fetchAll('SELECT * FROM Dokter ORDER BY nama_dokter');
    $perawat = fetchAll('SELECT * FROM Perawat ORDER BY nama_perawat');
    $rawatInap = fetchAll(
        'SELECT ri.id_rawat_inap, p.nama_pasien, k.nomor_kamar
         FROM Rawat_Inap ri
         JOIN Registrasi r ON r.id_registrasi = ri.Registrasi_id_registrasi
         JOIN Pasien p ON p.id_pasien = r.Pasien_id_pasien
         JOIN Kamar k ON k.id_kamar = ri.Kamar_id_kamar
         WHERE ri.tanggal_keluar IS NULL
         ORDER BY ri.tanggal_masuk DESC'
    );
    $obat = fetchAll('SELECT * FROM Obat ORDER BY nama_obat');
    requiredSelect($registrasi, 'registrasi');
    requiredSelect($dokter, 'dokter');
    requiredSelect($perawat, 'perawat');

    $ids = [
        'rm' => 'RM000',
        'dg' => nextId('Diagnosa', 'id_diagnosa', 'DG', 3),
        'tm' => nextId('Tindakan_Medis', 'id_tindakan_medis', 'T', 4),
        'rs' => nextId('Resep', 'id_resep', 'RS', 3),
        'dr' => nextId('Detail_Resep', 'id_detail_resep', 'DR', 3),
    ];
    ?>
    <section class="header">
        <div>
            <h1>Buat Rekam Medis</h1>
            <p>Form ini memakai stored procedure buat_rekam_medis. ID RM000 akan digenerate trigger.</p>
        </div>
        <a class="btn secondary" href="<?= e(url('medical')) ?>">Kembali</a>
    </section>

    <form class="form" method="post" action="<?= e(url('medical', ['action' => 'store'])) ?>">
        <div class="card">
            <h2>Data Pemeriksaan</h2>
            <div class="form-row-3">
                <label>ID Rekam Medis
                    <input name="id_rekam_medis" value="<?= e($ids['rm']) ?>" readonly>
                </label>
                <label>Registrasi
                    <select name="id_registrasi" required>
                        <?php foreach ($registrasi as $row): ?><option value="<?= e($row['id_registrasi']) ?>"><?= e($row['id_registrasi'] . ' - ' . $row['nama_pasien'] . ' - ' . $row['nama_poliklinik']) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <label>Rawat Inap Opsional
                    <select name="id_rawat_inap">
                        <option value="">Tidak rawat inap</option>
                        <?php foreach ($rawatInap as $row): ?><option value="<?= e($row['id_rawat_inap']) ?>"><?= e($row['id_rawat_inap'] . ' - ' . $row['nama_pasien'] . ' - Kamar ' . $row['nomor_kamar']) ?></option><?php endforeach; ?>
                    </select>
                </label>
            </div>
            <div class="form-row">
                <label>Dokter
                    <select name="id_dokter" required>
                        <?php foreach ($dokter as $row): ?><option value="<?= e($row['id_dokter']) ?>"><?= e($row['nama_dokter'] . ' - ' . $row['spesialisasi_dokter']) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <label>Perawat
                    <select name="id_perawat" required>
                        <?php foreach ($perawat as $row): ?><option value="<?= e($row['id_perawat']) ?>"><?= e($row['nama_perawat']) ?></option><?php endforeach; ?>
                    </select>
                </label>
            </div>
            <label>Keluhan Pasien
                <textarea name="keluhan_pasien" required></textarea>
            </label>
        </div>

        <div class="grid grid-2">
            <div class="card">
                <h2>Diagnosa</h2>
                <label>ID Diagnosa <input name="id_diagnosa" value="<?= e($ids['dg']) ?>" readonly></label>
                <label>Nama Diagnosa <input name="nama_diagnosa" required></label>
                <label>Keterangan <textarea name="keterangan_diagnosa" required></textarea></label>
            </div>
            <div class="card">
                <h2>Tindakan Medis</h2>
                <label>ID Tindakan <input name="id_tindakan_medis" value="<?= e($ids['tm']) ?>" readonly></label>
                <label>Nama Tindakan <input name="nama_tindakan" required></label>
                <label>Biaya Tindakan <input type="number" name="biaya_tindakan" min="0" step="0.01" required></label>
                <label>Hasil Tindakan <textarea name="hasil_tindakan" required></textarea></label>
            </div>
        </div>

        <div class="card">
            <h2>Resep Opsional</h2>
            <label class="small"><input type="checkbox" name="buat_resep" value="1"> Buat resep untuk rekam medis ini</label>
            <div class="form-row-3">
                <label>ID Resep <input name="id_resep" value="<?= e($ids['rs']) ?>" readonly></label>
                <label>ID Detail Resep <input name="id_detail_resep" value="<?= e($ids['dr']) ?>" readonly></label>
                <label>Obat
                    <select name="id_obat">
                        <option value="">Pilih obat</option>
                        <?php foreach ($obat as $row): ?><option value="<?= e($row['id_obat']) ?>"><?= e($row['nama_obat'] . ' - stok ' . $row['stok_obat']) ?></option><?php endforeach; ?>
                    </select>
                </label>
            </div>
            <div class="form-row">
                <label>Jumlah Obat <input type="number" name="jumlah_obat" min="0" value="0"></label>
                <label>Dosis Obat <input name="dosis_obat" placeholder="contoh: 3x1 tablet"></label>
            </div>
        </div>

        <div class="actions"><button class="btn" type="submit">Simpan Rekam Medis</button></div>
    </form>
    <?php
    return;
}

$medical = fetchAll(
    'SELECT rm.*, p.nama_pasien, d.nama_dokter, pr.nama_perawat, dg.nama_diagnosa, tm.nama_tindakan, tm.biaya_tindakan
     FROM Rekam_Medis rm
     JOIN Registrasi r ON r.id_registrasi = rm.Registrasi_id_registrasi
     JOIN Pasien p ON p.id_pasien = r.Pasien_id_pasien
     JOIN Dokter d ON d.id_dokter = rm.Dokter_id_dokter
     JOIN Perawat pr ON pr.id_perawat = rm.Perawat_id_perawat
     LEFT JOIN Diagnosa dg ON dg.Rekam_Medis_id_rekam_medis = rm.id_rekam_medis
     LEFT JOIN Tindakan_Medis tm ON tm.Rekam_Medis_id_rekam_medis = rm.id_rekam_medis
     ORDER BY rm.tanggal_pemeriksaan DESC'
);
?>
<section class="header">
    <div>
        <h1>Rekam Medis</h1>
        <p>Riwayat medis pasien, diagnosa, tindakan, dan resep.</p>
    </div>
    <a class="btn" href="<?= e(url('medical', ['action' => 'create'])) ?>">Buat Rekam Medis</a>
</section>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>ID</th><th>Tanggal</th><th>Pasien</th><th>Dokter</th><th>Diagnosa</th><th>Tindakan</th><th>Keluhan</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($medical as $row): ?>
                <tr>
                    <td><?= e($row['id_rekam_medis']) ?></td>
                    <td><?= e($row['tanggal_pemeriksaan']) ?></td>
                    <td><?= e($row['nama_pasien']) ?></td>
                    <td><?= e($row['nama_dokter']) ?></td>
                    <td><?= e($row['nama_diagnosa'] ?? '-') ?></td>
                    <td><?= e($row['nama_tindakan'] ?? '-') ?><br><span class="muted small"><?= isset($row['biaya_tindakan']) ? e(rupiah($row['biaya_tindakan'])) : '' ?></span></td>
                    <td><?= e($row['keluhan_pasien']) ?></td>
                    <td>
                        <form method="post" action="<?= e(url('medical', ['action' => 'update_keluhan'])) ?>" class="form">
                            <input type="hidden" name="id_rekam_medis" value="<?= e($row['id_rekam_medis']) ?>">
                            <input name="keluhan_pasien" value="<?= e($row['keluhan_pasien']) ?>">
                            <button class="btn secondary" type="submit">Update Keluhan</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$medical): ?><tr><td colspan="8" class="muted">Belum ada data.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
