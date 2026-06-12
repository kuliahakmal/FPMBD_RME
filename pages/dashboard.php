<?php
$stats = [
    'Pasien' => (int)(fetchOne('SELECT COUNT(*) AS total FROM Pasien')['total'] ?? 0),
    'Registrasi' => (int)(fetchOne('SELECT COUNT(*) AS total FROM Registrasi')['total'] ?? 0),
    'Rawat Jalan' => (int)(fetchOne("SELECT COUNT(*) AS total FROM Registrasi WHERE jenis_layanan = 'Rawat Jalan'")['total'] ?? 0),
    'Rawat Inap' => (int)(fetchOne("SELECT COUNT(*) AS total FROM Registrasi WHERE jenis_layanan = 'Rawat Inap'")['total'] ?? 0),
    'Rekam Medis' => (int)(fetchOne('SELECT COUNT(*) AS total FROM Rekam_Medis')['total'] ?? 0),
    'Kamar Kosong' => (int)(fetchOne("SELECT COUNT(*) AS total FROM Kamar WHERE status_kamar = 'Kosong'")['total'] ?? 0),
];

$latestPatients = fetchAll(
    'SELECT p.*, hitung_umur_pasien(p.id_pasien) AS umur, riwayat_alergi_pasien(p.id_pasien) AS alergi
     FROM Pasien p
     ORDER BY p.id_pasien DESC
     LIMIT 5'
);

$latestMedical = fetchAll(
    'SELECT rm.*, p.nama_pasien, d.nama_dokter
     FROM Rekam_Medis rm
     JOIN Registrasi r ON r.id_registrasi = rm.Registrasi_id_registrasi
     JOIN Pasien p ON p.id_pasien = r.Pasien_id_pasien
     JOIN Dokter d ON d.id_dokter = rm.Dokter_id_dokter
     ORDER BY rm.tanggal_pemeriksaan DESC
     LIMIT 5'
);
?>
<section class="header">
    <div>
        <h1>Dashboard</h1>
        <p>Ringkasan Sistem Rekam Medis Elektronik Rumah Sakit Harapan Bangsa.</p>
    </div>
    <div class="actions">
        <a class="btn" href="<?= e(url('patients', ['action' => 'create'])) ?>">Tambah Pasien</a>
        <a class="btn secondary" href="<?= e(url('medical', ['action' => 'create'])) ?>">Buat Rekam Medis</a>
    </div>
</section>

<div class="grid grid-4 mb">
    <?php foreach ($stats as $label => $value): ?>
        <div class="card stat">
            <div class="num"><?= e((string)$value) ?></div>
            <span><?= e($label) ?></span>
        </div>
    <?php endforeach; ?>
</div>

<div class="grid grid-2">
    <div class="card">
        <h2>Pasien Terbaru</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>ID</th><th>Nama</th><th>Umur</th><th>Alergi</th></tr></thead>
                <tbody>
                <?php foreach ($latestPatients as $row): ?>
                    <tr>
                        <td><?= e($row['id_pasien']) ?></td>
                        <td><?= e($row['nama_pasien']) ?></td>
                        <td><?= e((string)$row['umur']) ?></td>
                        <td><?= e($row['alergi']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$latestPatients): ?><tr><td colspan="4" class="muted">Belum ada data.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h2>Rekam Medis Terbaru</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>ID</th><th>Pasien</th><th>Dokter</th><th>Keluhan</th></tr></thead>
                <tbody>
                <?php foreach ($latestMedical as $row): ?>
                    <tr>
                        <td><?= e($row['id_rekam_medis']) ?></td>
                        <td><?= e($row['nama_pasien']) ?></td>
                        <td><?= e($row['nama_dokter']) ?></td>
                        <td><?= e($row['keluhan_pasien']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$latestMedical): ?><tr><td colspan="4" class="muted">Belum ada data.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
