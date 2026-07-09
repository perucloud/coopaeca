<?php
$statusLabels = [
    'pendiente' => 'Pendiente',
    'voucher_enviado' => 'Voucher enviado',
    'en_revision' => 'En revision',
    'aprobado' => 'Aprobado',
    'rechazado' => 'Rechazado',
    'cancelado' => 'Cancelado',
];
$canProcess = in_array($order['status'], ['pendiente', 'voucher_enviado', 'en_revision'], true);
$voucherUrl = url('/' . $order['voucher_path']);
?>

<section class="page-card">
    <div class="page-header">
        <div>
            <h2><?= e($order['code']) ?></h2>
            <span>Pedido <?= e($statusLabels[$order['status']] ?? $order['status']) ?> · S/ <?= number_format((float)$order['total'], 2) ?></span>
        </div>
        <a class="button ghost" href="<?= e(url('/orders')) ?>"><?= icon('arrow-left') ?> Volver</a>
    </div>

    <div class="detail-grid">
        <article class="detail-panel">
            <h3>Comprador</h3>
            <dl class="detail-list">
                <div><dt>Nombre</dt><dd><?= e($order['customer_name']) ?></dd></div>
                <div><dt>Documento</dt><dd><?= e($order['document_type'] . ' ' . $order['document_number']) ?></dd></div>
                <div><dt>WhatsApp</dt><dd><?= e($order['whatsapp']) ?></dd></div>
                <div><dt>Celular</dt><dd><?= e($order['phone'] ?: '-') ?></dd></div>
                <div><dt>Correo</dt><dd><?= e($order['email'] ?: '-') ?></dd></div>
            </dl>
        </article>

        <article class="detail-panel">
            <h3>Direccion</h3>
            <dl class="detail-list">
                <div><dt>Region</dt><dd><?= e($order['region']) ?></dd></div>
                <div><dt>Provincia</dt><dd><?= e($order['province']) ?></dd></div>
                <div><dt>Distrito</dt><dd><?= e($order['district']) ?></dd></div>
                <div><dt>Direccion</dt><dd><?= e($order['address']) ?></dd></div>
                <div><dt>Referencia</dt><dd><?= e($order['address_reference'] ?: '-') ?></dd></div>
            </dl>
        </article>

        <article class="detail-panel">
            <h3>Pago</h3>
            <dl class="detail-list">
                <div><dt>Metodo</dt><dd><?= e($order['payment_method']) ?></dd></div>
                <div><dt>Operacion</dt><dd><?= e($order['payment_operation_number']) ?></dd></div>
                <div><dt>Total</dt><dd>S/ <?= number_format((float)$order['total'], 2) ?></dd></div>
                <div><dt>Voucher</dt><dd><a href="<?= e($voucherUrl) ?>" target="_blank" rel="noopener">Ver archivo</a></dd></div>
            </dl>
        </article>
    </div>

    <div class="detail-split">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Producto</th><th>SKU</th><th>Presentacion</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th></tr></thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= e($item['product_name']) ?></td>
                    <td><?= e($item['product_sku'] ?: '-') ?></td>
                    <td><?= e($item['presentation'] ?: '-') ?></td>
                    <td><?= (int)$item['quantity'] ?></td>
                    <td>S/ <?= number_format((float)$item['unit_price'], 2) ?></td>
                    <td><strong>S/ <?= number_format((float)$item['subtotal'], 2) ?></strong></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <aside class="detail-panel voucher-panel">
            <?php if (str_starts_with((string)$order['voucher_mime'], 'image/')): ?>
            <a href="<?= e($voucherUrl) ?>" target="_blank" rel="noopener">
                <img src="<?= e($voucherUrl) ?>" alt="Voucher">
            </a>
            <?php else: ?>
            <div class="file-preview"><?= icon('file') ?><span><?= e($order['voucher_name']) ?></span></div>
            <?php endif; ?>
        </aside>
    </div>

    <?php if ($canProcess): ?>
    <div class="action-row">
        <?php if ($order['status'] === 'voucher_enviado'): ?>
        <form method="post" action="<?= e(url('/orders/review')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
            <button class="button ghost" type="submit">Marcar en revision</button>
        </form>
        <?php endif; ?>
        <form method="post" action="<?= e(url('/orders/approve')) ?>" onsubmit="return confirm('Aprobar este pedido, generar venta y descontar stock?')">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
            <button class="button primary" type="submit"><?= icon('check-circle') ?> Aprobar pedido</button>
        </form>
        <form method="post" action="<?= e(url('/orders/reject')) ?>" class="reject-form" onsubmit="return confirm('Rechazar este pedido?')">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
            <input class="form-control" name="admin_notes" placeholder="Motivo de rechazo">
            <button class="button danger" type="submit">Rechazar</button>
        </form>
    </div>
    <?php endif; ?>
</section>
