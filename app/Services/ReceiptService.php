<?php

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Genera y guarda el ticket/comprobante de una venta confirmada (80mm,
 * formato termico). El PDF se genera una sola vez ("emitir") y queda
 * guardado en `files`/`sales.receipt_file_id` para poder reimprimirlo o
 * reenviarlo por correo despues, sin regenerar contenido distinto.
 */
final class ReceiptService
{
    /** Emite el ticket si aun no existe uno guardado para la venta; si ya existe, lo devuelve tal cual (idempotente). */
    public static function ensureIssued(int $saleId, int $userId): array
    {
        $sale = SaleService::find($saleId);
        if ($sale['receipt_file_id']) {
            return $sale;
        }
        return self::generate($sale, $userId);
    }

    public static function emailTo(int $saleId, string $toEmail, int $userId): void
    {
        $toEmail = trim($toEmail);
        if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Ingresa un correo valido.');
        }

        $sale = self::ensureIssued($saleId, $userId);
        $file = self::fileRow((int)$sale['receipt_file_id']);
        $account = self::remitente();
        if ($account === null) {
            throw new RuntimeException('Configura un correo de soporte valido en Configuracion para poder enviar tickets.');
        }

        $settings = self::settings();
        $coopName = $settings['cooperative_name'] ?? 'COOPAECA';

        SmtpService::enviar($account, [
            'to' => $toEmail,
            'subject' => 'Comprobante de venta ' . $sale['code'] . ' - ' . $coopName,
            'html' => '<p>Hola ' . e((string)$sale['customer_name']) . ',</p>'
                . '<p>Adjuntamos el comprobante de tu compra <strong>' . e((string)$sale['code']) . '</strong> por un total de S/ '
                . number_format((float)$sale['total'], 2) . '.</p>'
                . '<p>Gracias por tu preferencia.<br>' . e((string)$coopName) . '</p>',
            'adjuntos' => [
                ['path' => dirname(__DIR__, 2) . '/public/' . $file['disk_path'], 'name' => $sale['code'] . '.pdf', 'mime' => 'application/pdf'],
            ],
        ]);

