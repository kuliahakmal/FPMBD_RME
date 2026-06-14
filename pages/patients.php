<?php
$action = $_GET['action'] ?? 'index';

function allergyHistoryJson(array $input): string
{
    $history = [];
    $allowedSeverity = ['Ringan', 'Sedang', 'Berat'];
    $allowedStatus = ['Aktif', 'Tidak Aktif'];

    foreach ($input as $idAllergy => $row) {
        if (!isset($row['selected'])) {
            continue;
        }

        $reaction = trim((string)($row['reaksi'] ?? ''));
        $knownDate = trim((string)($row['tanggal_diketahui'] ?? ''));
        $severity = (string)($row['keparahan'] ?? '');
        $status = (string)($row['status'] ?? '');
        $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $knownDate);

        if (
            $reaction === ''
            || !$parsedDate
            || $parsedDate->format('Y-m-d') !== $knownDate
            || $knownDate > date('Y-m-d')
            || !in_array($severity, $allowedSeverity, true)
            || !in_array($status, $allowedStatus, true)
        ) {
            throw new RuntimeException('Setiap alergi yang dipilih wajib memiliki reaksi, tingkat keparahan, tanggal diketahui, dan status yang valid.');
        }

        $history[] = [
            'id_alergi' => (string)$idAllergy,
            'reaksi' => $reaction,
            'keparahan' => $severity,
            'tanggal_diketahui' => $knownDate,
            'status' => $status,
            'catatan' => trim((string)($row['catatan'] ?? '')) ?: null,
        ];
    }

    return json_encode($history, JSON_THROW_ON_ERROR);
}

if ($action === 'store') {
    postOnly();

    try {
        execute('CALL registrasi_pasien_baru(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            $_POST['id_pasien'],
            $_POST['nama_pasien'],
            $_POST['nomor_telepon_pasien'],
            $_POST['alamat_pasien'],
            $_POST['tanggal_lahir_pasien'],
            $_POST['jenis_kelamin_pasien'],
            allergyHistoryJson($_POST['alergi'] ?? []),
            $_POST['id_registrasi'],
            $_POST['id_poliklinik'],
            $_POST['jenis_layanan'] ?? 'Rawat Jalan',
        ]);

        flash('Pasien, registrasi, dan riwayat alergi berhasil dibuat.');
        redirect('patients');
    } catch (Throwable $e) {
        flash($e->getMessage(), 'danger');
        redirect('patients', ['action' => 'create']);
    }
}

if ($action === 'update') {
    postOnly();

    try {
        execute('CALL perbarui_pasien_dan_alergi(?, ?, ?, ?, ?, ?, ?)', [
            $_POST['id_pasien'],
            $_POST['nama_pasien'],
            $_POST['nomor_telepon_pasien'],
            $_POST['alamat_pasien'],
            $_POST['tanggal_lahir_pasien'],
            $_POST['jenis_kelamin_pasien'],
            allergyHistoryJson($_POST['alergi'] ?? []),
        ]);

        flash('Data pasien dan riwayat alergi berhasil diperbarui.');
        redirect('patients');
    } catch (Throwable $e) {
        flash($e->getMessage(), 'danger');
        redirect('patients', ['action' => 'edit', 'id' => $_POST['id_pasien']]);
    }
}

