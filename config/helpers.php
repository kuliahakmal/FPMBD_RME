<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function url(string $page, array $params = []): string
{
    $query = array_merge(['page' => $page], $params);
    return 'index.php?' . http_build_query($query);
}

function redirect(string $page, array $params = []): void
{
    header('Location: ' . url($page, $params));
    exit;
}

function flash(?string $message = null, string $type = 'success'): ?array
{
    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
        return null;
    }

    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function input(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

function rupiah(mixed $number): string
{
    return 'Rp ' . number_format((float)$number, 0, ',', '.');
}

function fetchAll(string $sql, array $params = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetchOne(string $sql, array $params = []): ?array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

function execute(string $sql, array $params = []): void
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
}

function nextId(string $table, string $column, string $prefix, int $digits = 3): string
{
    $row = fetchOne("SELECT MAX({$column}) AS max_id FROM {$table} WHERE {$column} LIKE ?", [$prefix . '%']);
    $max = $row['max_id'] ?? null;
    $next = 1;

    if ($max) {
        $num = (int)preg_replace('/\D/', '', (string)$max);
        $next = $num + 1;
    }

    return $prefix . str_pad((string)$next, $digits, '0', STR_PAD_LEFT);
}

function selected(mixed $current, mixed $expected): string
{
    return (string)$current === (string)$expected ? 'selected' : '';
}

function requiredSelect(array $rows, string $label): void
{
    if (count($rows) === 0) {
        throw new RuntimeException("Data {$label} masih kosong. Isi dulu di menu Master Data.");
    }
}

function postOnly(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method not allowed');
    }
}

function currentPage(): string
{
    return $_GET['page'] ?? 'dashboard';
}
