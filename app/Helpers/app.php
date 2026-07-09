<?php

function config_app(string $key, mixed $default = null): mixed
{
    static $config = null;
    $config ??= require dirname(__DIR__) . '/Config/app.php';
    return $config[$key] ?? $default;
}

/**
 * Nombre visible del panel admin (sidebar y pestaña del navegador),
 * configurable en Configuracion > Nombre del sistema. Cae al nombre
 * fijo del .env si aun no se ha configurado. Se cachea por request
 * para no repetir la consulta en cada controlador.
 */
function dashboard_name(): string
{
    static $name = null;
    if ($name !== null) {
        return $name;
    }

    $stmt = Database::connection()->prepare("SELECT setting_value FROM settings WHERE setting_key = 'app_name' LIMIT 1");
    $stmt->execute();
    $value = trim((string)$stmt->fetchColumn());

    return $name = $value !== '' ? $value : (string)config_app('name');
}

function load_env_file(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");
        if ($key !== '') {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

function env_value(string $key, mixed $default = null): mixed
{
    if (array_key_exists($key, $_ENV) && $_ENV[$key] !== '') {
        return $_ENV[$key];
    }

    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

function url(string $path = ''): string
{
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    return app_path($path);
}

function absolute_url(string $path = ''): string
{
    $base = config_app('base_url');
    if ($base === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if (str_ends_with($script, '/public')) {
            $script = substr($script, 0, -7) ?: '/';
        }
        $base = $scheme . '://' . $host . rtrim($script === '/' ? '' : $script, '/');
    }
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function app_path(string $path = ''): string
{
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if (str_ends_with($script, '/public')) {
        $script = substr($script, 0, -7) ?: '';
    }
    $script = $script === '/' ? '' : rtrim($script, '/');

    return $script . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $relative = ltrim($path, '/');
    $full = dirname(__DIR__, 2) . '/public/assets/' . $relative;
    $version = is_file($full) ? filemtime($full) : time();
    return url('assets/' . $relative) . '?v=' . $version;
}

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function slugify(string $value): string
{
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    $value = strtolower((string)preg_replace('/[^a-zA-Z0-9]+/', '-', $value));
    $value = trim($value, '-');
    return $value !== '' ? $value : 'item-' . time();
}

function view(string $template, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require dirname(__DIR__) . '/Views/' . $template . '.php';
}

function render(string $template, array $data = [], string $layout = 'layouts/app'): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require dirname(__DIR__) . '/Views/' . $template . '.php';
    $content = ob_get_clean();
    require dirname(__DIR__) . '/Views/' . $layout . '.php';
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old'][$key] ?? $default;
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }
    $message = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $message;
}

function back_with_errors(array $errors, array $old = []): never
{
    $_SESSION['_errors'] = $errors;
    $_SESSION['_old'] = $old;
    Response::redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

function is_active(string ...$paths): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    foreach ($paths as $path) {
        if ($path === '/' || $path === '/dashboard') {
            if (preg_match('#/(dashboard|)$#', $uri)) {
                return 'active';
            }
        } elseif (str_contains($uri, $path)) {
            return 'active';
        }
    }
    return '';
}

function errors(): array
{
    $errors = $_SESSION['_errors'] ?? [];
    unset($_SESSION['_errors']);
    return $errors;
}
