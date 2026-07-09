<?php

/**
 * Bandeja de correo (Fase 1: solo lectura).
 * El listado se sirve desde mail_cache; la lectura y los adjuntos
 * se obtienen en vivo por IMAP.
 */
final class MailboxController extends Controller
{
    private const POR_PAGINA = 25;
    private const CARPETAS_TTL = 600; // segundos de cache de carpetas en sesion

    /** GET /dashboard/mail — bandeja desde mail_cache */
    public function inbox(): void
    {
        $cuenta = $this->cuentaActual();
        if ($cuenta === null) {
            flash('status', 'Primero registra una cuenta de correo.');
            Response::redirect('/dashboard/mail/accounts');
        }

        $carpeta = trim((string)($_GET['folder'] ?? 'INBOX')) ?: 'INBOX';
        $pagina = max(1, (int)($_GET['page'] ?? 1));

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM mail_cache WHERE account_id = ? AND folder = ?');
        $stmt->execute([(int)$cuenta['id'], $carpeta]);
        $total = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare(
            'SELECT * FROM mail_cache WHERE account_id = ? AND folder = ?
             ORDER BY date DESC, uid DESC LIMIT ' . self::POR_PAGINA . ' OFFSET ' . (($pagina - 1) * self::POR_PAGINA)
        );
        $stmt->execute([(int)$cuenta['id'], $carpeta]);
        $mensajes = $stmt->fetchAll();

        render('mail/inbox', [
            'title'    => 'Correo',
            'cuenta'   => $cuenta,
            'cuentas'  => $this->cuentasUsuario(),
            'carpetas' => $this->carpetas($cuenta),
            'carpeta'  => $carpeta,
            'mensajes' => $mensajes,
            'pagina'   => $pagina,
            'paginas'  => max(1, (int)ceil($total / self::POR_PAGINA)),
            'total'    => $total,
        ]);
    }

    /** GET /dashboard/mail/sync — sincroniza nuevos UIDs a mail_cache (JSON) */
    public function sync(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $cuenta = $this->cuentaActual();
            if ($cuenta === null) {
                throw new RuntimeException('No hay una cuenta de correo configurada.');
            }
            $carpeta = trim((string)($_GET['folder'] ?? 'INBOX')) ?: 'INBOX';

            $pdo = Database::connection();
            $stmt = $pdo->prepare('SELECT COALESCE(MAX(uid), 0) FROM mail_cache WHERE account_id = ? AND folder = ?');
            $stmt->execute([(int)$cuenta['id'], $carpeta]);
            $maxUid = (int)$stmt->fetchColumn();

            $imap = new ImapService($cuenta);
            // Mensajes nuevos + refresco de banderas de los mas recientes
            $cabeceras = $imap->mensajesDesdeUid($carpeta, $maxUid);
            $recientes = $imap->mensajes($carpeta, 1, 50);
            $imap->desconectar();

            $nuevos = $this->guardarEnCache((int)$cuenta['id'], $carpeta, array_merge($cabeceras, $recientes), $maxUid);

            // Invalida el cache de carpetas para refrescar contadores
            unset($_SESSION['_mail_folders'][(int)$cuenta['id']]);

            echo json_encode(['ok' => true, 'nuevos' => $nuevos, 'carpeta' => $carpeta]);
        } catch (Throwable $e) {
            app_log('mail', 'Error al sincronizar: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'No se pudo sincronizar con el servidor de correo.']);
        }
        exit;
    }

    /** GET /dashboard/mail/read?uid= — cuerpo en vivo por IMAP, sanitizado */
    public function read(): void
    {
        $cuenta = $this->cuentaRequerida();
        $carpeta = trim((string)($_GET['folder'] ?? 'INBOX')) ?: 'INBOX';
        $uid = (int)($_GET['uid'] ?? 0);
        if ($uid <= 0) {
            Response::abort(404, 'Mensaje no encontrado.');
        }

        try {
            $imap = new ImapService($cuenta);
            $mensaje = $imap->mensaje($carpeta, $uid);
            if (!$mensaje['is_seen']) {
                $imap->marcarLeido($carpeta, $uid, true);
                $mensaje['is_seen'] = 1;
            }
            $imap->desconectar();
        } catch (Throwable $e) {
            app_log('mail', 'Error al leer mensaje: ' . $e->getMessage());
            Response::abort(502, 'No se pudo obtener el mensaje del servidor de correo.');
        }

        // Refleja la lectura en el cache local
        Database::connection()->prepare(
            'UPDATE mail_cache SET is_seen = 1 WHERE account_id = ? AND folder = ? AND uid = ?'
        )->execute([(int)$cuenta['id'], $carpeta, $uid]);

        $imagenesBloqueadas = 0;
        if ($mensaje['html'] !== '') {
            $html = $this->sanitizar($mensaje['html']);
            $html = $this->bloquearImagenesRemotas($html, $imagenesBloqueadas);
            $mensaje['html'] = $html;
        }

        render('mail/read', [
            'title'              => $mensaje['subject'] !== '' ? $mensaje['subject'] : 'Mensaje',
            'cuenta'             => $cuenta,
            'carpeta'            => $carpeta,
            'carpetas'           => $this->carpetas($cuenta),
            'mensaje'            => $mensaje,
            'imagenesBloqueadas' => $imagenesBloqueadas,
        ]);
    }

