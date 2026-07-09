<?php
$errors = errors();
$productsJson = json_encode(array_map(fn ($p) => [
    'id' => (int)$p['id'],
    'name' => $p['name'],
    'sku' => $p['sku'],
    'presentation' => $p['presentation'],
    'stock' => $p['stock'] === null ? null : (int)$p['stock'],
], $products), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>

<section class="page-card">
    <div class="page-header">
        <div>
            <h2>Ingreso masivo de stock</h2>
            <span>Registra el reingreso de varios productos a la vez (por ejemplo, cuando llega un lote de compra). Cada linea queda auditada en el historial de inventario.</span>
        </div>
        <a class="button ghost" href="<?= e(url('/inventory')) ?>"><?= icon('arrow-left') ?> Volver</a>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-error"><?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?></div>
    <?php endif; ?>

    <form action="<?= e(url('/inventory/bulk/store')) ?>" method="post" id="bulkStockForm" class="manual-sale-form">
        <?= csrf_field() ?>

        <article class="detail-panel">
            <div class="card-title-row">
                <div>
                    <h3>Productos a ingresar</h3>
                    <p>Selecciona el producto y la cantidad que ingresa. Puedes agregar tantas lineas como necesites.</p>
                </div>
                <button type="button" class="button small" id="addBulkStockItem"><?= icon('plus') ?> Agregar</button>
            </div>
            <div class="manual-sale-items" id="bulkStockItems"></div>
        </article>

        <article class="detail-panel">
            <label>Observacion (motivo del ingreso)
                <input type="text" name="notes" value="<?= e(old('notes')) ?>" required placeholder="Ej. Compra a proveedor, lote julio 2026">
            </label>
            <small class="text-muted">Esta observacion se aplica a todas las lineas de este ingreso.</small>
        </article>

        <div class="action-row">
            <button class="button primary" type="submit"><?= icon('save') ?> Registrar ingreso</button>
        </div>
    </form>
</section>

<template id="bulkStockItemTemplate">
    <div class="manual-sale-item">
        <label>Producto
            <select name="product_id[]" class="bulk-stock-product" required>
                <option value="">Selecciona producto</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= (int)$product['id'] ?>">
                        <?= e($product['name']) ?><?= $product['stock'] !== null ? ' - Stock actual: ' . (int)$product['stock'] : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Cantidad que ingresa
            <input type="number" name="quantity[]" class="bulk-stock-quantity" min="1" value="1" required>
        </label>
        <button type="button" class="button small danger bulk-stock-remove">Quitar</button>
    </div>
</template>

<script>
window.BULK_STOCK_PRODUCTS = <?= $productsJson ?: '[]' ?>;
</script>