        activity('Envio ticket de venta ' . $sale['code'] . ' a ' . $toEmail, 'sales');
    }

    private static function generate(array $sale, int $userId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM sale_items WHERE sale_id = ? ORDER BY id ASC');
        $stmt->execute([$sale['id']]);
        $items = $stmt->fetchAll();
        $html = self::buildHtml($sale, $items);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        // Ancho fijo de 80mm (226.77pt); alto holgado porque Dompdf no soporta
        // alto de pagina automatico segun el contenido de un ticket termico.
        $dompdf->setPaper([0, 0, 226.77, 1600], 'portrait');
        $dompdf->render();
        $pdfContent = $dompdf->output();

        $dir = dirname(__DIR__, 2) . '/public/uploads/receipts';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $name = bin2hex(random_bytes(18)) . '.pdf';
        file_put_contents($dir . '/' . $name, $pdfContent);

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'INSERT INTO files (disk_path, original_name, mime_type, size_bytes, uploaded_by, alt_text)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([
                'uploads/receipts/' . $name,
                $sale['code'] . '.pdf',
                'application/pdf',
                strlen($pdfContent),
                $userId,
                'Ticket de venta ' . $sale['code'],
            ]);
            $fileId = (int)$pdo->lastInsertId();

            $pdo->prepare(
                'UPDATE sales SET receipt_file_id = ?, receipt_issued_at = NOW(), receipt_issued_by = ? WHERE id = ?'
            )->execute([$fileId, $userId, $sale['id']]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        activity('Emitio ticket de venta ' . $sale['code'], 'sales');

        return SaleService::find((int)$sale['id']);
    }

    private static function buildHtml(array $sale, array $items): string
    {
        $settings = self::settings();
        $coopName = e((string)($settings['cooperative_name'] ?? 'COOPAECA'));
        $address = e((string)($settings['topbar_address'] ?? ''));
        $phone = e((string)($settings['topbar_phone'] ?? ''));
        $ruc = trim((string)($settings['ruc'] ?? ''));
        $rucLine = $ruc !== '' ? '<div class="muted">RUC: ' . e($ruc) . '</div>' : '';

        $rows = '';
        foreach ($items as $item) {
            $rows .= '<tr>'
                . '<td colspan="2">' . e((string)$item['product_name']) . '</td>'
                . '</tr><tr>'
                . '<td>' . (int)$item['quantity'] . ' x S/ ' . number_format((float)$item['unit_price'], 2) . '</td>'
                . '<td class="right">S/ ' . number_format((float)$item['subtotal'], 2) . '</td>'
                . '</tr>';
        }

        $date = e(date('d/m/Y H:i', strtotime((string)($sale['confirmed_at'] ?: $sale['created_at']))));
        $code = e((string)$sale['code']);
        $customerName = e((string)$sale['customer_name']);
        $docType = e((string)$sale['document_type']);
        $docNumber = e((string)$sale['document_number']);
        $paymentMethod = e((string)$sale['payment_method']);
        $operationNumber = e((string)$sale['payment_operation_number']);
        $totalFormatted = number_format((float)$sale['total'], 2);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 4mm 3mm; }
    body { font-family: 'Helvetica', sans-serif; font-size: 9px; color: #000; margin: 0; padding: 0; }
    .center { text-align: center; }
    .right { text-align: right; }
    h1 { font-size: 12px; margin: 0 0 2px; }
    .muted { color: #444; font-size: 8px; }
    hr { border: none; border-top: 1px dashed #000; margin: 6px 0; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 1px 0; font-size: 9px; }
    .totals td { font-size: 10px; padding-top: 2px; }
    .footer { margin-top: 8px; font-size: 8px; }
</style>
</head>
<body>
    <div class="center">
        <h1>{$coopName}</h1>
        <div class="muted">{$address}</div>
        <div class="muted">{$phone}</div>
        {$rucLine}
    </div>
    <hr>
    <div><strong>Comprobante:</strong> {$code}</div>
    <div><strong>Fecha:</strong> {$date}</div>
    <div><strong>Cliente:</strong> {$customerName}</div>
    <div><strong>Doc:</strong> {$docType} {$docNumber}</div>
    <hr>
    <table>
        {$rows}
    </table>
    <hr>
    <table class="totals">
        <tr><td><strong>TOTAL</strong></td><td class="right"><strong>S/ {$totalFormatted}</strong></td></tr>
    </table>
    <hr>
    <div><strong>Pago:</strong> {$paymentMethod}</div>
    <div><strong>Operacion:</strong> {$operationNumber}</div>
    <div class="footer center">Gracias por su compra.<br>Documento generado por el sistema.</div>
</body>
</html>
HTML;
    }

    private static function fileRow(int $fileId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM files WHERE id = ? LIMIT 1');
        $stmt->execute([$fileId]);
        $file = $stmt->fetch();
        if (!$file) {
            throw new RuntimeException('Archivo de ticket no encontrado.');
        }
        return $file;
    }

    private static function settings(): array
    {
        return Database::connection()
            ->query('SELECT setting_key, setting_value FROM settings')
            ->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    private static function remitente(): ?array
    {
        $host = env_value('MAIL_NOTIFY_HOST', '');
        $email = env_value('MAIL_NOTIFY_EMAIL', '');
        $password = env_value('MAIL_NOTIFY_PASSWORD', '');
        $port = (int)env_value('MAIL_NOTIFY_PORT', 465);
        if ($host === '' || $email === '' || $password === '') {
            return null;
        }
        return [
            'smtp_host' => $host,
            'smtp_port' => $port,
            'email' => $email,
            'password_encrypted' => encrypt($password),
            'display_name' => 'COOPAECA',
        ];
    }
}
