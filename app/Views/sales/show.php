<?php
$voucherUrl = !empty($sale['voucher_path']) ? url('/' . $sale['voucher_path']) : null;
?>

<section class="page-card">
    <div class="page-header">
        <div>
            <h2><?= e($sale['code']) ?></h2>
            <span><?= e(ucfirst($sale['status'])) ?> · S/ <?= number_format((float)$sale['total'], 2) ?></span>
        </div>
        <a class="button ghost" href="<?= e(url('/sales')) ?>"><?= icon('arrow-left') ?> Volver</a>
    </div>

    <div class="detail-grid">
        <article class="detail-panel">
            <h3>Comprador</h3>
            <dl class="detail-list">
                <div><dt>Nombre</dt><dd><?= e($sale['customer_name']) ?></dd></div>
                <div><dt>Documento</dt><dd><?= e($sale['document_type'] . ' ' . $sale['document_number']) ?></dd></div>
                <div><dt>WhatsApp</dt><dd><?= e($sale['whatsapp']) ?></dd></div>
                <div><dt>Correo</dt><dd><?= e($sale['email'] ?: '-') ?></dd></div>
            </dl>
        </article>
        <article class="detail-panel">
            <h3>Operacion</h3>
            <dl class="detail-list">
                <div><dt>Pedido</dt><dd><?= e($sale['order_code'] ?: '-') ?></dd></div>
                <div><dt>Origen</dt><dd><?= e($sale['source']) ?></dd></div>
                <div><dt>Metodo</dt><dd><?= e($sale['payment_method']) ?></dd></div>
                <div><dt>Operacion</dt><dd><?= e($sale['payment_operation_number']) ?></dd></div>
                <div><dt>Voucher</dt><dd><?= $voucherUrl ? '<a href="' . e($voucherUrl) . '" target="_blank" rel="noopener">Ver archivo</a>' : '-' ?></dd></div>
            </dl>
        </article>
    </div>

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

    <?php if ($sale['status'] !== 'anulada'): ?>
    <form method="post" action="<?= e(url('/sales/cancel')) ?>" class="action-row" onsubmit="return confirm('Anular esta venta y revertir stock?')">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$sale['id'] ?>">
        <input class="form-control" name="notes" placeholder="Motivo de anulacion">
        <button class="button danger" type="submit">Anular venta</button>
    </form>
    <?php endif; ?>
</section>
