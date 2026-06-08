<?php

declare(strict_types=1);

function loadEnv(string $path): array
{
    $env = [];
    if (!file_exists($path)) {
        return $env;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        $env[$key] = $value;
    }

    return $env;
}

$env = loadEnv(dirname(__DIR__) . '/.env');

function envValue(string $key, string $default = ''): string
{
    global $env;
    return $env[$key] ?? $default;
}

function appName(): string
{
    return envValue('APP_NAME', 'Harapan Bangsa RME');
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = envValue('DB_HOST', '127.0.0.1');
    $port = envValue('DB_PORT', '3306');
    $name = envValue('DB_NAME', 'rme_db');
    $user = envValue('DB_USER', 'root');
    $pass = envValue('DB_PASS', '');

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
