<?php
$action = $_GET['action'] ?? 'index';

if ($action === 'store') {
    postOnly();
    try {
        execute('CALL proses_rawat_inap(?, ?, ?, ?)', [
            $_POST['id_rawat_inap'],
            $_POST['tanggal_masuk'],
            $_POST['id_kamar'],
            $_POST['id_registrasi'],
        ]);
        flash('Rawat inap berhasil diproses. Status kamar akan otomatis terisi.');
        redirect('inpatient');
    } catch (Throwable $e) {
        flash($e->getMessage(), 'danger');
        redirect('inpatient', ['action' => 'create']);
    }
}

if ($action === 'checkout') {
    postOnly();
    execute('UPDATE Rawat_Inap SET tanggal_keluar = NOW() WHERE id_rawat_inap = ?', [$_POST['id_rawat_inap']]);
    flash('Pasien keluar rawat inap. Trigger akan mengubah status kamar menjadi kosong.');
    redirect('inpatient');
}

if ($action === 'create') {
    $registrasi = fetchAll(
        'SELECT r.id_registrasi, r.jenis_layanan, p.nama_pasien
         FROM Registrasi r
         JOIN Pasien p ON p.id_pasien = r.Pasien_id_pasien
         LEFT JOIN Rawat_Inap ri ON ri.Registrasi_id_registrasi = r.id_registrasi
         WHERE ri.id_rawat_inap IS NULL
         ORDER BY r.tanggal_registrasi DESC'
    );
    $kamar = fetchAll("SELECT * FROM Kamar WHERE status_kamar = 'Kosong' ORDER BY nomor_kamar");
    requiredSelect($registrasi, 'registrasi');
    requiredSelect($kamar, 'kamar kosong');
    ?>
    <section class="header">
        <div>
            <h1>Proses Rawat Inap</h1>
            <p>Rawat inap dapat dibuat setelah registrasi; procedure akan menetapkan jenis layanan menjadi Rawat Inap.</p>
        </div>
        <a class="btn secondary" href="<?= e(url('inpatient')) ?>">Kembali</a>
    </section>

    <div class="card">
        <form class="form" method="post" action="<?= e(url('inpatient', ['action' => 'store'])) ?>">
            <div class="form-row">
                <label>ID Rawat Inap
                    <input name="id_rawat_inap" value="<?= e(nextId('Rawat_Inap', 'id_rawat_inap', 'RI', 3)) ?>" readonly>
                </label>
                <label>Tanggal Masuk
                    <input type="datetime-local" name="tanggal_masuk" value="<?= e(date('Y-m-d\TH:i')) ?>" required>
                </label>
            </div>
            <div class="form-row">
                <label>Registrasi
                    <select name="id_registrasi" required>
                        <?php foreach ($registrasi as $row): ?><option value="<?= e($row['id_registrasi']) ?>"><?= e($row['id_registrasi'] . ' - ' . $row['nama_pasien'] . ' - ' . $row['jenis_layanan']) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <label>Kamar Kosong
                    <select name="id_kamar" required>
                        <?php foreach ($kamar as $row): ?><option value="<?= e($row['id_kamar']) ?>"><?= e('Kamar ' . $row['nomor_kamar'] . ' - ' . $row['tipe_kamar']) ?></option><?php endforeach; ?>
                    </select>
                </label>
            </div>
            <button class="btn" type="submit">Simpan Rawat Inap</button>
        </form>
    </div>
    <?php
    return;
}

$rows = fetchAll(
    'SELECT ri.*, p.nama_pasien, k.nomor_kamar, k.tipe_kamar, cek_ketersediaan_kamar(k.id_kamar) AS status_kamar
     FROM Rawat_Inap ri
     JOIN Registrasi r ON r.id_registrasi = ri.Registrasi_id_registrasi
     JOIN Pasien p ON p.id_pasien = r.Pasien_id_pasien
     JOIN Kamar k ON k.id_kamar = ri.Kamar_id_kamar
     ORDER BY ri.tanggal_masuk DESC'
);
?>
<section class="header">
    <div>
        <h1>Rawat Inap</h1>
        <p>Mengelola pasien yang menjalani rawat inap dan status kamar.</p>
    </div>
    <a class="btn" href="<?= e(url('inpatient', ['action' => 'create'])) ?>">Proses Rawat Inap</a>
</section>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>ID</th><th>Pasien</th><th>Kamar</th><th>Masuk</th><th>Keluar</th><th>Status Kamar</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['id_rawat_inap']) ?></td>
                    <td><?= e($row['nama_pasien']) ?></td>
                    <td><?= e($row['nomor_kamar'] . ' - ' . $row['tipe_kamar']) ?></td>
                    <td><?= e($row['tanggal_masuk']) ?></td>
                    <td><?= e($row['tanggal_keluar'] ?? '-') ?></td>
                    <td><span class="badge <?= $row['status_kamar'] === 'Kosong' ? 'success' : 'warning' ?>"><?= e($row['status_kamar']) ?></span></td>
                    <td>
                        <?php if ($row['tanggal_keluar'] === null): ?>
                            <form method="post" action="<?= e(url('inpatient', ['action' => 'checkout'])) ?>">
                                <input type="hidden" name="id_rawat_inap" value="<?= e($row['id_rawat_inap']) ?>">
                                <button class="btn secondary" type="submit">Selesai</button>
                            </form>
                        <?php else: ?>
                            <span class="muted">Selesai</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="7" class="muted">Belum ada data.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
