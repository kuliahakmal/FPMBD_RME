<?php $flash = flash(); ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(appName()) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">HB</div>
            <div>
                <strong><?= e(appName()) ?></strong>
                <span>Electronic Medical Records</span>
            </div>
        </div>
        <nav>
            <?php
            $menus = [
                'dashboard' => 'Dashboard',
                'patients' => 'Pasien',
                'registrations' => 'Registrasi',
                'medical' => 'Rekam Medis',
                'inpatient' => 'Rawat Inap',
                'payments' => 'Pembayaran',
                'master' => 'Master Data',
                'schedule' => 'Jadwal Jaga',
                'audit' => 'Audit Log',
            ];
            foreach ($menus as $key => $label):
            ?>
                <a class="<?= currentPage() === $key ? 'active' : '' ?>" href="<?= e(url($key)) ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="main">
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>
        <?= $content ?>
    </main>
</body>
</html>
