<?php
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
        <h1>Registrasi</h1>
        <p>Mencatat kedatangan pasien ke rumah sakit dan unit layanan.</p>
    </div>
    <a class="btn" href="<?= e(url('patients', ['action' => 'create'])) ?>">Registrasi Pasien Baru</a>
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
