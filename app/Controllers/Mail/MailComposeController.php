<?php

/**
 * Redaccion de correo: nuevo, responder, responder a todos y reenviar.
 * Los adjuntos se suben de inmediato a un borrador (autoguardado); al
 * enviar se arma el correo con SmtpService y se archiva copia en Enviados.
 */
final class MailComposeController extends Controller
{
    private const ADJUNTOS_MAX_BYTES = 20971520; // 20 MB por adjunto

    /** GET /dashboard/mail/compose — nuevo | responder | responder a todos | reenviar */
    public function form(): void
    {
        $cuenta = $this->cuentaRequerida();
        $modoSolicitado = (string)($_GET['mode'] ?? 'new');
        $modo = in_array($modoSolicitado, ['new', 'reply', 'reply_all', 'forward'], true) ? $modoSolicitado : 'new';

        $borrador = null;
        if (!empty($_GET['draft'])) {
            $borrador = $this->borradorPropio((int)$_GET['draft'], (int)$cuenta['id']);
        }

        $prefill = [
            'to' => '', 'cc' => '', 'bcc' => '', 'subject' => '',
            'body' => $this->firmaHtml($cuenta),
            'in_reply_to_folder' => null, 'in_reply_to_uid' => null, 'mode' => $modo,
        ];

        if ($borrador) {
            $prefill = [
                'to' => (string)$borrador['to_addresses'],
                'cc' => (string)$borrador['cc_addresses'],
                'bcc' => (string)$borrador['bcc_addresses'],
                'subject' => (string)$borrador['subject'],
                'body' => (string)$borrador['body_html'],
                'in_reply_to_folder' => $borrador['in_reply_to_folder'],
                'in_reply_to_uid' => $borrador['in_reply_to_uid'],
                'mode' => $borrador['mode'],
            ];
        } elseif (in_array($modo, ['reply', 'reply_all', 'forward'], true) && !empty($_GET['uid'])) {
            $prefill = $this->prefillDesdeOriginal($cuenta, $modo, (string)($_GET['folder'] ?? 'INBOX'), (int)$_GET['uid']);
        }

        render('mail/compose', [
            'title'     => 'Redactar correo',
            'cuenta'    => $cuenta,
            'borrador'  => $borrador,
            'prefill'   => $prefill,
            'adjuntos'  => $borrador ? $this->adjuntosDelBorrador((int)$borrador['id']) : [],
        ]);
    }

