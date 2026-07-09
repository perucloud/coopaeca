<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Envio de correo via SMTP con PHPMailer usando las credenciales
 * de una cuenta de mail_accounts. Soporta nuevo, responder y reenviar,
 * con adjuntos y encabezados de hilo (In-Reply-To / References).
 */
final class SmtpService
{
    /**
     * @param array $account fila de mail_accounts (con password_encrypted)
     * @param array $mensaje ['to' => 'a@b.c, c@d.e', 'cc' => '', 'bcc' => '', 'subject' => '', 'html' => '',
     *                        'in_reply_to_message_id' => '?', 'references' => '?', 'adjuntos' => [['path','name','mime'], ...]]
     * @return string el mensaje RFC 822 crudo, para guardar copia en la carpeta Enviados
     */
    public static function enviar(array $account, array $mensaje): string
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->Host = (string)$account['smtp_host'];
            $mail->Port = (int)$account['smtp_port'];
            $mail->SMTPSecure = (int)$account['smtp_port'] === 587 ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
            $mail->SMTPAuth = true;
            $mail->Username = (string)$account['email'];
            $mail->Password = decrypt((string)$account['password_encrypted']);
            $mail->Timeout = 20;

            $mail->setFrom((string)$account['email'], (string)($account['display_name'] ?: $account['email']));

            foreach (self::direcciones($mensaje['to'] ?? '') as $addr) {
                $mail->addAddress($addr['email'], $addr['name']);
            }
            foreach (self::direcciones($mensaje['cc'] ?? '') as $addr) {
                $mail->addCC($addr['email'], $addr['name']);
            }
            foreach (self::direcciones($mensaje['bcc'] ?? '') as $addr) {
                $mail->addBCC($addr['email'], $addr['name']);
            }

            if (!$mail->getToAddresses()) {
                throw new RuntimeException('Debes indicar al menos un destinatario.');
            }

            foreach (self::direcciones($mensaje['reply_to'] ?? '') as $addr) {
                $mail->addReplyTo($addr['email'], $addr['name']);
            }

            $mail->Subject = (string)($mensaje['subject'] ?? '(sin asunto)');
            $mail->isHTML(true);
            $mail->Body = (string)($mensaje['html'] ?? '');
            $mail->AltBody = trim(strip_tags((string)($mensaje['html'] ?? '')));

            if (!empty($mensaje['in_reply_to_message_id'])) {
                $mail->addCustomHeader('In-Reply-To', (string)$mensaje['in_reply_to_message_id']);
                $mail->addCustomHeader('References', (string)($mensaje['references'] ?? $mensaje['in_reply_to_message_id']));
            }

            foreach ($mensaje['adjuntos'] ?? [] as $adjunto) {
                $mail->addAttachment($adjunto['path'], $adjunto['name'], PHPMailer::ENCODING_BASE64, $adjunto['mime'] ?? '');
            }

            $mail->send();
            return $mail->getSentMIMEMessage();
        } catch (PHPMailerException $e) {
            throw new RuntimeException('No se pudo enviar el correo: ' . $mail->ErrorInfo, 0, $e);
        }
    }

    /**
     * Parsea "Nombre <correo>, correo2@x.com" en direcciones [email, name].
     * @return array<int, array{email: string, name: string}>
     */
    private static function direcciones(string $raw): array
    {
        $result = [];
        foreach (array_filter(array_map('trim', explode(',', $raw))) as $part) {
            if (preg_match('/^(.*)<\s*([^<>\s]+)\s*>$/', $part, $m)) {
                $result[] = ['email' => trim($m[2]), 'name' => trim($m[1], " \t\"'")];
            } elseif (filter_var($part, FILTER_VALIDATE_EMAIL)) {
                $result[] = ['email' => $part, 'name' => ''];
            }
        }
        return $result;
    }
}
