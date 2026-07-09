<?php

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = (bool)config_app('secure_cookies');
    session_name(config_app('session_name'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $token = $_POST['_csrf'] ?? $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        Response::abort(419, 'Token CSRF invalido. Recarga la pagina e intenta nuevamente.');
    }
}

function rate_limit(string $key, int $maxAttempts, int $seconds): bool
{
    $now = time();
    $_SESSION['_rate'][$key] = array_filter(
        $_SESSION['_rate'][$key] ?? [],
        fn (int $timestamp) => $timestamp > ($now - $seconds)
    );
    if (count($_SESSION['_rate'][$key]) >= $maxAttempts) {
        return false;
    }
    $_SESSION['_rate'][$key][] = $now;
    return true;
}

function secure_remember_cookie(string $value, int $days = 30): void
{
    setcookie('remember_token', $value, [
        'expires' => time() + ($days * 86400),
        'path' => '/',
        'secure' => (bool)config_app('secure_cookies'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function clear_remember_cookie(): void
{
    setcookie('remember_token', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => (bool)config_app('secure_cookies'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}