if ($action === 'create' || $action === 'edit') {
    $allergies = fetchAll('SELECT * FROM Alergi ORDER BY kategori_alergi, nama_alergi');
    $clinics = fetchAll('SELECT * FROM Poliklinik ORDER BY nama_poliklinik');
    requiredSelect($clinics, 'poliklinik');

    $allergyMap = [];
    foreach ($allergies as $row) {
        $allergyMap[$row['id_alergi']] = $row;
    }

    $patient = null;
    $historyByAllergy = [];

    if ($action === 'edit') {
        $patient = fetchOne('SELECT * FROM Pasien WHERE id_pasien = ?', [$_GET['id'] ?? '']);

        if (!$patient) {
            throw new RuntimeException('Pasien tidak ditemukan.');
        }

        foreach (fetchAll('SELECT * FROM Riwayat_Alergi WHERE Pasien_id_pasien = ?', [$patient['id_pasien']]) as $row) {
            $historyByAllergy[$row['Alergi_id_alergi']] = $row;
        }
    }

    $patientId = $patient['id_pasien'] ?? nextId('Pasien', 'id_pasien', 'PS', 3);
    $registrationId = nextId('Registrasi', 'id_registrasi', 'R', 4);
    ?>

    <section class="header">
        <div>
            <h1><?= $action === 'create' ? 'Tambah Pasien' : 'Edit Pasien' ?></h1>
            <p>Pasien baru langsung dibuatkan registrasi dengan jenis layanan per kunjungan.</p>
        </div>
        <a class="btn secondary" href="<?= e(url('patients')) ?>">Kembali</a>
    </section>

    <div class="card">
        <form class="form" method="post" action="<?= e(url('patients', ['action' => $action === 'create' ? 'store' : 'update'])) ?>">
            <div class="form-row-3">
                <label>ID Pasien
                    <input name="id_pasien" value="<?= e($patientId) ?>" readonly>
                </label>

                <?php if ($action === 'create'): ?>
                    <label>ID Registrasi
                        <input name="id_registrasi" value="<?= e($registrationId) ?>" readonly>
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

                    <label>Jenis Layanan
                        <select name="jenis_layanan" required>
                            <option value="Rawat Jalan" selected>Rawat Jalan</option>
                            <option value="Rawat Inap">Rawat Inap</option>
                        </select>
                    </label>
                <?php endif; ?>
            </div>

            <div class="form-row">
                <label>Nama Pasien
                    <input name="nama_pasien" value="<?= e($patient['nama_pasien'] ?? '') ?>" required>
                </label>

                <label>No Telepon
                    <input name="nomor_telepon_pasien" value="<?= e($patient['nomor_telepon_pasien'] ?? '') ?>" required>
                </label>
            </div>

            <div class="form-row">
                <label>Tanggal Lahir
                    <input type="date" name="tanggal_lahir_pasien" value="<?= e($patient['tanggal_lahir_pasien'] ?? '') ?>" max="<?= e(date('Y-m-d')) ?>" required>
                </label>

                <label>Jenis Kelamin
                    <select name="jenis_kelamin_pasien" required>
                        <option value="L" <?= selected($patient['jenis_kelamin_pasien'] ?? '', 'L') ?>>Laki-laki</option>
                        <option value="P" <?= selected($patient['jenis_kelamin_pasien'] ?? '', 'P') ?>>Perempuan</option>
                    </select>
                </label>
            </div>

            <label>Alamat
                <textarea name="alamat_pasien" required><?= e($patient['alamat_pasien'] ?? '') ?></textarea>
            </label>

            <div>
                <h2>Riwayat Alergi</h2>
                <p class="muted small">
                    Pilih alergi dari daftar, lalu klik Tambah. Kosongkan bila pasien tidak memiliki riwayat alergi.
                </p>

                <?php if (!$allergies): ?>
                    <div class="alert alert-warning">Master alergi masih kosong. Pasien tetap dapat disimpan tanpa alergi.</div>
                <?php else: ?>
                        <div class="allergy-toolbar">
                            <div class="allergy-search">
                                <input
                                    id="allergySearchInput"
                                    type="text"
                                    placeholder="Cari alergi, contoh: parasetamol, kacang, debu..."
                                    autocomplete="off"
                                >
                                <input id="selectedAllergyId" type="hidden">
                                <div id="allergySuggestions" class="allergy-suggestions"></div>
                            </div>

                            <button class="btn" type="button" id="addAllergyBtn">Tambah</button>
                        </div>
                <?php endif; ?>

                <div class="allergy-list" id="selectedAllergies">
                    <?php foreach ($historyByAllergy as $idAllergy => $history):
                        $allergy = $allergyMap[$idAllergy] ?? null;
                        if (!$allergy) {
                            continue;
                        }

                        $field = 'alergi[' . $idAllergy . ']';
                    ?>
                        <div class="allergy-item compact" data-allergy-id="<?= e($idAllergy) ?>">
                            <div class="allergy-head">
                                <div>
                                    <strong><?= e($allergy['nama_alergi']) ?></strong>
                                    <div class="muted small">
                                        <?= e($allergy['kategori_alergi']) ?> - <?= e($allergy['keterangan_alergi'] ?? '') ?>
                                    </div>
                                </div>

                                <button class="btn secondary remove-allergy" type="button">Hapus</button>
                            </div>

                            <input type="hidden" name="<?= e($field) ?>[selected]" value="1">

                            <div class="form-row-3">
                                <label>Reaksi
                                    <input name="<?= e($field) ?>[reaksi]" value="<?= e($history['reaksi_alergi'] ?? '') ?>" placeholder="Contoh: gatal dan sesak" required>
                                </label>

                                <label>Keparahan
                                    <select name="<?= e($field) ?>[keparahan]" required>
                                        <?php foreach (['Ringan', 'Sedang', 'Berat'] as $severity): ?>
                                            <option <?= selected($history['tingkat_keparahan'] ?? 'Ringan', $severity) ?>>
                                                <?= e($severity) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>

                                <label>Tanggal Diketahui
                                    <input type="date" name="<?= e($field) ?>[tanggal_diketahui]" value="<?= e($history['tanggal_diketahui'] ?? date('Y-m-d')) ?>" max="<?= e(date('Y-m-d')) ?>" required>
                                </label>
                            </div>

                            <div class="form-row">
                                <label>Status
                                    <select name="<?= e($field) ?>[status]" required>
                                        <option <?= selected($history['status_alergi'] ?? 'Aktif', 'Aktif') ?>>Aktif</option>
                                        <option <?= selected($history['status_alergi'] ?? '', 'Tidak Aktif') ?>>Tidak Aktif</option>
                                    </select>
                                </label>

                                <label>Catatan
                                    <input name="<?= e($field) ?>[catatan]" value="<?= e($history['catatan'] ?? '') ?>" placeholder="Opsional">
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="actions">
                <button class="btn" type="submit">Simpan</button>
            </div>
        </form>
    </div>

    <script>
    const allergies = <?= json_encode($allergies, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    const selectedAllergies = document.getElementById('selectedAllergies');
    const allergySearchInput = document.getElementById('allergySearchInput');
    const selectedAllergyId = document.getElementById('selectedAllergyId');
    const allergySuggestions = document.getElementById('allergySuggestions');
    const addAllergyBtn = document.getElementById('addAllergyBtn');

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function allergyExists(id) {
        return [...selectedAllergies.querySelectorAll('.allergy-item')]
            .some(item => item.dataset.allergyId === id);
    }

    function renderAllergySuggestions(keyword = '') {
    if (!allergySuggestions) {
        return;
    }

    const normalizedKeyword = keyword.trim().toLowerCase();

    if (normalizedKeyword === '') {
        allergySuggestions.innerHTML = '';
        allergySuggestions.style.display = 'none';
        return;
    }

    const filteredAllergies = allergies
        .filter(allergy => {
            const text = [
                allergy.id_alergi,
                allergy.nama_alergi,
                allergy.kategori_alergi,
                allergy.keterangan_alergi ?? ''
            ].join(' ').toLowerCase();

            return text.includes(normalizedKeyword);
        })
        .filter(allergy => !allergyExists(allergy.id_alergi))
        .slice(0, 8);

    if (filteredAllergies.length === 0) {
        allergySuggestions.innerHTML = `
            <div class="allergy-suggestion empty">
                Tidak ada alergi yang cocok.
            </div>
        `;
        allergySuggestions.style.display = 'block';
        return;
    }

    allergySuggestions.innerHTML = filteredAllergies.map(allergy => `
        <button
            class="allergy-suggestion"
            type="button"
            data-id="${escapeHtml(allergy.id_alergi)}"
        >
            <strong>${escapeHtml(allergy.nama_alergi)}</strong>
            <span>${escapeHtml(allergy.kategori_alergi)} - ${escapeHtml(allergy.keterangan_alergi ?? '')}</span>
        </button>
    `).join('');

    allergySuggestions.style.display = 'block';
    }

if (allergySearchInput) {
    allergySearchInput.addEventListener('input', () => {
        selectedAllergyId.value = '';
        renderAllergySuggestions(allergySearchInput.value);
    });

    allergySearchInput.addEventListener('focus', () => {
        renderAllergySuggestions(allergySearchInput.value);
    });

    allergySearchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
        }
    });
}

