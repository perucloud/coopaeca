<?php

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Correo de confirmacion que recibe el cliente apenas registra su pedido
 * en el checkout publico (antes de cualquier revision del administrador),
 * con un PDF adjunto que resume el pedido. Es independiente de la nota de
 * venta (ReceiptService), que solo se genera y envia despues de que el
 * pedido es aprobado y se convierte en una Venta.
 */
final class OrderConfirmationService
{
    public static function send(array $order, array $items, bool $isEn): void
    {
        if (empty($order['email']) || !filter_var($order['email'], FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $account = ReceiptService::remitente();
        if ($account === null) {
            return;
        }

        $settings = ReceiptService::settings();
        $coopName = (string)($settings['cooperative_name'] ?? 'COOPAECA');
        $code = (string)$order['code'];

        $lines = '';
        foreach ($items as $item) {
            $lines .= '<tr>'
                . '<td style="padding:4px 8px;border-bottom:1px solid #eee;">' . e((string)$item['product_name']) . '</td>'
                . '<td style="padding:4px 8px;border-bottom:1px solid #eee;text-align:center;">' . (int)$item['quantity'] . '</td>'
                . '<td style="padding:4px 8px;border-bottom:1px solid #eee;text-align:right;">S/ ' . number_format((float)$item['subtotal'], 2) . '</td>'
                . '</tr>';
        }

        $subject = ($isEn ? 'Order received ' : 'Pedido recibido ') . $code . ' - ' . $coopName;
        $html = '<p>' . ($isEn ? 'Hello' : 'Hola') . ' ' . e((string)$order['customer_name']) . ',</p>'
            . '<p>' . ($isEn
                ? 'We received your order <strong>' . e($code) . '</strong> with your payment voucher. It will be verified as soon as possible and we will contact you to coordinate the delivery of your product. We attach a PDF summary of your order.'
                : 'Hemos recibido tu pedido <strong>' . e($code) . '</strong> junto con tu voucher de pago. Será verificado a la brevedad y nos comunicaremos contigo para coordinar el envío de tu producto. Adjuntamos un PDF con el resumen de tu pedido.') . '</p>'
            . '<table style="border-collapse:collapse;width:100%;max-width:480px;">' . $lines . '</table>'
            . '<p><strong>' . ($isEn ? 'Total' : 'Total') . ':</strong> S/ ' . number_format((float)$order['total'], 2) . '<br>'
            . '<strong>' . ($isEn ? 'Payment method' : 'Método de pago') . ':</strong> ' . e((string)$order['payment_method']) . '<br>'
            . '<strong>' . ($isEn ? 'Operation number' : 'N.º de operación') . ':</strong> ' . e((string)$order['payment_operation_number']) . '</p>'
            . '<p>' . ($isEn ? 'Thank you for your preference.' : 'Gracias por tu preferencia.') . '<br>' . e($coopName) . '</p>';

        $tmpPath = self::writeTempPdf($order, $items);

        try {
            SmtpService::enviar($account, [
                'to' => (string)$order['email'],
                'subject' => $subject,
                'html' => $html,
                'adjuntos' => $tmpPath !== null
                    ? [['path' => $tmpPath, 'name' => $code . '.pdf', 'mime' => 'application/pdf']]
                    : [],
            ]);
            activity('Envio confirmacion de pedido ' . $code . ' a ' . $order['email'], 'orders');
        } catch (Throwable $e) {
            app_log('order_confirmation_failed', $e->getMessage(), ['order_id' => (int)$order['id']]);
        } finally {
            if ($tmpPath !== null && is_file($tmpPath)) {
                @unlink($tmpPath);
            }
        }
    }

    /**
     * Genera el PDF del pedido ("ticket de pedido"): un resumen de la compra
     * tal como la registro el cliente, distinto de la nota de venta que solo
     * existe tras aprobar el pedido. Se regenera bajo demanda (no se guarda
     * de forma permanente); al ser un resumen informativo del pedido, no
     * necesita quedar congelado como si fuera un documento de venta.
     * Publico: usado por el correo de confirmacion y por OrderController
     * para la vista/descarga del boton "TCK-PEDIDO" en el dashboard.
     */
    public static function renderPdfBytes(array $order, array $items): string
    {
        $settings = ReceiptService::settings();
        $coopName = (string)($settings['cooperative_name'] ?? 'COOPAECA');
        $html = self::buildPdfHtml($order, $items, $coopName, $settings);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper([0, 0, 226.77, 1600], 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Genera el PDF del pedido en un archivo temporal (no se guarda de forma
     * permanente). Devuelve null si algo falla, para no impedir el envio
     * del correo sin adjunto.
     */
    private static function writeTempPdf(array $order, array $items): ?string
    {
        try {
            $path = sys_get_temp_dir() . '/pedido-' . bin2hex(random_bytes(8)) . '.pdf';
            if (file_put_contents($path, self::renderPdfBytes($order, $items), LOCK_EX) === false) {
                return null;
            }
            return $path;
        } catch (Throwable $e) {
            app_log('order_confirmation_pdf_failed', $e->getMessage(), ['order_id' => (int)($order['id'] ?? 0)]);
            return null;
        }
    }

    private static function buildPdfHtml(array $order, array $items, string $coopName, array $settings): string
    {
        $coopNameSafe = e($coopName);
        $address = e((string)($settings['topbar_address'] ?? ''));
        $phone = e((string)($settings['topbar_phone'] ?? ''));
        $logoDataUri = ReceiptService::logoDataUri($settings);
        $logoHtml = $logoDataUri !== null ? '<img class="brand-logo" src="' . e($logoDataUri) . '" alt="">' : '';

        $rows = '';
        foreach ($items as $item) {
            $rows .= '<tr>'
                . '<td colspan="2">' . e((string)$item['product_name']) . '</td>'
                . '</tr><tr>'
                . '<td>' . (int)$item['quantity'] . ' x S/ ' . number_format((float)$item['unit_price'], 2) . '</td>'
                . '<td class="right">S/ ' . number_format((float)$item['subtotal'], 2) . '</td>'
                . '</tr>';
        }

        $date = e(date('d/m/Y H:i', strtotime((string)$order['created_at'])));
        $code = e((string)$order['code']);
        $customerName = e((string)$order['customer_name']);
        $docType = e((string)$order['document_type']);
        $docNumber = e((string)$order['document_number']);
        $paymentMethod = e((string)$order['payment_method']);
        $operationNumber = e((string)$order['payment_operation_number']);
        $totalFormatted = number_format((float)$order['total'], 2);

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
    .brand { padding: 1mm 0 1.5mm; }
    .brand-logo { display: block; max-width: 49mm; max-height: 17mm; width: auto; height: auto; margin: 0 auto 1.5mm; }
    h1 { font-size: 12px; line-height: 1.15; margin: 0 0 2px; }
    .muted { color: #444; font-size: 8px; }
    hr { border: none; border-top: 1px dashed #000; margin: 6px 0; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 1px 0; font-size: 9px; }
    .totals td { font-size: 10px; padding-top: 2px; }
    .footer { margin-top: 8px; font-size: 8px; }
</style>
</head>
<body>
    <div class="center brand">
        {$logoHtml}
        <h1>{$coopNameSafe}</h1>
        <div class="muted">{$address}</div>
        <div class="muted">{$phone}</div>
    </div>
    <hr>
    <div class="center"><strong>PEDIDO</strong></div>
    <div><strong>N.º:</strong> {$code}</div>
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
    <div class="footer center">Este documento es un resumen de tu pedido (ticket de pedido), no una nota de venta.</div>
</body>
</html>
HTML;
    }
}