    /** POST /dashboard/mail/compose/draft — autoguardado (texto), crea el borrador si no existe */
    public function guardarBorrador(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $cuenta = $this->cuentaRequerida();

        $id = (int)($_POST['draft_id'] ?? 0);
        $modoPost = (string)($_POST['mode'] ?? 'new');
        $datos = [
            'to_addresses' => trim((string)($_POST['to'] ?? '')) ?: null,
            'cc_addresses' => trim((string)($_POST['cc'] ?? '')) ?: null,
            'bcc_addresses' => trim((string)($_POST['bcc'] ?? '')) ?: null,
            'subject' => trim((string)($_POST['subject'] ?? '')) ?: null,
            'body_html' => (string)($_POST['body'] ?? ''),
            'mode' => in_array($modoPost, ['new', 'reply', 'reply_all', 'forward'], true) ? $modoPost : 'new',
            'in_reply_to_folder' => trim((string)($_POST['in_reply_to_folder'] ?? '')) ?: null,
            'in_reply_to_uid' => !empty($_POST['in_reply_to_uid']) ? (int)$_POST['in_reply_to_uid'] : null,
        ];

        $pdo = Database::connection();
        if ($id > 0 && $this->borradorPropio($id, (int)$cuenta['id'])) {
            $pdo->prepare(
                'UPDATE mail_drafts SET to_addresses=?, cc_addresses=?, bcc_addresses=?, subject=?, body_html=?, mode=?, in_reply_to_folder=?, in_reply_to_uid=?, updated_at=NOW() WHERE id=?'
            )->execute([...array_values($datos), $id]);
        } else {
            $pdo->prepare(
                'INSERT INTO mail_drafts (account_id, to_addresses, cc_addresses, bcc_addresses, subject, body_html, mode, in_reply_to_folder, in_reply_to_uid, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            )->execute([(int)$cuenta['id'], ...array_values($datos)]);
            $id = (int)$pdo->lastInsertId();
        }

        echo json_encode(['ok' => true, 'draft_id' => $id]);
        exit;
    }

    /** POST /dashboard/mail/compose/attachment — sube un adjunto y lo liga a un borrador */
    public function subirAdjunto(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $cuenta = $this->cuentaRequerida();
        $draftId = (int)($_POST['draft_id'] ?? 0);
        $borrador = $draftId > 0 ? $this->borradorPropio($draftId, (int)$cuenta['id']) : null;

        if (!$borrador) {
            // No hay borrador todavia: se crea uno vacio para poder ligar el adjunto
            $modoPost = (string)($_POST['mode'] ?? 'new');
            $modoPost = in_array($modoPost, ['new', 'reply', 'reply_all', 'forward'], true) ? $modoPost : 'new';
            Database::connection()->prepare(
                'INSERT INTO mail_drafts (account_id, mode, updated_at) VALUES (?, ?, NOW())'
            )->execute([(int)$cuenta['id'], $modoPost]);
            $draftId = (int)Database::connection()->lastInsertId();
        }

        $file = $_FILES['file'] ?? null;
        $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'No se pudo subir el archivo.']);
            exit;
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > self::ADJUNTOS_MAX_BYTES) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'El adjunto supera el limite de 20 MB.']);
            exit;
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Archivo temporal invalido.']);
            exit;
        }

        $originalName = basename((string)($file['name'] ?? 'adjunto'));
        $mime = mime_content_type($tmp) ?: 'application/octet-stream';
        $dir = dirname(__DIR__, 3) . '/storage/mail_attachments/' . $draftId;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $diskName = bin2hex(random_bytes(12)) . '_' . preg_replace('/[^a-zA-Z0-9._-]+/', '_', $originalName);
        $target = $dir . '/' . $diskName;
        if (!move_uploaded_file($tmp, $target)) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'No se pudo guardar el adjunto.']);
            exit;
        }

        $pdo = Database::connection();
        $pdo->prepare(
            'INSERT INTO mail_draft_attachments (draft_id, original_name, disk_path, mime_type, size) VALUES (?, ?, ?, ?, ?)'
        )->execute([$draftId, $originalName, $target, $mime, $size]);

        echo json_encode([
            'ok' => true,
            'draft_id' => $draftId,
            'attachment' => ['id' => (int)$pdo->lastInsertId(), 'name' => $originalName, 'size' => $size],
        ]);
        exit;
    }

    /** POST /dashboard/mail/compose/attachment/delete — quita un adjunto de un borrador */
    public function eliminarAdjunto(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $cuenta = $this->cuentaRequerida();
        $attachmentId = (int)($_POST['attachment_id'] ?? 0);

        $stmt = Database::connection()->prepare(
            'SELECT a.* FROM mail_draft_attachments a
             JOIN mail_drafts d ON d.id = a.draft_id
             WHERE a.id = ? AND d.account_id = ? LIMIT 1'
        );
        $stmt->execute([$attachmentId, (int)$cuenta['id']]);
        $adjunto = $stmt->fetch();

        if ($adjunto) {
            if (is_file($adjunto['disk_path'])) {
                @unlink($adjunto['disk_path']);
            }
            Database::connection()->prepare('DELETE FROM mail_draft_attachments WHERE id = ?')->execute([$attachmentId]);
        }

        echo json_encode(['ok' => true]);
        exit;
    }

    /** POST /dashboard/mail/compose/send — arma y envia el correo, guarda copia en Enviados */
    public function enviar(): void
    {
        $cuenta = $this->cuentaRequerida();
        $draftId = (int)($_POST['draft_id'] ?? 0);
        $borrador = $draftId > 0 ? $this->borradorPropio($draftId, (int)$cuenta['id']) : null;

        $datos = [
            'to' => trim((string)($_POST['to'] ?? '')),
            'cc' => trim((string)($_POST['cc'] ?? '')),
            'bcc' => trim((string)($_POST['bcc'] ?? '')),
            'subject' => trim((string)($_POST['subject'] ?? '')) ?: '(sin asunto)',
            'html' => (string)($_POST['body'] ?? ''),
        ];

        if ($datos['to'] === '') {
            back_with_errors(['Indica al menos un destinatario.'], $_POST);
        }

        $inReplyFolder = trim((string)($_POST['in_reply_to_folder'] ?? ''));
        $inReplyUid = (int)($_POST['in_reply_to_uid'] ?? 0);
        $messageIdOriginal = null;

        if ($inReplyFolder !== '' && $inReplyUid > 0) {
            try {
                $imap = new ImapService($cuenta);
                $original = $imap->mensaje($inReplyFolder, $inReplyUid);
                $messageIdOriginal = $original['message_id'] ?: null;
                $imap->desconectar();
            } catch (Throwable) {
                // Si el mensaje original ya no existe, se envia igual sin encabezados de hilo.
            }
        }

        $adjuntosFisicos = [];
        if ($borrador) {
            foreach ($this->adjuntosDelBorrador((int)$borrador['id']) as $a) {
                $adjuntosFisicos[] = ['path' => $a['disk_path'], 'name' => $a['original_name'], 'mime' => $a['mime_type']];
            }
        }

        if ($messageIdOriginal) {
            $datos['in_reply_to_message_id'] = $messageIdOriginal;
            $datos['references'] = $messageIdOriginal;
        }

        try {
            $raw = SmtpService::enviar($cuenta, $datos + ['adjuntos' => $adjuntosFisicos]);
        } catch (Throwable $e) {
            app_log('mail', 'Error al enviar correo: ' . $e->getMessage());
            back_with_errors(['No se pudo enviar el correo: ' . $e->getMessage()], $_POST);
        }

        try {
            $imap = new ImapService($cuenta);
            $imap->guardarEnviado($raw);
            $imap->desconectar();
        } catch (Throwable $e) {
            app_log('mail', 'Correo enviado pero no se pudo archivar en Enviados: ' . $e->getMessage());
        }

        if ($borrador) {
            $this->borrarBorradorConAdjuntos((int)$borrador['id']);
        }

        activity('Envio un correo a ' . $datos['to'], 'mail');
        flash('status', 'Correo enviado correctamente.');
        Response::redirect('/dashboard/mail?account=' . (int)$cuenta['id']);
    }

    /** POST /dashboard/mail/compose/discard — descarta un borrador sin enviar */
    public function descartar(): void
    {
        $cuenta = $this->cuentaRequerida();
        $id = (int)($_POST['draft_id'] ?? 0);
        if ($this->borradorPropio($id, (int)$cuenta['id'])) {
            $this->borrarBorradorConAdjuntos($id);
        }
        flash('status', 'Borrador descartado.');
        Response::redirect('/dashboard/mail?account=' . (int)$cuenta['id']);
    }

    // ------------------------------------------------------------------

    private function prefillDesdeOriginal(array $cuenta, string $modo, string $folder, int $uid): array
    {
        try {
            $imap = new ImapService($cuenta);
            $original = $imap->mensaje($folder, $uid);
            $imap->desconectar();
        } catch (Throwable $e) {
            app_log('mail', 'Error al preparar respuesta/reenvio: ' . $e->getMessage());
            back_with_errors(['No se pudo cargar el mensaje original.']);
        }

        $remitente = $original['from_name']
            ? $original['from_name'] . ' <' . $original['from_email'] . '>'
            : $original['from_email'];

        $to = '';
        $cc = '';
        if ($modo === 'reply') {
            $to = $remitente;
        } elseif ($modo === 'reply_all') {
            $to = $remitente;
            $cc = implode(', ', array_filter($original['to'] ?? [], fn ($d) => !str_contains($d, $cuenta['email'])));
            $cc = trim($cc . (($cc && $original['cc']) ? ', ' : '') . implode(', ', $original['cc'] ?? []), ', ');
        }

        $asuntoPrefijo = $modo === 'forward' ? 'Fwd: ' : 'Re: ';
        $asunto = preg_match('/^(re|fwd|rv):/i', (string)$original['subject']) ? $original['subject'] : $asuntoPrefijo . $original['subject'];

        $fecha = $original['date'] ? date('d/m/Y H:i', strtotime($original['date'])) : '';
        $citado = sprintf(
            '<p>&nbsp;</p><p>El %s, %s escribio:</p><blockquote style="border-left:3px solid #ccc;margin:0;padding-left:12px;color:#555">%s</blockquote>',
            e($fecha),
            e($remitente),
            $original['html'] !== '' ? $original['html'] : '<pre>' . e($original['text']) . '</pre>'
        );

        return [
            'to' => $to,
            'cc' => $cc,
            'bcc' => '',
            'subject' => $asunto,
            'body' => $this->firmaHtml($cuenta) . $citado,
            'in_reply_to_folder' => $modo === 'forward' ? null : $folder,
            'in_reply_to_uid' => $modo === 'forward' ? null : $uid,
            'mode' => $modo,
        ];
    }

    /** Firma configurada en la cuenta, lista para anteponerse al cuerpo del correo. */
    private function firmaHtml(array $cuenta): string
    {
        $firma = trim((string)($cuenta['signature'] ?? ''));
        if ($firma === '') {
            return '';
        }
        return '<p>&nbsp;</p><p>--</p><p>' . nl2br(e($firma)) . '</p>';
    }

    private function adjuntosDelBorrador(int $draftId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM mail_draft_attachments WHERE draft_id = ? ORDER BY id ASC');
        $stmt->execute([$draftId]);
        return $stmt->fetchAll();
    }

    private function borrarBorradorConAdjuntos(int $draftId): void
    {
        foreach ($this->adjuntosDelBorrador($draftId) as $a) {
            if (is_file($a['disk_path'])) {
                @unlink($a['disk_path']);
            }
        }
        $dir = dirname(__DIR__, 3) . '/storage/mail_attachments/' . $draftId;
        if (is_dir($dir)) {
            @rmdir($dir);
        }
        Database::connection()->prepare('DELETE FROM mail_drafts WHERE id = ?')->execute([$draftId]);
    }

    private function borradorPropio(int $id, int $accountId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM mail_drafts WHERE id = ? AND account_id = ? LIMIT 1');
        $stmt->execute([$id, $accountId]);
        return $stmt->fetch() ?: null;
    }

    private function cuentaRequerida(): array
    {
        $id = (int)($_GET['account'] ?? $_POST['account'] ?? 0);
        $pdo = Database::connection();
        if ($id > 0) {
            $stmt = $pdo->prepare('SELECT * FROM mail_accounts WHERE id = ? AND user_id = ? LIMIT 1');
            $stmt->execute([$id, (int)user()['id']]);
            $cuenta = $stmt->fetch();
            if ($cuenta) {
                return $cuenta;
            }
        }
        $stmt = $pdo->prepare('SELECT * FROM mail_accounts WHERE user_id = ? ORDER BY is_default DESC, id ASC LIMIT 1');
        $stmt->execute([(int)user()['id']]);
        $cuenta = $stmt->fetch();
        if (!$cuenta) {
            Response::redirect('/dashboard/mail/accounts');
        }
        return $cuenta;
    }
}
