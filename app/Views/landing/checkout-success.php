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
                <div><span><?= e($isEn ? 'Status' : 'Estado') ?></span><strong><?= e($order['status']) ?></strong></div>
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
                <a href="https://wa.me/<?= e($waPhone) ?>?text=<?= urlencode($message) ?>" class="lp-btn lp-btn-primary" target="_blank" rel="noopener"><?= icon('message-circle') ?> <?= e($isEn ? 'Send summary by WhatsApp' : 'Enviar resumen por WhatsApp') ?></a>
                <a href="<?= e(lurl('/#productos')) ?>" class="checkout-nav-btn"><?= e($isEn ? 'Back to products' : 'Volver a productos') ?></a>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
