<?php

function user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    static $user = null;
    if ($user !== null) {
        return $user;
    }

    $stmt = Database::connection()->prepare(
        'SELECT u.*, r.name AS role_name, r.slug AS role_slug
         FROM users u
         JOIN roles r ON r.id = u.role_id
         WHERE u.id = ? AND u.active = 1
         LIMIT 1'
    );
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;
    return $user;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
}

function logout_user(): void
{
    if (!empty($_SESSION['user_id'])) {
        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = ?')->execute([$_SESSION['user_id']]);
    }
    clear_remember_cookie();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function require_auth(): void
{
    if (!user()) {
        Response::redirect('/login');
    }
}

function is_superadmin(): bool
{
    return (user()['role_slug'] ?? '') === 'superadmin';
}

/**
 * Los permisos son por usuario (no por rol): una fila en user_module_access
 * da acceso completo (ver/crear/editar/eliminar) a ese modulo. El parametro
 * $action se conserva por compatibilidad con las llamadas existentes, pero
 * ya no distingue nivel de acceso dentro de un modulo.
 */
function can(string $module, string $action = 'view'): bool
{
    if (!user()) {
        return false;
    }
    if (is_superadmin()) {
        return true;
    }
    $stmt = Database::connection()->prepare(
        'SELECT 1 FROM user_module_access WHERE user_id = ? AND module = ? LIMIT 1'
    );
    $stmt->execute([(int)user()['id'], $module]);
    return (bool)$stmt->fetch();
}

function require_permission(string $module, string $action = 'view'): void
{
    require_auth();
    if (!can($module, $action)) {
        Response::abort(403, 'No tienes permisos para acceder a este modulo.');
    }
}

function remember_login(): void
{
    if (user() || empty($_COOKIE['remember_token'])) {
        return;
    }

    $parts = explode(':', (string)$_COOKIE['remember_token'], 2);
    if (count($parts) !== 2) {
        clear_remember_cookie();
        return;
    }

    [$selector, $validator] = $parts;
    $stmt = Database::connection()->prepare(
        'SELECT rt.*, u.active FROM remember_tokens rt
         JOIN users u ON u.id = rt.user_id
         WHERE rt.selector = ? AND rt.expires_at > NOW()
         LIMIT 1'
    );
    $stmt->execute([$selector]);
    $row = $stmt->fetch();

    if (!$row || !hash_equals($row['token_hash'], hash('sha256', $validator)) || (int)$row['active'] !== 1) {
        clear_remember_cookie();
        return;
    }

    login_user(['id' => $row['user_id']]);
}

function create_remember_token(int $userId): void
{
    $selector = bin2hex(random_bytes(9));
    $validator = bin2hex(random_bytes(32));
    $hash = hash('sha256', $validator);
    $expires = date('Y-m-d H:i:s', time() + 30 * 86400);

    $pdo = Database::connection();
    $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = ?')->execute([$userId]);
    $pdo->prepare(
        'INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at) VALUES (?, ?, ?, ?)'
    )->execute([$userId, $selector, $hash, $expires]);

    secure_remember_cookie($selector . ':' . $validator);
}
