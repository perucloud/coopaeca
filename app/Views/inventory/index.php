<section class="page-card">
    <div class="page-header">
        <div>
            <h2>Inventario</h2>
            <span>Stock actual de productos y ultimo movimiento auditado.</span>
        </div>
        <button type="button" class="button primary" id="toggleBulkMode">
            <span class="bulk-label-off"><?= icon('plus') ?> Ingreso masivo</span>
            <span class="bulk-label-on" hidden><?= icon('x') ?> Cancelar ingreso masivo</span>
        </button>
    </div>

    <div class="filter-panel">
        <div class="filter-panel-head">
            <span class="filter-panel-icon"><?= icon('search') ?></span>
            <div>
                <strong>Filtros de búsqueda</strong>
                <span>Ubica productos por nombre, SKU, estado o nivel de stock.</span>
            </div>
        </div>
        <form method="get" action="<?= e(url('/inventory')) ?>" class="filter-grid">
            <label class="filter-field wide">
                <span>Buscar</span>
                <input class="form-control" type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="Nombre o SKU">
            </label>
            <label class="filter-field">
                <span>Estado</span>
                <select class="form-control" name="status">
                    <option value="">Todos</option>
                    <option value="published" <?= $filters['status'] === 'published' ? 'selected' : '' ?>>Publicado</option>
                    <option value="draft" <?= $filters['status'] === 'draft' ? 'selected' : '' ?>>Borrador</option>
                </select>
            </label>
            <label class="filter-field">
                <span>Nivel de stock</span>
                <select class="form-control" name="stock_level">
                    <option value="">Todos</option>
                    <option value="low" <?= $filters['stock_level'] === 'low' ? 'selected' : '' ?>>Stock bajo (≤ 5)</option>
                    <option value="out" <?= $filters['stock_level'] === 'out' ? 'selected' : '' ?>>Sin stock</option>
                </select>
            </label>
            <div class="filter-actions">
                <button class="button primary" type="submit"><?= icon('search') ?> Filtrar</button>
                <a class="button ghost" href="<?= e(url('/inventory')) ?>">Limpiar</a>
            </div>
        </form>
    </div>

    <form method="post" action="<?= e(url('/inventory/bulk/store')) ?>" id="bulkStockForm">
        <?= csrf_field() ?>

        <div class="bulk-stock-bar" id="bulkStockBar" hidden>
            <label class="bulk-stock-notes">Motivo del ingreso
                <input type="text" name="notes" placeholder="Ej. Compra a proveedor, lote julio 2026" required>
            </label>
            <div class="bulk-stock-bar-actions">
                <button type="button" class="button ghost" id="cancelBulkMode">Cancelar</button>
                <button type="submit" class="button primary"><?= icon('save') ?> Guardar cambios</button>
            </div>
        </div>

        <div class="table-wrap">
            <table class="inventory-table" id="inventoryTable">
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
                        <span class="stock-view">
                            <?php if ($product['stock'] === null): ?>
                            <span class="badge muted">Sin control</span>
                            <?php else: ?>
                            <strong><?= (int)$product['stock'] ?></strong> und.
                            <?php endif; ?>
                        </span>
                        <span class="stock-edit">
                            <span class="stock-edit-current"><?= $product['stock'] === null ? '0' : (int)$product['stock'] ?> und. actual</span>
                            <input type="number" min="0" class="form-control bulk-stock-input" name="quantity[<?= (int)$product['id'] ?>]" placeholder="+0">
                        </span>
                    </td>
                    <td data-label="Estado"><span class="badge <?= $product['status'] === 'published' ? 'ok' : 'muted' ?>"><?= e($product['status']) ?></span></td>
                    <td data-label="Ultimo movimiento">
                        <?= e($product['last_movement_type'] ?: '-') ?>
                        <?php if (!empty($product['last_movement_at'])): ?><br><span class="text-muted"><?= e(date('d/m/Y H:i', strtotime($product['last_movement_at']))) ?></span><?php endif; ?>
                    </td>
                    <td class="actions">
                        <button type="button" class="button small icon-only action-add adjust-open"
                                data-id="<?= (int)$product['id'] ?>"
                                data-name="<?= e($product['name']) ?>"
                                data-stock="<?= $product['stock'] === null ? 'Sin control' : (int)$product['stock'] . ' und.' ?>"
                                title="Ajustar stock"><?= icon('plus') ?></button>
                        <a class="button small action-movements" href="<?= e(url('/inventory/movements?product_id=' . (int)$product['id'])) ?>"><?= icon('history') ?> Movimientos</a>
                        <?php if (can('products', 'edit')): ?>
                        <a class="button small icon-only action-edit" href="<?= e(url('/products/edit?id=' . (int)$product['id'])) ?>" title="Editar producto"><?= icon('edit') ?></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$products): ?>
                <tr><td colspan="7" class="empty-state">No hay productos para mostrar.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </form>
