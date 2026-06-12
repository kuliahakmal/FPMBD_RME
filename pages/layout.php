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
            <div class="brand-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                    <path d="M12 5v14M5 12h14"/>
                    <path d="M8.5 3.5h7a5 5 0 0 1 5 5v7a5 5 0 0 1-5 5h-7a5 5 0 0 1-5-5v-7a5 5 0 0 1 5-5Z" opacity=".45"/>
                </svg>
            </div>
            <div>
                <strong><?= e(appName()) ?></strong>
                <span>Electronic Medical Records</span>
            </div>
        </div>
        <p class="nav-title">Menu Utama</p>
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
                <a class="<?= currentPage() === $key ? 'active' : '' ?>" href="<?= e(url($key)) ?>" <?= currentPage() === $key ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
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
