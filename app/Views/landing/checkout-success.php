<?php
$activeLandingNav = 'productos';
require __DIR__ . '/partials/header.php';
$isEn = landing_lang() === 'en';
$waPhone = $settings['whatsapp_products'] ?? $settings['topbar_phone'] ?? '51999999999';
$waPhone = preg_replace('/\D+/', '', (string)$waPhone) ?: '51999999999';
$lines = [];
foreach ($items as $item) {
    $lines[] = '- ' . $item['product_name'] . ' x ' . (int)$item['quantity'] . ' = S/ ' . number_format((float)$item['subtotal'], 2);
}
$message = ($isEn ? 'Hello, I generated my COOPAECA order.' : 'Hola, genere mi pedido COOPAECA.')
    . "\nCodigo: " . $order['code']
    . "\nCliente: " . $order['customer_name']
    . "\nDocumento: " . $order['document_type'] . ' ' . $order['document_number']
    . "\nDireccion: " . $order['address'] . ', ' . $order['district'] . ', ' . $order['province'] . ', ' . $order['region']
    . "\nProductos:\n" . implode("\n", $lines)
    . "\nTotal: S/ " . number_format((float)$order['total'], 2)
    . "\nMetodo de pago: " . $order['payment_method']
    . "\nOperacion: " . $order['payment_operation_number']
    . "\n" . ($isEn ? 'The voucher was uploaded in the system.' : 'El voucher fue adjuntado en el sistema.');
$waLink = whatsapp_link($waPhone, $message);
$statusLabels = [
    'pendiente' => $isEn ? 'Pending' : 'Pendiente',
    'voucher_enviado' => $isEn ? 'Voucher sent' : 'Voucher enviado',
    'en_revision' => $isEn ? 'Under review' : 'En revisión',
    'aprobado' => $isEn ? 'Approved' : 'Aprobado',
    'rechazado' => $isEn ? 'Rejected' : 'Rechazado',
    'cancelado' => $isEn ? 'Cancelled' : 'Cancelado',
];
$statusLabel = $statusLabels[$order['status']] ?? ucfirst(str_replace('_', ' ', (string)$order['status']));
?>

<section class="checkout-success" data-clear-cart="1">
    <div class="lp-container">
        <div class="success-card">
            <div class="success-icon"><?= icon('check') ?></div>
            <span><?= e($isEn ? 'Order registered' : 'Pedido registrado') ?></span>
            <h1><?= e($order['code']) ?></h1>
            <p><?= e($isEn ? 'Your purchase request was saved with voucher. COOPAECA will validate the payment and stock before confirming the sale.' : 'Tu solicitud de compra fue guardada con voucher. COOPAECA validara el pago y el stock antes de confirmar la venta.') ?></p>

            <div class="success-summary">
                <div><span><?= e($isEn ? 'Customer' : 'Comprador') ?></span><strong><?= e($order['customer_name']) ?></strong></div>
                <div><span>Total</span><strong>S/ <?= e(number_format((float)$order['total'], 2)) ?></strong></div>
                <div><span><?= e($isEn ? 'Status' : 'Estado') ?></span><strong><?= e($statusLabel) ?></strong></div>
                <div><span><?= e($isEn ? 'Payment' : 'Pago') ?></span><strong><?= e($order['payment_method']) ?></strong></div>
                <div class="span-full"><span><?= e($isEn ? 'Delivery address' : 'Direccion de entrega') ?></span><strong><?= e($order['address']) ?><?= $order['address_reference'] ? ' (' . e($order['address_reference']) . ')' : '' ?>, <?= e($order['district']) ?>, <?= e($order['province']) ?>, <?= e($order['region']) ?></strong></div>
            </div>

            <div class="success-items">
                <?php foreach ($items as $item): ?>
                    <div>
                        <span><?= e($item['product_name']) ?> x <?= (int)$item['quantity'] ?></span>
                        <strong>S/ <?= e(number_format((float)$item['subtotal'], 2)) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="success-actions">
                <a href="<?= e($waLink) ?>" class="lp-btn lp-btn-primary" target="_blank" rel="noopener"><?= icon('message-circle') ?> <?= e($isEn ? 'Send summary by WhatsApp' : 'Enviar resumen por WhatsApp') ?></a>
                <a href="<?= e(lurl('/#productos')) ?>" class="checkout-nav-btn"><?= e($isEn ? 'Back to products' : 'Volver a productos') ?></a>
            </div>
        </div>
    </div>
</section>

<!-- Modal automatico: evidencia del pedido por WhatsApp -->
<div class="success-wa-modal" id="successWaModal" role="dialog" aria-modal="true" aria-labelledby="successWaTitle">
    <div class="success-wa-box">
        <div class="success-wa-icon"><?= icon('check-circle') ?></div>
        <h2 id="successWaTitle"><?= e($isEn ? 'Order registered!' : '¡Pedido registrado!') ?></h2>
        <p class="success-wa-code"><?= e($order['code']) ?></p>
        <p><?= e($isEn
            ? 'Your voucher was received by the system and will be verified as soon as possible. We will contact you to coordinate the delivery of your product.'
            : 'Tu voucher fue recibido por el sistema y será verificado a la brevedad. Nos comunicaremos contigo para coordinar el envío de tu producto.') ?></p>
        <p class="success-wa-important"><strong><?= e($isEn ? 'Important:' : 'Importante:') ?></strong> <?= e($isEn
            ? 'to leave evidence of your order, send it to the company WhatsApp.'
            : 'para dejar evidencia de tu pedido, envíalo al WhatsApp de la empresa.') ?></p>
        <a href="<?= e($waLink) ?>" class="lp-btn lp-btn-primary success-wa-send" id="successWaSend" target="_blank" rel="noopener"><?= icon('message-circle') ?> <?= e($isEn ? 'Send order by WhatsApp' : 'Enviar pedido por WhatsApp') ?></a>
        <button type="button" class="success-wa-skip" id="successWaSkip"><?= e($isEn ? 'Continue without sending' : 'Continuar sin enviar') ?></button>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('successWaModal');
    if (!modal) return;
    document.body.classList.add('success-wa-open');
    function close() { modal.classList.add('is-closed'); document.body.classList.remove('success-wa-open'); }
    document.getElementById('successWaSkip').addEventListener('click', close);
    document.getElementById('successWaSend').addEventListener('click', function () { setTimeout(close, 400); });
    // No se cierra al hacer clic fuera: el envio por WhatsApp es la accion esperada.
})();
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
