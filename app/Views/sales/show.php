<?php
$voucherUrl = !empty($sale['voucher_path']) ? url('/' . $sale['voucher_path']) : null;
?>

<section class="page-card">
    <div class="page-header">
        <div>
            <h2><?= e(short_code('VEN', (int)$sale['id'])) ?></h2>
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
                <div><dt>Pedido</dt><dd><?= $sale['order_id'] ? e(short_code('PED', (int)$sale['order_id'])) : '-' ?></dd></div>
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

    <?php if ($sale['status'] === 'confirmada'): ?>
    <div class="action-row">
        <?php if (!$sale['receipt_file_id']): ?>
            <?php if (can('sales', 'create')): ?>
            <form method="post" action="<?= e(url('/sales/receipt/issue')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$sale['id'] ?>">
                <input type="hidden" name="redirect" value="<?= e(url('/sales/show?id=' . (int)$sale['id'])) ?>">
                <button class="button primary" type="submit"><?= icon('printer') ?> Emitir ticket</button>
            </form>
            <?php endif; ?>
        <?php else: ?>
            <a class="button ghost" href="<?= e(url('/sales/receipt/view?id=' . (int)$sale['id'])) ?>" target="_blank" rel="noopener"><?= icon('file') ?> Ver ticket</a>
            <?php if (can('sales', 'create')): ?>
            <button type="button" class="button ghost" id="openEmailReceiptModal"><?= icon('mail') ?> Enviar por correo</button>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($sale['status'] !== 'anulada'): ?>
    <form method="post" action="<?= e(url('/sales/cancel')) ?>" class="action-row" onsubmit="return confirm('Anular esta venta y revertir stock?')">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$sale['id'] ?>">
        <input class="form-control" name="notes" placeholder="Motivo de anulacion">
        <button class="button danger" type="submit">Anular venta</button>
    </form>
    <?php endif; ?>
</section>

<?php if ($sale['status'] === 'confirmada' && $sale['receipt_file_id'] && can('sales', 'create')): ?>
<div class="modal-overlay" id="emailReceiptModal" style="display:none">
    <div class="modal-box modal-sm">
        <div class="modal-header">
            <h3>Enviar ticket por correo</h3>
            <button type="button" class="modal-close" id="emailReceiptModalClose">&times;</button>
        </div>
        <form method="post" action="<?= e(url('/sales/receipt/email')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$sale['id'] ?>">
            <input type="hidden" name="redirect" value="<?= e(url('/sales/show?id=' . (int)$sale['id'])) ?>">
            <div class="modal-body">
                <label>Correo electronico
                    <input class="form-control" type="email" name="email" value="<?= e($sale['email'] ?: '') ?>" placeholder="cliente@correo.com" required autofocus>
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" class="button ghost" id="emailReceiptModalCancel">Cancelar</button>
                <button type="submit" class="button primary"><?= icon('mail') ?> Enviar</button>
            </div>
        </form>
    </div>
</div>
<script>
(function () {
    var modal = document.getElementById('emailReceiptModal');
    var openBtn = document.getElementById('openEmailReceiptModal');
    if (!modal || !openBtn) return;
    function open() { modal.style.display = 'flex'; }
    function close() { modal.style.display = 'none'; }
    openBtn.addEventListener('click', open);
    document.getElementById('emailReceiptModalClose')?.addEventListener('click', close);
    document.getElementById('emailReceiptModalCancel')?.addEventListener('click', close);
    modal.addEventListener('click', function (event) { if (event.target === modal) close(); });
})();
</script>
<?php endif; ?>
