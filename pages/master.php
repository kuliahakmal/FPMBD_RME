<?php
$action = $_GET['action'] ?? 'index';

if ($action === 'store') {
    postOnly();
    $type = $_POST['type'] ?? '';
    try {
        switch ($type) {
            case 'alergi':
                execute('INSERT INTO Alergi (id_alergi, nama_alergi, kategori_alergi, keterangan_alergi) VALUES (?, ?, ?, ?)', [$_POST['id'], $_POST['nama'], $_POST['kategori'], $_POST['keterangan']]);
                break;
            case 'poliklinik':
                execute('INSERT INTO Poliklinik (id_poliklinik, nama_poliklinik, lokasi_poliklinik) VALUES (?, ?, ?)', [$_POST['id'], $_POST['nama'], $_POST['lokasi']]);
                break;
            case 'dokter':
                execute('INSERT INTO Dokter (id_dokter, nama_dokter, nomor_telepon_dokter, spesialisasi_dokter) VALUES (?, ?, ?, ?)', [$_POST['id'], $_POST['nama'], $_POST['telepon'], $_POST['spesialisasi']]);
                break;
            case 'perawat':
                execute('INSERT INTO Perawat (id_perawat, nama_perawat, nomor_telepon_perawat) VALUES (?, ?, ?)', [$_POST['id'], $_POST['nama'], $_POST['telepon']]);
                break;
            case 'kamar':
                execute('INSERT INTO Kamar (id_kamar, nomor_kamar, tipe_kamar, status_kamar) VALUES (?, ?, ?, ?)', [$_POST['id'], $_POST['nomor'], $_POST['tipe'], $_POST['status']]);
                break;
            case 'obat':
                execute('INSERT INTO Obat (id_obat, nama_obat, stok_obat, harga_obat) VALUES (?, ?, ?, ?)', [$_POST['id'], $_POST['nama'], $_POST['stok'], $_POST['harga']]);
                break;
            case 'jenis_pembayaran':
                execute('INSERT INTO Jenis_Pembayaran (id_jenis_pembayaran, nama_jenis_pembayaran) VALUES (?, ?)', [$_POST['id'], $_POST['nama']]);
                break;
            case 'asuransi':
                execute('INSERT INTO Asuransi (nomor_asuransi, nama_lembaga_asuransi, jenis_asuransi) VALUES (?, ?, ?)', [$_POST['nomor'], $_POST['nama'], $_POST['jenis']]);
                break;
            case 'shift':
                execute('INSERT INTO Shift (id_shift, Jenis_Shift, Jam_Masuk, Jam_Selesai) VALUES (?, ?, ?, ?)', [$_POST['id'], $_POST['jenis'], $_POST['masuk'], $_POST['selesai']]);
                break;
            default:
                throw new RuntimeException('Jenis master data tidak valid.');
        }
        flash('Master data berhasil ditambahkan.');
    } catch (Throwable $e) {
        flash($e->getMessage(), 'danger');
    }
    redirect('master');
}

$alergi = fetchAll('SELECT * FROM Alergi ORDER BY id_alergi DESC');
$poli = fetchAll('SELECT * FROM Poliklinik ORDER BY id_poliklinik DESC');
$dokter = fetchAll('SELECT * FROM Dokter ORDER BY id_dokter DESC');
$perawat = fetchAll('SELECT * FROM Perawat ORDER BY id_perawat DESC');
$kamar = fetchAll('SELECT * FROM Kamar ORDER BY nomor_kamar');
$obat = fetchAll('SELECT * FROM Obat ORDER BY id_obat DESC');
$jenis = fetchAll('SELECT * FROM Jenis_Pembayaran ORDER BY id_jenis_pembayaran DESC');
$asuransi = fetchAll('SELECT * FROM Asuransi ORDER BY nomor_asuransi DESC');
$shift = fetchAll('SELECT * FROM Shift ORDER BY id_shift DESC');
?>
<section class="header">
    <div>
        <h1>Master Data</h1>
        <p>Data pendukung untuk pasien, layanan, tenaga medis, farmasi, dan pembayaran.</p>
    </div>
</section>

