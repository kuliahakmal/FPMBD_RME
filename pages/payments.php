<?php
$action = $_GET['action'] ?? 'index';

if ($action === 'store') {
    postOnly();
    try {
        execute(
            'INSERT INTO Detail_Pembayaran (id_detail_pembayaran, keterangan_biaya, sub_total, Tindakan_Medis_id_tindakan_medis, Rawat_Inap_id_rawat_inap, Resep_id_resep) VALUES (?, ?, ?, ?, ?, ?)',
            [
                $_POST['id_detail_pembayaran'],
                $_POST['keterangan_biaya'],
                $_POST['sub_total'],
                $_POST['id_tindakan_medis'] !== '' ? $_POST['id_tindakan_medis'] : null,
                $_POST['id_rawat_inap'] !== '' ? $_POST['id_rawat_inap'] : null,
                $_POST['id_resep'] !== '' ? $_POST['id_resep'] : null,
            ]
        );

        execute('CALL proses_pembayaran(?, ?, ?, ?, ?)', [
            $_POST['id_pembayaran'],
            $_POST['id_registrasi'],
            $_POST['id_jenis_pembayaran'],
            $_POST['nomor_asuransi'] !== '' ? $_POST['nomor_asuransi'] : null,
            $_POST['id_detail_pembayaran'],
        ]);

        flash('Pembayaran berhasil dibuat. Total biaya disinkronkan oleh trigger.');
        redirect('payments');
    } catch (Throwable $e) {
        flash($e->getMessage(), 'danger');
        redirect('payments', ['action' => 'create']);
    }
}