    /** GET /dashboard/mail/attachment?uid=&index= — descarga de adjunto */
    public function attachment(): void
    {
        $cuenta = $this->cuentaRequerida();
        $carpeta = trim((string)($_GET['folder'] ?? 'INBOX')) ?: 'INBOX';
        $uid = (int)($_GET['uid'] ?? 0);
        $indice = (int)($_GET['index'] ?? -1);
        if ($uid <= 0 || $indice < 0) {
            Response::abort(404, 'Adjunto no encontrado.');
        }

        try {
            $imap = new ImapService($cuenta);
            $adjunto = $imap->adjunto($carpeta, $uid, $indice);
            $imap->desconectar();
        } catch (Throwable $e) {
            app_log('mail', 'Error al descargar adjunto: ' . $e->getMessage());
            Response::abort(502, 'No se pudo descargar el adjunto.');
        }

        $nombre = preg_replace('/[\r\n"\\\\]+/', '_', $adjunto['name']) ?: 'adjunto';
        header('Content-Type: ' . $adjunto['mime']);
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        header('Content-Length: ' . strlen($adjunto['content']));
        header('X-Content-Type-Options: nosniff');
        echo $adjunto['content'];
        exit;
    }

    /** POST /dashboard/mail/seen — marcar leido/no leido */
    public function toggleSeen(): void
    {
        $cuenta = $this->cuentaRequerida();
        $carpeta = trim((string)($_POST['folder'] ?? 'INBOX')) ?: 'INBOX';
        $uid = (int)($_POST['uid'] ?? 0);
        $leido = !empty($_POST['seen']);

        try {
            $imap = new ImapService($cuenta);
            $imap->marcarLeido($carpeta, $uid, $leido);
            $imap->desconectar();
            Database::connection()->prepare(
                'UPDATE mail_cache SET is_seen = ? WHERE account_id = ? AND folder = ? AND uid = ?'
            )->execute([$leido ? 1 : 0, (int)$cuenta['id'], $carpeta, $uid]);
            flash('status', $leido ? 'Mensaje marcado como leido.' : 'Mensaje marcado como no leido.');
        } catch (Throwable $e) {
            app_log('mail', 'Error al marcar mensaje: ' . $e->getMessage());
            back_with_errors(['No se pudo actualizar el estado del mensaje.']);
        }

        Response::redirect('/dashboard/mail?folder=' . rawurlencode($carpeta));
    }

    /** POST /dashboard/mail/delete — mueve el mensaje a la papelera del servidor */
    public function delete(): void
    {
        $cuenta = $this->cuentaRequerida();
        $carpeta = trim((string)($_POST['folder'] ?? 'INBOX')) ?: 'INBOX';
        $uid = (int)($_POST['uid'] ?? 0);

        try {
            $imap = new ImapService($cuenta);
            $imap->eliminar($carpeta, $uid);
            $imap->desconectar();
            Database::connection()->prepare(
                'DELETE FROM mail_cache WHERE account_id = ? AND folder = ? AND uid = ?'
            )->execute([(int)$cuenta['id'], $carpeta, $uid]);
            flash('status', 'Mensaje movido a la papelera.');
        } catch (Throwable $e) {
            app_log('mail', 'Error al eliminar mensaje: ' . $e->getMessage());
            back_with_errors(['No se pudo eliminar el mensaje.']);
        }

        Response::redirect('/dashboard/mail?account=' . (int)$cuenta['id'] . '&folder=' . rawurlencode($carpeta));
    }

    /** POST /dashboard/mail/move — mueve el mensaje a otra carpeta del buzon */
    public function move(): void
    {
        $cuenta = $this->cuentaRequerida();
        $carpeta = trim((string)($_POST['folder'] ?? 'INBOX')) ?: 'INBOX';
        $uid = (int)($_POST['uid'] ?? 0);
        $destino = trim((string)($_POST['destino'] ?? ''));

        if ($destino === '') {
            back_with_errors(['Selecciona una carpeta de destino.']);
        }

        try {
            $imap = new ImapService($cuenta);
            $imap->mover($carpeta, $uid, $destino);
            $imap->desconectar();
            Database::connection()->prepare(
                'DELETE FROM mail_cache WHERE account_id = ? AND folder = ? AND uid = ?'
            )->execute([(int)$cuenta['id'], $carpeta, $uid]);
            flash('status', 'Mensaje movido a ' . $destino . '.');
        } catch (Throwable $e) {
            app_log('mail', 'Error al mover mensaje: ' . $e->getMessage());
            back_with_errors(['No se pudo mover el mensaje.']);
        }

        Response::redirect('/dashboard/mail?account=' . (int)$cuenta['id'] . '&folder=' . rawurlencode($carpeta));
    }