if (allergySuggestions) {
    allergySuggestions.addEventListener('click', (event) => {
        const button = event.target.closest('.allergy-suggestion');

        if (!button || button.classList.contains('empty')) {
            return;
        }

        const id = button.dataset.id;
        const allergy = allergies.find(item => item.id_alergi === id);

        if (!allergy) {
            return;
        }

        selectedAllergyId.value = id;
        allergySearchInput.value = `${allergy.nama_alergi} - ${allergy.kategori_alergi}`;
        allergySuggestions.innerHTML = '';
        allergySuggestions.style.display = 'none';
    });
}

document.addEventListener('click', (event) => {
    if (
        allergySuggestions
        && allergySearchInput
        && !event.target.closest('.allergy-search')
    ) {
        allergySuggestions.innerHTML = '';
        allergySuggestions.style.display = 'none';
    }
});

    function allergyCard(allergy) {
        const id = allergy.id_alergi;
        const today = '<?= e(date('Y-m-d')) ?>';
        const field = `alergi[${id}]`;

        return `
            <div class="allergy-item compact" data-allergy-id="${escapeHtml(id)}">
                <div class="allergy-head">
                    <div>
                        <strong>${escapeHtml(allergy.nama_alergi)}</strong>
                        <div class="muted small">
                            ${escapeHtml(allergy.kategori_alergi)} - ${escapeHtml(allergy.keterangan_alergi ?? '')}
                        </div>
                    </div>

                    <button class="btn secondary remove-allergy" type="button">Hapus</button>
                </div>

                <input type="hidden" name="${field}[selected]" value="1">

                <div class="form-row-3">
                    <label>Reaksi
                        <input name="${field}[reaksi]" placeholder="Contoh: gatal dan sesak" required>
                    </label>

                    <label>Keparahan
                        <select name="${field}[keparahan]" required>
                            <option>Ringan</option>
                            <option>Sedang</option>
                            <option>Berat</option>
                        </select>
                    </label>

                    <label>Tanggal Diketahui
                        <input type="date" name="${field}[tanggal_diketahui]" value="${today}" max="${today}" required>
                    </label>
                </div>

                <div class="form-row">
                    <label>Status
                        <select name="${field}[status]" required>
                            <option>Aktif</option>
                            <option>Tidak Aktif</option>
                        </select>
                    </label>

                    <label>Catatan
                        <input name="${field}[catatan]" placeholder="Opsional">
                    </label>
                </div>
            </div>
        `;
    }