if ($action === 'create') {
    $registrasi = fetchAll(
        'SELECT r.id_registrasi, r.jenis_layanan, p.nama_pasien FROM Registrasi r JOIN Pasien p ON p.id_pasien = r.Pasien_id_pasien ORDER BY r.tanggal_registrasi DESC'
    );
    $jenis = fetchAll('SELECT * FROM Jenis_Pembayaran ORDER BY nama_jenis_pembayaran');
    $asuransi = fetchAll('SELECT * FROM Asuransi ORDER BY nama_lembaga_asuransi');
    $tindakan = fetchAll('SELECT tm.*, p.nama_pasien FROM Tindakan_Medis tm JOIN Rekam_Medis rm ON rm.id_rekam_medis = tm.Rekam_Medis_id_rekam_medis JOIN Registrasi r ON r.id_registrasi = rm.Registrasi_id_registrasi JOIN Pasien p ON p.id_pasien = r.Pasien_id_pasien ORDER BY tm.id_tindakan_medis DESC');
    $rawatInap = fetchAll('SELECT ri.*, p.nama_pasien FROM Rawat_Inap ri JOIN Registrasi r ON r.id_registrasi = ri.Registrasi_id_registrasi JOIN Pasien p ON p.id_pasien = r.Pasien_id_pasien ORDER BY ri.id_rawat_inap DESC');
    $resep = fetchAll('SELECT rs.*, p.nama_pasien FROM Resep rs JOIN Rekam_Medis rm ON rm.id_rekam_medis = rs.Rekam_Medis_id_rekam_medis JOIN Registrasi r ON r.id_registrasi = rm.Registrasi_id_registrasi JOIN Pasien p ON p.id_pasien = r.Pasien_id_pasien ORDER BY rs.id_resep DESC');
    requiredSelect($registrasi, 'registrasi');
    requiredSelect($jenis, 'jenis pembayaran');
    ?>
    <section class="header">
        <div>
            <h1>Proses Pembayaran</h1>
            <p>Form ini membuat detail pembayaran lalu memanggil stored procedure proses_pembayaran.</p>
        </div>
        <a class="btn secondary" href="<?= e(url('payments')) ?>">Kembali</a>
    </section>

    <div class="card">
        <form class="form" method="post" action="<?= e(url('payments', ['action' => 'store'])) ?>">
            <div class="form-row-3">
                <label>ID Detail Pembayaran <input name="id_detail_pembayaran" value="<?= e(nextId('Detail_Pembayaran', 'id_detail_pembayaran', 'DP', 3)) ?>" readonly></label>
                <label>ID Pembayaran <input name="id_pembayaran" value="<?= e(nextId('Pembayaran', 'id_pembayaran', 'PY', 3)) ?>" readonly></label>
                <label>Sub Total <input type="number" name="sub_total" min="0" step="0.01" required></label>
            </div>
            <label>Keterangan Biaya <input name="keterangan_biaya" required></label>
            <div class="form-row">
                <label>Registrasi
                    <select name="id_registrasi" required>
                        <?php foreach ($registrasi as $row): ?><option value="<?= e($row['id_registrasi']) ?>"><?= e($row['id_registrasi'] . ' - ' . $row['nama_pasien'] . ' - ' . $row['jenis_layanan']) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <label>Jenis Pembayaran
                    <select name="id_jenis_pembayaran" required>
                        <?php foreach ($jenis as $row): ?><option value="<?= e($row['id_jenis_pembayaran']) ?>"><?= e($row['nama_jenis_pembayaran']) ?></option><?php endforeach; ?>
                    </select>
                </label>
            </div>
            <label>Asuransi Opsional
                <select name="nomor_asuransi">
                    <option value="">Tanpa asuransi</option>
                    <?php foreach ($asuransi as $row): ?><option value="<?= e($row['nomor_asuransi']) ?>"><?= e($row['nama_lembaga_asuransi'] . ' - ' . $row['jenis_asuransi']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <div class="grid grid-3">
                <label>Tindakan Medis Opsional
                    <select name="id_tindakan_medis"><option value="">Tidak ada</option><?php foreach ($tindakan as $row): ?><option value="<?= e($row['id_tindakan_medis']) ?>"><?= e($row['id_tindakan_medis'] . ' - ' . $row['nama_tindakan'] . ' - ' . $row['nama_pasien']) ?></option><?php endforeach; ?></select>
                </label>
                <label>Rawat Inap Opsional
                    <select name="id_rawat_inap"><option value="">Tidak ada</option><?php foreach ($rawatInap as $row): ?><option value="<?= e($row['id_rawat_inap']) ?>"><?= e($row['id_rawat_inap'] . ' - ' . $row['nama_pasien']) ?></option><?php endforeach; ?></select>
                </label>
                <label>Resep Opsional
                    <select name="id_resep"><option value="">Tidak ada</option><?php foreach ($resep as $row): ?><option value="<?= e($row['id_resep']) ?>"><?= e($row['id_resep'] . ' - ' . $row['nama_pasien']) ?></option><?php endforeach; ?></select>
                </label>
            </div>
            <button class="btn" type="submit">Simpan Pembayaran</button>
        </form>
    </div>
    <?php
    return;
}

$rows = fetchAll(
    'SELECT py.*, r.jenis_layanan, p.nama_pasien, jp.nama_jenis_pembayaran, a.nama_lembaga_asuransi, dp.keterangan_biaya
     FROM Pembayaran py
     JOIN Registrasi r ON r.id_registrasi = py.Registrasi_id_registrasi
     JOIN Pasien p ON p.id_pasien = r.Pasien_id_pasien
     JOIN Jenis_Pembayaran jp ON jp.id_jenis_pembayaran = py.Jenis_Pembayaran_id_jenis_pembayaran
     LEFT JOIN Asuransi a ON a.nomor_asuransi = py.Asuransi_nomor_asuransi
     JOIN Detail_Pembayaran dp ON dp.id_detail_pembayaran = py.Detail_Pembayaran_id_detail_pembayaran
     ORDER BY py.tanggal_pembayaran DESC'
);
?>
<section class="header">
    <div>
        <h1>Pembayaran</h1>
        <p>Administrasi transaksi dan biaya layanan pasien.</p>
    </div>
    <a class="btn" href="<?= e(url('payments', ['action' => 'create'])) ?>">Proses Pembayaran</a>
</section>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>ID</th><th>Tanggal</th><th>Pasien</th><th>Layanan</th><th>Keterangan</th><th>Metode</th><th>Asuransi</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['id_pembayaran']) ?></td>
                    <td><?= e($row['tanggal_pembayaran']) ?></td>
                    <td><?= e($row['nama_pasien']) ?></td>
                    <td><?= e($row['jenis_layanan']) ?></td>
                    <td><?= e($row['keterangan_biaya']) ?></td>
                    <td><?= e($row['nama_jenis_pembayaran']) ?></td>
                    <td><?= e($row['nama_lembaga_asuransi'] ?? '-') ?></td>
                    <td><strong><?= e(rupiah($row['total_biaya'])) ?></strong></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="8" class="muted">Belum ada data.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
