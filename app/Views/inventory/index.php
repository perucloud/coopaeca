<section class="page-card">
    <div class="page-header">
        <div>
            <h2>Inventario</h2>
            <span>Stock actual de productos y ultimo movimiento auditado.</span>
        </div>
        <a class="button primary" href="<?= e(url('/inventory/bulk')) ?>"><?= icon('plus') ?> Ingreso masivo</a>
    </div>

    <form method="get" action="<?= e(url('/inventory')) ?>" class="filters-bar">
        <input class="form-control" type="text" name="q" value="<?= e($q) ?>" placeholder="Buscar producto o SKU">
        <button class="button primary" type="submit"><?= icon('search') ?> Buscar</button>
        <a class="button ghost" href="<?= e(url('/inventory')) ?>">Limpiar</a>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
            <tr><th>Producto</th><th>SKU</th><th>Precio</th><th>Stock</th><th>Estado</th><th>Ultimo movimiento</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($products as $product): ?>
            <tr>
                <td data-label="Producto"><strong><?= e($product['name']) ?></strong><br><span class="text-muted"><?= e($product['presentation'] ?: '-') ?></span></td>
                <td data-label="SKU"><?= e($product['sku'] ?: '-') ?></td>
                <td data-label="Precio">S/ <?= number_format((float)($product['sale_price'] ?? $product['price']), 2) ?></td>
                <td data-label="Stock">
                    <?php if ($product['stock'] === null): ?>
                    <span class="badge muted">Sin control</span>
                    <?php else: ?>
                    <strong><?= (int)$product['stock'] ?></strong> und.
                    <?php endif; ?>
                </td>
                <td data-label="Estado"><span class="badge <?= $product['status'] === 'published' ? 'ok' : 'muted' ?>"><?= e($product['status']) ?></span></td>
                <td data-label="Ultimo movimiento">
                    <?= e($product['last_movement_type'] ?: '-') ?>
                    <?php if (!empty($product['last_movement_at'])): ?><br><span class="text-muted"><?= e(date('d/m/Y H:i', strtotime($product['last_movement_at']))) ?></span><?php endif; ?>
                </td>
                <td class="actions"><a class="button small" href="<?= e(url('/inventory/movements?product_id=' . (int)$product['id'])) ?>">Movimientos</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$products): ?>
            <tr><td colspan="7" class="empty-state">No hay productos para mostrar.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
