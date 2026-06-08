<?php
$action = $_GET['action'] ?? 'index';

if ($action === 'store') {
    postOnly();
    try {
        execute('CALL tambah_jadwal_jaga(?, ?, ?, ?, ?)', [
            $_POST['id_jadwal'],
            $_POST['tanggal_jaga'],
            $_POST['id_perawat'],
            $_POST['id_dokter'],
            $_POST['id_shift'],
        ]);
        flash('Jadwal jaga berhasil ditambahkan.');
    } catch (Throwable $e) {
        flash($e->getMessage(), 'danger');
    }
    redirect('schedule');
}

$dokter = fetchAll('SELECT * FROM Dokter ORDER BY nama_dokter');
$perawat = fetchAll('SELECT * FROM Perawat ORDER BY nama_perawat');
$shift = fetchAll('SELECT * FROM Shift ORDER BY id_shift');
$rows = fetchAll(
    'SELECT jj.*, d.nama_dokter, p.nama_perawat, s.Jenis_Shift, s.Jam_Masuk, s.Jam_Selesai
     FROM Jadwal_Jaga jj
     JOIN Dokter d ON d.id_dokter = jj.Dokter_id_dokter
     JOIN Perawat p ON p.id_perawat = jj.Perawat_id_perawat
     JOIN Shift s ON s.id_shift = jj.Shift_id_shift
     ORDER BY jj.tanggal_jaga DESC, s.Jam_Masuk'
);
?>
<section class="header">
    <div>
        <h1>Jadwal Jaga</h1>
        <p>Validasi bentrok dokter dan perawat diproses oleh stored procedure.</p>
    </div>
</section>

<div class="card mb">
    <h2>Tambah Jadwal</h2>
    <form class="form" method="post" action="<?= e(url('schedule', ['action' => 'store'])) ?>">
        <div class="form-row-3">
            <label>ID Jadwal <input name="id_jadwal" value="<?= e(nextId('Jadwal_Jaga', 'id_jadwal', 'J', 4)) ?>" required></label>
            <label>Tanggal <input type="date" name="tanggal_jaga" required></label>
            <label>Shift
                <select name="id_shift" required>
                    <?php foreach ($shift as $row): ?><option value="<?= e((string)$row['id_shift']) ?>"><?= e($row['Jenis_Shift'] . ' (' . $row['Jam_Masuk'] . ' - ' . $row['Jam_Selesai'] . ')') ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="form-row">
            <label>Dokter
                <select name="id_dokter" required>
                    <?php foreach ($dokter as $row): ?><option value="<?= e($row['id_dokter']) ?>"><?= e($row['nama_dokter']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Perawat
                <select name="id_perawat" required>
                    <?php foreach ($perawat as $row): ?><option value="<?= e($row['id_perawat']) ?>"><?= e($row['nama_perawat']) ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <button class="btn" type="submit">Simpan Jadwal</button>
    </form>
</div>

<div class="card">
    <h2>Daftar Jadwal</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>ID</th><th>Tanggal</th><th>Shift</th><th>Dokter</th><th>Perawat</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['id_jadwal']) ?></td>
                    <td><?= e($row['tanggal_jaga']) ?></td>
                    <td><?= e($row['Jenis_Shift']) ?></td>
                    <td><?= e($row['nama_dokter']) ?></td>
                    <td><?= e($row['nama_perawat']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="5" class="muted">Belum ada data.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
