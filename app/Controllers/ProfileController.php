<?php

final class ProfileController extends Controller
{
    public function edit(): void
    {
        render('profile/edit', ['title' => 'Mi perfil']);
    }

    public function update(): void
    {
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') {
            back_with_errors(['El nombre es obligatorio.'], $_POST);
        }
        Database::connection()->prepare('UPDATE users SET name = ?, updated_at = NOW() WHERE id = ?')
            ->execute([$name, user()['id']]);
        activity('Actualizo su perfil', 'profile');
        flash('status', 'Perfil actualizado.');
        Response::redirect('/profile');
    }

    public function password(): void
    {
        $current  = (string)($_POST['current_password'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $confirm  = (string)($_POST['password_confirmation'] ?? '');

        if (!password_verify($current, user()['password_hash']) || strlen($password) < 8 || $password !== $confirm) {
            back_with_errors(['Verifica tu contrasena actual y la nueva contrasena.'], []);
        }
        Database::connection()->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), user()['id']]);
        activity('Cambio su contrasena', 'profile');
        flash('status', 'Contrasena actualizada.');
        Response::redirect('/profile');
    }
}