    // ------------------------------------------------------------------

    /** Inserta/actualiza cabeceras en mail_cache y devuelve cuantos son nuevos. */
    private function guardarEnCache(int $accountId, string $carpeta, array $cabeceras, int $maxUidPrevio): int
    {
        if (!$cabeceras) {
            return 0;
        }

        $stmt = Database::connection()->prepare(
            'INSERT INTO mail_cache (account_id, folder, uid, message_id, subject, from_email, from_name, preview, has_attachments, is_seen, date)
             VALUES (?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                subject = VALUES(subject),
                is_seen = VALUES(is_seen),
                has_attachments = VALUES(has_attachments)'
        );

        $nuevos = 0;
        $vistos = [];
        foreach ($cabeceras as $c) {
            if ($c['uid'] <= 0 || isset($vistos[$c['uid']])) {
                continue;
            }
            $vistos[$c['uid']] = true;
            $stmt->execute([
                $accountId,
                $carpeta,
                $c['uid'],
                $c['message_id'] ?: null,
                $c['subject'] ?: null,
                $c['from_email'] ?: null,
                $c['from_name'] ?: null,
                $c['has_attachments'],
                $c['is_seen'],
                $c['date'],
            ]);
            if ($c['uid'] > $maxUidPrevio) {
                $nuevos++;
            }
        }
        return $nuevos;
    }

    /** Carpetas del buzon, cacheadas en sesion para no conectar en cada carga. */
    private function carpetas(array $cuenta): array
    {
        $id = (int)$cuenta['id'];
        $cache = $_SESSION['_mail_folders'][$id] ?? null;
        if ($cache !== null && ($cache['at'] + self::CARPETAS_TTL) > time()) {
            return $cache['data'];
        }

        try {
            $imap = new ImapService($cuenta);
            $carpetas = $imap->carpetas();
            $imap->desconectar();
        } catch (Throwable $e) {
            app_log('mail', 'Error al listar carpetas: ' . $e->getMessage());
            $carpetas = $cache['data'] ?? [['path' => 'INBOX', 'name' => 'INBOX', 'total' => 0, 'unseen' => 0]];
        }

        $_SESSION['_mail_folders'][$id] = ['at' => time(), 'data' => $carpetas];
        return $carpetas;
    }

    /** Sanitiza HTML de correo con HTMLPurifier (enlaces en pestana nueva). */
    private function sanitizar(string $html): string
    {
        $cacheDir = dirname(__DIR__, 3) . '/storage/purifier';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', is_dir($cacheDir) ? $cacheDir : null);
        $config->set('HTML.TargetBlank', true);
        $config->set('HTML.Nofollow', true);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'data' => true]);

        return (new HTMLPurifier($config))->purify($html);
    }

    /**
     * Reemplaza imagenes remotas por un pixel transparente y guarda la URL
     * original en data-remote-src; la vista permite restaurarlas bajo demanda.
     */
    private function bloquearImagenesRemotas(string $html, int &$bloqueadas): string
    {
        $pixel = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

        $html = preg_replace_callback(
            '/(<img\b[^>]*?)\ssrc="(https?:\/\/[^"]*)"/i',
            function (array $m) use ($pixel, &$bloqueadas): string {
                $bloqueadas++;
                return $m[1] . ' src="' . $pixel . '" data-remote-src="' . $m[2] . '"';
            },
            $html
        ) ?? $html;

        // Bloquea tambien fondos remotos declarados en style=""
        return preg_replace('/url\(\s*[\'"]?https?:\/\/[^)]*\)/i', 'none', $html) ?? $html;
    }

    /** Cuenta activa: ?account= (propia), la marcada por defecto o la primera. */
    private function cuentaActual(): ?array
    {
        $cuentas = $this->cuentasUsuario();
        if (!$cuentas) {
            return null;
        }

        $solicitada = (int)($_GET['account'] ?? $_POST['account'] ?? 0);
        foreach ($cuentas as $cuenta) {
            if ($solicitada > 0 && (int)$cuenta['id'] === $solicitada) {
                return $cuenta;
            }
        }
        foreach ($cuentas as $cuenta) {
            if ((int)$cuenta['is_default'] === 1) {
                return $cuenta;
            }
        }
        return $cuentas[0];
    }

    private function cuentaRequerida(): array
    {
        $cuenta = $this->cuentaActual();
        if ($cuenta === null) {
            Response::redirect('/dashboard/mail/accounts');
        }
        return $cuenta;
    }

    private function cuentasUsuario(): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM mail_accounts WHERE user_id = ? ORDER BY is_default DESC, id ASC'
        );
        $stmt->execute([(int)user()['id']]);
        return $stmt->fetchAll();
    }
}