<div class="grid grid-2">
    <div class="card">
        <h2>Tambah Alergi</h2>
        <form class="form" method="post" action="<?= e(url('master', ['action' => 'store'])) ?>">
            <input type="hidden" name="type" value="alergi">
            <div class="form-row"><label>ID <input name="id" value="<?= e(nextId('Alergi', 'id_alergi', 'A', 4)) ?>" required></label><label>Nama Alergen <input name="nama" required></label></div>
            <label>Kategori <select name="kategori" required><option>Obat</option><option>Makanan</option><option>Lingkungan</option><option>Lainnya</option></select></label>
            <label>Keterangan <input name="keterangan" required></label>
            <button class="btn" type="submit">Simpan</button>
        </form>
    </div>
    <div class="card">
        <h2>Tambah Poliklinik</h2>
        <form class="form" method="post" action="<?= e(url('master', ['action' => 'store'])) ?>">
            <input type="hidden" name="type" value="poliklinik">
            <div class="form-row"><label>ID <input name="id" value="<?= e(nextId('Poliklinik', 'id_poliklinik', 'P', 4)) ?>" required></label><label>Nama <input name="nama" required></label></div>
            <label>Lokasi <input name="lokasi" required></label>
            <button class="btn" type="submit">Simpan</button>
        </form>
    </div>
    <div class="card">
        <h2>Tambah Dokter</h2>
        <form class="form" method="post" action="<?= e(url('master', ['action' => 'store'])) ?>">
            <input type="hidden" name="type" value="dokter">
            <div class="form-row"><label>ID <input name="id" value="<?= e(nextId('Dokter', 'id_dokter', 'D', 4)) ?>" required></label><label>Nama <input name="nama" required></label></div>
            <div class="form-row"><label>Telepon <input name="telepon" required></label><label>Spesialisasi <input name="spesialisasi" required></label></div>
            <button class="btn" type="submit">Simpan</button>
        </form>
    </div>
    <div class="card">
        <h2>Tambah Perawat</h2>
        <form class="form" method="post" action="<?= e(url('master', ['action' => 'store'])) ?>">
            <input type="hidden" name="type" value="perawat">
            <div class="form-row"><label>ID <input name="id" value="<?= e(nextId('Perawat', 'id_perawat', 'N', 4)) ?>" required></label><label>Nama <input name="nama" required></label></div>
            <label>Telepon <input name="telepon" required></label>
            <button class="btn" type="submit">Simpan</button>
        </form>
    </div>
    <div class="card">
        <h2>Tambah Kamar</h2>
        <form class="form" method="post" action="<?= e(url('master', ['action' => 'store'])) ?>">
            <input type="hidden" name="type" value="kamar">
            <div class="form-row"><label>ID <input name="id" value="<?= e(nextId('Kamar', 'id_kamar', 'K', 4)) ?>" required></label><label>Nomor <input type="number" name="nomor" required></label></div>
            <div class="form-row"><label>Tipe <input name="tipe" required></label><label>Status <select name="status"><option>Kosong</option><option>Terisi</option></select></label></div>
            <button class="btn" type="submit">Simpan</button>
        </form>
    </div>
    <div class="card">
        <h2>Tambah Obat</h2>
        <form class="form" method="post" action="<?= e(url('master', ['action' => 'store'])) ?>">
            <input type="hidden" name="type" value="obat">
            <div class="form-row"><label>ID <input name="id" value="<?= e(nextId('Obat', 'id_obat', 'O', 4)) ?>" required></label><label>Nama <input name="nama" required></label></div>
            <div class="form-row"><label>Stok <input type="number" name="stok" min="0" required></label><label>Harga <input type="number" name="harga" min="0" step="0.01" required></label></div>
            <button class="btn" type="submit">Simpan</button>
        </form>
    </div>
    <div class="card">
        <h2>Tambah Jenis Pembayaran</h2>
        <form class="form" method="post" action="<?= e(url('master', ['action' => 'store'])) ?>">
            <input type="hidden" name="type" value="jenis_pembayaran">
            <div class="form-row"><label>ID <input name="id" value="<?= e(nextId('Jenis_Pembayaran', 'id_jenis_pembayaran', 'JP', 3)) ?>" required></label><label>Nama <input name="nama" required></label></div>
            <button class="btn" type="submit">Simpan</button>
        </form>
    </div>
    <div class="card">
        <h2>Tambah Asuransi</h2>
        <form class="form" method="post" action="<?= e(url('master', ['action' => 'store'])) ?>">
            <input type="hidden" name="type" value="asuransi">
            <div class="form-row"><label>Nomor <input name="nomor" maxlength="13" required></label><label>Nama Lembaga <input name="nama" required></label></div>
            <label>Jenis <input name="jenis" required></label>
            <button class="btn" type="submit">Simpan</button>
        </form>
    </div>
    <div class="card">
        <h2>Tambah Shift</h2>
        <form class="form" method="post" action="<?= e(url('master', ['action' => 'store'])) ?>">
            <input type="hidden" name="type" value="shift">
            <div class="form-row"><label>ID <input type="number" name="id" value="<?= e((string)((int)(fetchOne('SELECT COALESCE(MAX(id_shift), 0) + 1 AS id FROM Shift')['id'] ?? 1))) ?>" required></label><label>Jenis <input name="jenis" required></label></div>
            <div class="form-row"><label>Jam Masuk <input type="time" name="masuk" required></label><label>Jam Selesai <input type="time" name="selesai" required></label></div>
            <button class="btn" type="submit">Simpan</button>
        </form>
    </div>
</div>

<div class="grid grid-2 mt">
<?php
$tables = [
    'Master Alergi' => [$alergi, ['id_alergi', 'nama_alergi', 'kategori_alergi', 'keterangan_alergi']],
    'Poliklinik' => [$poli, ['id_poliklinik', 'nama_poliklinik', 'lokasi_poliklinik']],
    'Dokter' => [$dokter, ['id_dokter', 'nama_dokter', 'spesialisasi_dokter']],
    'Perawat' => [$perawat, ['id_perawat', 'nama_perawat', 'nomor_telepon_perawat']],
    'Kamar' => [$kamar, ['id_kamar', 'nomor_kamar', 'tipe_kamar', 'status_kamar']],
    'Obat' => [$obat, ['id_obat', 'nama_obat', 'stok_obat', 'harga_obat']],
    'Jenis Pembayaran' => [$jenis, ['id_jenis_pembayaran', 'nama_jenis_pembayaran']],
    'Asuransi' => [$asuransi, ['nomor_asuransi', 'nama_lembaga_asuransi', 'jenis_asuransi']],
    'Shift' => [$shift, ['id_shift', 'Jenis_Shift', 'Jam_Masuk', 'Jam_Selesai']],
];
foreach ($tables as $title => [$rows, $cols]):
?>
    <div class="card">
        <h3><?= e($title) ?></h3>
        <div class="table-wrap">
            <table>
                <thead><tr><?php foreach ($cols as $col): ?><th><?= e($col) ?></th><?php endforeach; ?></tr></thead>
                <tbody>
                <?php foreach (array_slice($rows, 0, 8) as $row): ?>
                    <tr><?php foreach ($cols as $col): ?><td><?= e((string)($row[$col] ?? '-')) ?></td><?php endforeach; ?></tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?><tr><td colspan="<?= count($cols) ?>" class="muted">Belum ada data.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; ?>
</div>
