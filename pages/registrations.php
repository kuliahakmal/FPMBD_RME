<?php
$action = $_GET['action'] ?? 'index';

if ($action === 'store') {
    postOnly();

    try {
        execute(
            'INSERT INTO Registrasi (id_registrasi, tanggal_registrasi, status_registrasi, jenis_layanan, Pasien_id_pasien, Poliklinik_id_poliklinik) 
             VALUES (?, NOW(), ?, ?, ?, ?)',
            [
                $_POST['id_registrasi'],
                'Terdaftar',
                $_POST['jenis_layanan'],
                $_POST['id_pasien'],
                $_POST['id_poliklinik']
            ]
        );

        flash('Kunjungan / Registrasi baru berhasil dibuat.');
        redirect('registrations');
    } catch (Throwable $e) {
        flash($e->getMessage(), 'danger');
        redirect('registrations', ['action' => 'create', 'patient_id' => $_POST['id_pasien'] ?? '']);
    }
}

if ($action === 'create') {
    $clinics = fetchAll('SELECT * FROM Poliklinik ORDER BY nama_poliklinik');
    requiredSelect($clinics, 'poliklinik');
    
    $patients = fetchAll('SELECT id_pasien, nama_pasien FROM Pasien ORDER BY id_pasien DESC');
    requiredSelect($patients, 'pasien');

    $registrationId = nextId('Registrasi', 'id_registrasi', 'R', 4);
    $selectedPatientId = $_GET['patient_id'] ?? '';
    ?>

    <section class="header">
        <div>
            <h1>Registrasi Pasien Lama</h1>
            <p>Mendaftarkan kunjungan baru untuk pasien yang sudah terdaftar di sistem.</p>
        </div>
        <a class="btn secondary" href="<?= e(url('registrations')) ?>">Kembali</a>
    </section>

    <div class="card">
        <form class="form" method="post" action="<?= e(url('registrations', ['action' => 'store'])) ?>">
            <div class="form-row-3">
                <label>ID Registrasi
                    <input name="id_registrasi" value="<?= e($registrationId) ?>" readonly>
                </label>

                <label>Pasien
                    <select name="id_pasien" required>
                        <option value="">-- Pilih Pasien --</option>
                        <?php foreach ($patients as $row): ?>
                            <option value="<?= e($row['id_pasien']) ?>" <?= selected($selectedPatientId, $row['id_pasien']) ?>>
                                <?= e($row['id_pasien'] . ' - ' . $row['nama_pasien']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>Poliklinik
                    <select name="id_poliklinik" required>
                        <?php foreach ($clinics as $row): ?>
                            <option value="<?= e($row['id_poliklinik']) ?>">
                                <?= e($row['nama_poliklinik']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <div class="form-row">
                <label>Jenis Layanan
                    <select name="jenis_layanan" required>
                        <option value="Rawat Jalan" selected>Rawat Jalan</option>
                        <option value="Rawat Inap">Rawat Inap</option>
                    </select>
                </label>
            </div>

            <div class="actions">
                <button class="btn" type="submit">Daftar Kunjungan</button>
            </div>
        </form>
    </div>

    <?php
    return;
}

// Index Action
$rows = fetchAll(
    'SELECT r.*, p.nama_pasien, pl.nama_poliklinik
     FROM Registrasi r
     JOIN Pasien p ON p.id_pasien = r.Pasien_id_pasien
     JOIN Poliklinik pl ON pl.id_poliklinik = r.Poliklinik_id_poliklinik
     ORDER BY r.tanggal_registrasi DESC'
);
?>
<section class="header">
    <div>
        <h1>Registrasi Kunjungan</h1>
        <p>Mencatat kedatangan pasien ke rumah sakit dan unit layanan.</p>
    </div>
    <div style="display: flex; gap: 8px;">
        <a class="btn secondary" href="<?= e(url('registrations', ['action' => 'create'])) ?>">Pasien Lama</a>
        <a class="btn" href="<?= e(url('patients', ['action' => 'create'])) ?>">Pasien Baru</a>
    </div>
</section>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>ID</th><th>Tanggal</th><th>Pasien</th><th>Poliklinik</th><th>Jenis Layanan</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['id_registrasi']) ?></td>
                    <td><?= e($row['tanggal_registrasi']) ?></td>
                    <td><?= e($row['nama_pasien']) ?></td>
                    <td><?= e($row['nama_poliklinik']) ?></td>
                    <td><span class="badge <?= $row['jenis_layanan'] === 'Rawat Inap' ? 'warning' : 'success' ?>"><?= e($row['jenis_layanan']) ?></span></td>
                    <td><span class="badge"><?= e($row['status_registrasi']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="6" class="muted">Belum ada data.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
