<?php

declare(strict_types=1);

session_start();

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/helpers.php';

$page = currentPage();
$allowedPages = [
    'dashboard' => 'dashboard.php',
    'patients' => 'patients.php',
    'registrations' => 'registrations.php',
    'medical' => 'medical.php',
    'inpatient' => 'inpatient.php',
    'payments' => 'payments.php',
    'master' => 'master.php',
    'schedule' => 'schedule.php',
    'audit' => 'audit.php',
];

if (!array_key_exists($page, $allowedPages)) {
    $page = 'dashboard';
}

try {
    ob_start();
    require dirname(__DIR__) . '/pages/' . $allowedPages[$page];
    $content = ob_get_clean();
} catch (Throwable $e) {
    ob_end_clean();
    $content = '<div class="alert alert-danger"><strong>Error:</strong> ' . e($e->getMessage()) . '</div>';
}

require dirname(__DIR__) . '/pages/layout.php';
