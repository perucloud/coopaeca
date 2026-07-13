<?php

/**
 * Orquesta la entrega y conserva un historial auditable. El estado de WhatsApp
 * es siempre "prepared": abrir WhatsApp Web no prueba que el mensaje se envio.
 */
final class ReceiptDeliveryService
{
    public static function automaticEmail(int $saleId, int $userId): array
    {
        $sale = SaleService::find($saleId);
        return self::email($sale, (string)($sale['email'] ?? ''), $userId, 'automatic', 'initial');
    }

    public static function resendEmail(int $saleId, string $email, int $userId): array
    {
        return self::email(SaleService::find($saleId), $email, $userId, 'manual', 'resend');
    }

    private static function email(array $sale, string $email, int $userId, string $mode, string $purpose): array
    {
        $email = strtolower(trim($email));
        $deliveryId = self::insert((int)$sale['id'], $sale['receipt_file_id'] ? (int)$sale['receipt_file_id'] : null,
            'email', $mode, $purpose, $email !== '' ? $email : null, $userId);

        try {
            // La emision no depende de que exista un canal de entrega valido.
            $issued = ReceiptService::ensureIssued((int)$sale['id'], $userId);
            self::attachFile($deliveryId, (int)$issued['receipt_file_id']);
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException($email === ''
                    ? 'El cliente no tiene correo electronico.'
                    : 'El correo electronico del cliente no es valido.');
            }
            ReceiptService::emailTo((int)$sale['id'], $email, $userId);
            self::mark($deliveryId, 'sent');
        } catch (Throwable $e) {
            $publicError = $e instanceof InvalidArgumentException
                ? $e->getMessage()
                : 'No se pudo enviar el comprobante por correo. Verifica la configuracion e intentalo nuevamente.';
            app_log('receipt_email_failed', self::sanitizedTechnicalMessage($e), [
                'sale_id' => (int)$sale['id'], 'delivery_id' => $deliveryId, 'mode' => $mode,
            ]);
            self::mark($deliveryId, 'failed', $publicError);
        }

        return self::find($deliveryId);
    }

    /** @return array{delivery:array,url:string} */
    public static function prepareWhatsApp(int $saleId, string $phone, int $userId): array
    {
        $sale = SaleService::find($saleId);
        $phone = preg_replace('/\D+/', '', $phone) ?: '';
        $deliveryId = self::insert($saleId, $sale['receipt_file_id'] ? (int)$sale['receipt_file_id'] : null,
            'whatsapp', 'manual', 'resend', $phone !== '' ? $phone : null, $userId);

        try {
            if (strlen($phone) < 9 || strlen($phone) > 15) {
                throw new InvalidArgumentException('El numero de WhatsApp no es valido.');
            }
            $issued = ReceiptService::ensureIssued($saleId, $userId);
            self::attachFile($deliveryId, (int)$issued['receipt_file_id']);
            self::mark($deliveryId, 'prepared');

            $message = 'Hola ' . trim((string)$sale['customer_name']) . ', tenemos listo el comprobante de su compra '
                . ReceiptService::referenceCode($sale) . ' por S/ ' . number_format((float)$sale['total'], 2)
                . '. El administrador adjuntara el PDF en esta conversacion.';

            return ['delivery' => self::find($deliveryId), 'url' => whatsapp_link($phone, $message)];
        } catch (Throwable $e) {
            self::mark($deliveryId, 'failed', $e->getMessage());
            throw $e;
        }
    }

    public static function history(int $saleId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT rd.*, u.name AS initiated_by_name
             FROM receipt_deliveries rd
             LEFT JOIN users u ON u.id = rd.initiated_by
             WHERE rd.sale_id = ? ORDER BY rd.created_at DESC, rd.id DESC'
        );
        $stmt->execute([$saleId]);
        return $stmt->fetchAll();
    }

    public static function latestBySaleIds(array $saleIds): array
    {
        $saleIds = array_values(array_unique(array_filter(array_map('intval', $saleIds))));
        if (!$saleIds) return [];
        $marks = implode(',', array_fill(0, count($saleIds), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT rd.* FROM receipt_deliveries rd
             JOIN (SELECT sale_id, MAX(id) id FROM receipt_deliveries WHERE sale_id IN ($marks) GROUP BY sale_id) x ON x.id = rd.id"
        );
        $stmt->execute($saleIds);
        $result = [];
        foreach ($stmt->fetchAll() as $row) $result[(int)$row['sale_id']] = $row;
        return $result;
    }

    private static function insert(int $saleId, ?int $fileId, string $channel, string $mode, string $purpose, ?string $recipient, int $userId): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO receipt_deliveries
             (sale_id, receipt_file_id, channel, delivery_mode, purpose, status, recipient, initiated_by, attempted_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$saleId, $fileId, $channel, $mode, $purpose, 'pending', $recipient, $userId]);
        return (int)Database::connection()->lastInsertId();
    }

    private static function attachFile(int $id, int $fileId): void
    {
        Database::connection()->prepare('UPDATE receipt_deliveries SET receipt_file_id = ? WHERE id = ?')->execute([$fileId, $id]);
    }

    private static function mark(int $id, string $status, ?string $error = null): void
    {
        $sent = $status === 'sent' ? 'NOW()' : 'NULL';
        $failed = $status === 'failed' ? 'NOW()' : 'NULL';
        Database::connection()->prepare(
            "UPDATE receipt_deliveries SET status = ?, error_message = ?, sent_at = $sent, failed_at = $failed WHERE id = ?"
        )->execute([$status, $error !== null ? mb_substr($error, 0, 2000) : null, $id]);
    }

    private static function find(int $id): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM receipt_deliveries WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: [];
    }

    private static function sanitizedTechnicalMessage(Throwable $e): string
    {
        $message = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[email]', $e->getMessage()) ?? '';
        $message = preg_replace('/(?i)(password|passwd|token|authorization|auth)[\s:=]+[^\s,;]+/', '$1=[redacted]', $message) ?? '';
        return substr(get_class($e) . ': ' . preg_replace('/[\r\n]+/', ' ', $message), 0, 1000);
    }
}