</section>

<!-- Modal de ajuste individual de stock -->
<div class="modal-overlay" id="adjustModal" style="display:none">
    <div class="modal-box modal-sm">
        <div class="modal-header">
            <h3>Ajustar stock</h3>
            <button type="button" class="modal-close" id="adjustModalClose">&times;</button>
        </div>
        <form method="post" action="<?= e(url('/inventory/adjust')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" id="adjustProductId">
            <div class="modal-body">
                <p class="adjust-product-name" id="adjustProductName"></p>
                <p class="text-muted" id="adjustProductStock"></p>
                <label>Cantidad (usa signo negativo para reducir)
                    <input type="number" name="delta" id="adjustDelta" placeholder="+10 o -3" required>
                </label>
                <label>Motivo del ajuste
                    <input type="text" name="notes" required placeholder="Ej. Merma, conteo fisico, reingreso">
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" class="button ghost" id="adjustModalCancel">Cancelar</button>
                <button type="submit" class="button primary">Registrar ajuste</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('adjustModal');
    var productIdInput = document.getElementById('adjustProductId');
    var productName = document.getElementById('adjustProductName');
    var productStock = document.getElementById('adjustProductStock');
    var deltaInput = document.getElementById('adjustDelta');

    function openModal(button) {
        productIdInput.value = button.dataset.id;
        productName.textContent = button.dataset.name;
        productStock.textContent = 'Stock actual: ' + button.dataset.stock;
        deltaInput.value = '';
        modal.style.display = 'flex';
    }
    function closeModal() {
        modal.style.display = 'none';
    }

    document.querySelectorAll('.adjust-open').forEach(function (btn) {
        btn.addEventListener('click', function () { openModal(btn); });
    });
    document.getElementById('adjustModalClose').addEventListener('click', closeModal);
    document.getElementById('adjustModalCancel').addEventListener('click', closeModal);
    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeModal();
    });

    var table = document.getElementById('inventoryTable');
    var bar = document.getElementById('bulkStockBar');
    var toggleBtn = document.getElementById('toggleBulkMode');
    var cancelBtn = document.getElementById('cancelBulkMode');
    var bulkForm = document.getElementById('bulkStockForm');

    var labelOff = toggleBtn.querySelector('.bulk-label-off');
    var labelOn = toggleBtn.querySelector('.bulk-label-on');

    function setBulkMode(active) {
        table.classList.toggle('bulk-mode', active);
        bar.hidden = !active;
        labelOff.hidden = active;
        labelOn.hidden = !active;
        if (!active) {
            bulkForm.querySelectorAll('.bulk-stock-input').forEach(function (input) { input.value = ''; });
            bulkForm.querySelector('input[name="notes"]').value = '';
        }
    }

    toggleBtn.addEventListener('click', function () {
        setBulkMode(!table.classList.contains('bulk-mode'));
    });
    cancelBtn.addEventListener('click', function () { setBulkMode(false); });

    bulkForm.addEventListener('submit', function (event) {
        if (!table.classList.contains('bulk-mode')) {
            event.preventDefault();
            return;
        }
        var hasQuantity = Array.from(bulkForm.querySelectorAll('.bulk-stock-input')).some(function (input) {
            return Number(input.value) > 0;
        });
        if (!hasQuantity) {
            event.preventDefault();
            alert('Ingresa una cantidad en al menos un producto.');
        }
    });
})();
</script>
