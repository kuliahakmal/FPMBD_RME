<?php
$logs = fetchAll('SELECT * FROM Log_Audit_Rekam_Medis ORDER BY waktu_perubahan DESC, id_log DESC');
?>
<section class="header">
    <div>
        <h1>Audit Log</h1>
        <p>Log perubahan rekam medis yang dicatat trigger trg_audit_rekam_medis.</p>
    </div>
</section>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Waktu</th><th>User</th><th>ID RM</th><th>Keluhan Lama</th><th>Keluhan Baru</th><th>Dokter</th><th>Perawat</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $row): ?>
                <tr>
                    <td><?= e($row['waktu_perubahan']) ?></td>
                    <td><?= e($row['user_pelaku']) ?></td>
                    <td><?= e($row['id_rekam_medis_lama'] . ' -> ' . $row['id_rekam_medis_baru']) ?></td>
                    <td><?= e($row['keluhan_lama']) ?></td>
                    <td><?= e($row['keluhan_baru']) ?></td>
                    <td><?= e($row['dokter_lama'] . ' -> ' . $row['dokter_baru']) ?></td>
                    <td><?= e($row['perawat_lama'] . ' -> ' . $row['perawat_baru']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$logs): ?><tr><td colspan="7" class="muted">Belum ada audit log. Coba update keluhan pada menu Rekam Medis.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
