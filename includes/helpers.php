<?php
declare(strict_types=1);

function app_base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
    $root = realpath(ROOT_DIR) ?: ROOT_DIR;
    $docRoot = str_replace('\\', '/', $docRoot);
    $root = str_replace('\\', '/', $root);

    if ($docRoot !== '' && str_starts_with($root, $docRoot)) {
        $base = substr($root, strlen($docRoot));
    } else {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $base = preg_replace('#/(api(?:/.*)?|[^/]+\.php)$#', '', $script) ?? '';
    }

    $base = rtrim($base, '/');
    return $base;
}

function base_url(string $path = ''): string
{
    $path = ltrim(str_replace('\\', '/', $path), '/');
    return $path === '' ? '.' : $path;
}

function asset_url(string $path): string
{
    return base_url($path);
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function request_method(): string
{
    $override = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? '';
    if ($override !== '') {
        return strtoupper($override);
    }
    if (isset($_POST['_method']) && is_string($_POST['_method'])) {
        return strtoupper($_POST['_method']);
    }
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function request_json(): array
{
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') {
        return $_POST;
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : $_POST;
}

function json_response(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $message, int $status = 400, array $extra = []): never
{
    json_response(array_merge(['ok' => false, 'error' => $message], $extra), $status);
}

function now_iso(): string
{
    return gmdate('Y-m-d\TH:i:s\Z');
}

function money(float|int $amount): string
{
    return number_format((float) $amount, 2, '.', '');
}