if (addAllergyBtn) {
    addAllergyBtn.addEventListener('click', () => {
        const id = selectedAllergyId.value;

        if (!id) {
            alert('Cari dan pilih alergi terlebih dahulu.');
            return;
        }

        if (allergyExists(id)) {
            alert('Alergi ini sudah ditambahkan.');
            return;
        }

        const allergy = allergies.find(item => item.id_alergi === id);

        if (!allergy) {
            alert('Alergi tidak ditemukan.');
            return;
        }

        selectedAllergies.insertAdjacentHTML('beforeend', allergyCard(allergy));

        selectedAllergyId.value = '';
        allergySearchInput.value = '';
        allergySuggestions.innerHTML = '';
        allergySuggestions.style.display = 'none';
    });
}
    selectedAllergies.addEventListener('click', (event) => {
        if (event.target.classList.contains('remove-allergy')) {
            event.target.closest('.allergy-item').remove();
        }
    });
    </script>

    <?php
    return;
}

$q = trim($_GET['q'] ?? '');
$params = [];
$where = '';

if ($q !== '') {
    $where = 'WHERE p.nama_pasien LIKE ? OR p.id_pasien LIKE ?';
    $params = ["%{$q}%", "%{$q}%"];
}

$patients = fetchAll(
    "SELECT p.*, hitung_umur_pasien(p.id_pasien) AS umur, riwayat_alergi_pasien(p.id_pasien) AS alergi
     FROM Pasien p
     {$where}
     ORDER BY p.id_pasien DESC",
    $params
);
?>

<section class="header">
    <div>
        <h1>Pasien</h1>
        <p>Data identitas pasien dan seluruh riwayat alerginya.</p>
    </div>

    <a class="btn" href="<?= e(url('patients', ['action' => 'create'])) ?>">Tambah Pasien</a>
</section>

<div class="card mb">
    <form class="form" method="get">
        <input type="hidden" name="page" value="patients">

        <div class="search-row">
            <input name="q" placeholder="Cari nama atau ID pasien" value="<?= e($q) ?>">
            <button class="btn search-btn" type="submit">Cari</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Umur</th>
                    <th>Gender</th>
                    <th>Riwayat Alergi</th>
                    <th>Telepon</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($patients as $row): ?>
                    <tr>
                        <td><?= e($row['id_pasien']) ?></td>
                        <td><?= e($row['nama_pasien']) ?></td>
                        <td><?= e((string)$row['umur']) ?></td>
                        <td><?= e($row['jenis_kelamin_pasien']) ?></td>
                        <td><?= e($row['alergi']) ?></td>
                        <td><?= e($row['nomor_telepon_pasien']) ?></td>
                        <td style="display: flex; gap: 4px;">
                            <a class="btn secondary" href="<?= e(url('registrations', ['action' => 'create', 'patient_id' => $row['id_pasien']])) ?>">Kunjungan</a>
                            <a class="btn secondary" href="<?= e(url('patients', ['action' => 'edit', 'id' => $row['id_pasien']])) ?>">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$patients): ?>
                    <tr>
                        <td colspan="7" class="muted">Belum ada data.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
