<?php

final class AuthController extends Controller
{
    public function loginForm(): void
    {
        render('auth/login', ['title' => 'Iniciar sesion'], 'layouts/guest');
    }

    public function login(): void
    {
        $email    = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            back_with_errors(['Ingresa un correo y contrasena validos.'], $_POST);
        }

        
        $stmt = Database::connection()->prepare(
            'SELECT * FROM users WHERE email = ? AND active = 1 LIMIT 1'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            activity('Intento de login fallido: ' . $email, 'auth');
            back_with_errors(['Credenciales incorrectas.'], ['email' => $email]);
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            Database::connection()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }

        login_user($user);
        if (!empty($_POST['remember'])) {
            create_remember_token((int)$user['id']);
        }
        Database::connection()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')
            ->execute([$user['id']]);
        activity('Inicio de sesion', 'auth');
        Response::redirect('/dashboard');
    }

    public function logout(): void
    {
        activity('Cerro sesion', 'auth');
        logout_user();
        Response::redirect('/login');
    }

    public function forgotForm(): void
    {
        render('auth/forgot', ['title' => 'Recuperar contrasena'], 'layouts/guest');
    }

    public function sendReset(): void
    {
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $stmt = Database::connection()->prepare(
                'SELECT id FROM users WHERE email = ? AND active = 1 LIMIT 1'
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user) {
                $token = bin2hex(random_bytes(32));
                Database::connection()->prepare(
                    'INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)'
                )->execute([(int)$user['id'], hash('sha256', $token), date('Y-m-d H:i:s', time() + 3600)]);
                $message = 'Token generado (desarrollo): ' . $token;
            }
        }
        flash('status', $message ?? 'Si el correo existe, recibira instrucciones de recuperacion.');
        Response::redirect('/forgot-password');
    }

    public function resetForm(): void
    {
        render('auth/reset', ['title' => 'Nueva contrasena', 'token' => $_GET['token'] ?? ''], 'layouts/guest');
    }

    public function reset(): void
    {
        $token    = (string)($_POST['token'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $confirm  = (string)($_POST['password_confirmation'] ?? '');

        if (strlen($password) < 8 || $password !== $confirm) {
            back_with_errors(['La contrasena debe tener 8 caracteres y coincidir.'], $_POST);
        }

        $stmt = Database::connection()->prepare(
            'SELECT * FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1'
        );
        $stmt->execute([hash('sha256', $token)]);
        $reset = $stmt->fetch();
        if (!$reset) {
            back_with_errors(['Token invalido o vencido.'], []);
        }

        $pdo = Database::connection();
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $reset['user_id']]);
        $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')
            ->execute([$reset['id']]);
        $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = ?')
            ->execute([$reset['user_id']]);

        flash('status', 'Contrasena actualizada. Ya puedes iniciar sesion.');
        Response::redirect('/login');
    }
}
