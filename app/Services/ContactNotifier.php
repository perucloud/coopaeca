<?php

/**
 * Notifica por correo los mensajes del formulario de contacto del landing.
 * El mensaje ya esta guardado en contact_messages antes de llamar a esta
 * clase, asi que un fallo de envio nunca pierde el mensaje: solo queda
 * marcado para reintentarse mas tarde (ver reintentarPendientes()).
 */
final class ContactNotifier
{
    private const MAX_INTENTOS = 5;
    private const MINUTOS_ENTRE_REINTENTOS = 10;

    /** Intenta notificar un mensaje puntual y actualiza su estado en la BD. */
    public static function intentarEnviar(array $contacto): bool
    {
        $pdo = Database::connection();
        $destino = self::destino();

        if ($destino === null) {
            $pdo->prepare(
                'UPDATE contact_messages SET notify_status = "failed", notify_attempts = notify_attempts + 1,
                 notify_last_attempt_at = NOW(), notify_error = ? WHERE id = ?'
            )->execute(['Configura un "Correo de soporte" valido en Configuracion.', $contacto['id']]);
            return false;
        }

        try {
            SmtpService::enviar(self::cuentaRemitente(), [
                'to' => $destino,
                'reply_to' => $contacto['email'],
                'subject' => 'Nuevo mensaje de contacto: ' . ($contacto['subject'] ?: 'Sin asunto'),
                'html' => self::plantilla($contacto),
            ]);

            $pdo->prepare(
                'UPDATE contact_messages SET notify_status = "sent", notify_attempts = notify_attempts + 1,
                 notify_last_attempt_at = NOW(), notify_sent_at = NOW(), notify_error = NULL WHERE id = ?'
            )->execute([$contacto['id']]);
            return true;
        } catch (Throwable $e) {
            app_log('mail', 'Error al notificar mensaje de contacto #' . $contacto['id'] . ': ' . $e->getMessage());
            $pdo->prepare(
                'UPDATE contact_messages SET notify_status = "failed", notify_attempts = notify_attempts + 1,
                 notify_last_attempt_at = NOW(), notify_error = ? WHERE id = ?'
            )->execute([mb_substr($e->getMessage(), 0, 255), $contacto['id']]);
            return false;
        }
    }

    /** Reintenta en segundo plano los mensajes pendientes/fallidos que ya cumplieron su espera. */
    public static function reintentarPendientes(): void
    {
        try {
            $stmt = Database::connection()->prepare(
                "SELECT * FROM contact_messages
                 WHERE notify_status IN ('pending', 'failed')
                   AND notify_attempts < ?
                   AND (notify_last_attempt_at IS NULL OR notify_last_attempt_at < DATE_SUB(NOW(), INTERVAL ? MINUTE))
                 ORDER BY id ASC
                 LIMIT 20"
            );
            $stmt->execute([self::MAX_INTENTOS, self::MINUTOS_ENTRE_REINTENTOS]);
            foreach ($stmt->fetchAll() as $contacto) {
                self::intentarEnviar($contacto);
            }
        } catch (Throwable $e) {
            app_log('mail', 'Error en el barrido de reintentos de contacto: ' . $e->getMessage());
        }
    }

    private static function destino(): ?string
    {
        $stmt = Database::connection()->prepare("SELECT setting_value FROM settings WHERE setting_key = 'support_email' LIMIT 1");
        $stmt->execute();
        $email = trim((string)$stmt->fetchColumn());
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private static function cuentaRemitente(): array
    {
        return [
            'email' => env_value('MAIL_NOTIFY_EMAIL', ''),
            'display_name' => 'Formulario web COOPAECA',
            'smtp_host' => env_value('MAIL_NOTIFY_HOST', ''),
            'smtp_port' => (int)env_value('MAIL_NOTIFY_PORT', 465),
            'password_encrypted' => encrypt((string)env_value('MAIL_NOTIFY_PASSWORD', '')),
        ];
    }

    private static function plantilla(array $contacto): string
    {
        return sprintf(
            '<h2>Nuevo mensaje desde la web</h2>
             <p><strong>Nombre:</strong> %s</p>
             <p><strong>Correo:</strong> %s</p>
             <p><strong>Teléfono:</strong> %s</p>
             <p><strong>Asunto:</strong> %s</p>
             <p><strong>Mensaje:</strong></p>
             <blockquote style="border-left:3px solid #ccc;margin:0;padding-left:12px;color:#333">%s</blockquote>',
            e($contacto['name']),
            e($contacto['email']),
            e($contacto['phone'] ?: 'No indicado'),
            e($contacto['subject'] ?: 'Sin asunto'),
            nl2br(e($contacto['message']))
        );
    }
}
