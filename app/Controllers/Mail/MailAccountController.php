<?php

/**
 * Cuentas de correo del usuario (CRUD minimo).
 * La contrasena se valida contra el servidor IMAP antes de guardarse
 * y se almacena cifrada con libsodium (ver app/Helpers/crypto.php).
 */
final class MailAccountController extends Controller
{
    private const IMAP_HOST = 'coopaeca.org.pe';
    private const IMAP_PORT = 993;
    private const SMTP_HOST = 'coopaeca.org.pe';
    private const SMTP_PORT = 465; // reservado para Fase 2 (envio)

    /** GET /dashboard/mail/accounts — listado + formulario */
    public function index(): void
    {
        render('mail/accounts', [
            'title'   => 'Cuentas de correo',
            'cuentas' => $this->cuentasUsuario(),
        ]);
    }

    /** POST /dashboard/mail/accounts/store — prueba IMAP, cifra y guarda */
    public function store(): void
    {
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $nombre = trim((string)($_POST['display_name'] ?? ''));
        $firma = trim((string)($_POST['signature'] ?? ''));

        $errores = [];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'Ingresa un correo valido.';
        }
        if ($password === '') {
            $errores[] = 'La contrasena es obligatoria.';
        }
        if ($errores) {
            back_with_errors($errores, ['email' => $email, 'display_name' => $nombre, 'signature' => $firma]);
        }

        $stmt = Database::connection()->prepare('SELECT 1 FROM mail_accounts WHERE user_id = ? AND email = ? LIMIT 1');
        $stmt->execute([(int)user()['id'], $email]);
        if ($stmt->fetch()) {
            back_with_errors(['Esa cuenta ya esta registrada.'], ['email' => $email]);
        }

        // Prueba la conexion IMAP antes de guardar
        try {
            ImapService::probar(self::IMAP_HOST, self::IMAP_PORT, $email, $password);
        } catch (Throwable $e) {
            app_log('mail', 'Fallo prueba IMAP para ' . $email . ': ' . $e->getMessage());
            back_with_errors(
                ['No se pudo conectar al servidor de correo. Verifica el correo y la contrasena.'],
                ['email' => $email, 'display_name' => $nombre, 'signature' => $firma]
            );
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM mail_accounts WHERE user_id = ?');
        $stmt->execute([(int)user()['id']]);
        $esPrimera = (int)$stmt->fetchColumn() === 0;

        $pdo->prepare(
            'INSERT INTO mail_accounts (user_id, email, display_name, imap_host, imap_port, smtp_host, smtp_port, password_encrypted, signature, is_default)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            (int)user()['id'],
            $email,
            $nombre ?: null,
            self::IMAP_HOST,
            self::IMAP_PORT,
            self::SMTP_HOST,
            self::SMTP_PORT,
            encrypt($password),
            $firma ?: null,
            $esPrimera ? 1 : 0,
        ]);

        activity('Registro cuenta de correo ' . $email, 'mail');
        flash('status', 'Cuenta de correo agregada.');
        Response::redirect('/dashboard/mail');
    }

    /** POST /dashboard/mail/accounts/delete */
    public function delete(): void
    {
        $cuenta = $this->cuentaPropia((int)($_POST['id'] ?? 0));
        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM mail_accounts WHERE id = ?')->execute([(int)$cuenta['id']]);

        // Si era la cuenta por defecto, promueve la mas antigua restante
        if ((int)$cuenta['is_default'] === 1) {
            $pdo->prepare(
                'UPDATE mail_accounts SET is_default = 1 WHERE user_id = ? ORDER BY id ASC LIMIT 1'
            )->execute([(int)user()['id']]);
        }

        unset($_SESSION['_mail_folders'][(int)$cuenta['id']]);
        activity('Elimino cuenta de correo ' . $cuenta['email'], 'mail');
        flash('status', 'Cuenta de correo eliminada.');
        Response::redirect('/dashboard/mail/accounts');
    }

    /** POST /dashboard/mail/accounts/default — marcar cuenta por defecto */
    public function setDefault(): void
    {
        $cuenta = $this->cuentaPropia((int)($_POST['id'] ?? 0));
        $pdo = Database::connection();
        $pdo->prepare('UPDATE mail_accounts SET is_default = 0 WHERE user_id = ?')->execute([(int)user()['id']]);
        $pdo->prepare('UPDATE mail_accounts SET is_default = 1 WHERE id = ?')->execute([(int)$cuenta['id']]);
        flash('status', 'Cuenta por defecto actualizada.');
        Response::redirect('/dashboard/mail/accounts');
    }

    // ------------------------------------------------------------------

    private function cuentaPropia(int $id): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM mail_accounts WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$id, (int)user()['id']]);
        $cuenta = $stmt->fetch();
        if (!$cuenta) {
            Response::abort(404, 'Cuenta de correo no encontrada.');
        }
        return $cuenta;
    }

    private function cuentasUsuario(): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, email, display_name, imap_host, imap_port, is_default, created_at
             FROM mail_accounts WHERE user_id = ? ORDER BY is_default DESC, id ASC'
        );
        $stmt->execute([(int)user()['id']]);
        return $stmt->fetchAll();
    }
}
