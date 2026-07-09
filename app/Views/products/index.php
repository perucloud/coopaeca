<section class="card">
    <div class="section-title row">
        <div>
            <h2>Productos</h2>
            <span>Gestiona los productos mostrados en el landing page.</span>
        </div>
        <div class="header-actions">
            <a class="button primary" href="<?= e(url('/products/create')) ?>"><?= icon('package') ?> Nuevo producto</a>
            <?php if (can('inventory')): ?>
            <a class="button ghost" href="<?= e(url('/inventory')) ?>"><?= icon('boxes') ?> Actualizar inventario</a>
            <?php endif; ?>
            <button type="button" class="button ghost" id="openPdfModal"><?= icon('printer') ?> Imprimir PDF</button>
        </div>
    </div>

    <div class="filter-panel">
        <div class="filter-panel-head">
            <span class="filter-panel-icon"><?= icon('search') ?></span>
            <div>
                <strong>Filtros de búsqueda</strong>
                <span>Refina el catálogo por nombre, categoría, estado o destacados.</span>
            </div>
        </div>
        <form method="get" action="<?= e(url('/products')) ?>" class="filter-grid">
            <label class="filter-field wide">
                <span>Buscar</span>
                <input class="form-control" type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="Nombre o SKU">
            </label>
            <label class="filter-field">
                <span>Categoría</span>
                <select class="form-control" name="category_id">
                    <option value="">Todas</option>
                    <?php foreach ($allCategories as $cat): ?>
                    <option value="<?= (int)$cat['id'] ?>" <?= $filters['category_id'] === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
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
                <span>Destacado</span>
                <select class="form-control" name="featured">
                    <option value="">Todos</option>
                    <option value="1" <?= $filters['featured'] === '1' ? 'selected' : '' ?>>Solo destacados</option>
                </select>
            </label>
            <div class="filter-actions">
                <button class="button primary" type="submit"><?= icon('search') ?> Filtrar</button>
                <a class="button ghost" href="<?= e(url('/products')) ?>">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:64px"></th>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Estado</th>
                    <th>Destacado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item):
                $cover = $item['cover_path'] ?? null;
                $catNames = [];
                if (!empty($categories[(int)$item['id']])) {
                    foreach ($categories[(int)$item['id']] as $c) {
                        $catNames[] = $c['cat_name'];
                    }
                }
                $catLabel = $catNames ? implode(', ', $catNames) : '-';
            ?>
                <tr>
                    <td>
                        <div class="table-thumb">
                            <?php if ($cover): ?>
                                <img src="<?= e(url('/' . $cover)) ?>" alt="<?= e($item['name']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="table-thumb-placeholder"><?= icon('package') ?></div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td data-label="Producto">
                        <strong class="table-title"><?= e($item['name']) ?></strong>
                        <span class="table-meta"><?= e($item['sku'] ?? 'Sin SKU') ?></span>
                    </td>
                    <td data-label="Categoría"><span class="badge muted"><?= e($catLabel) ?></span></td>
                    <td data-label="Precio">
                        <?php if ($item['sale_price'] !== null): ?>
                            <span class="price-old">S/ <?= e(number_format((float)$item['price'], 2)) ?></span><br>
                            <span class="price-offer">S/ <?= e(number_format((float)$item['sale_price'], 2)) ?></span>
                        <?php else: ?>
                            <span class="price-regular">S/ <?= e(number_format((float)$item['price'], 2)) ?></span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Stock">
                        <?php if ($item['stock'] === null): ?>
                            <span class="badge muted">Sin control</span>
                        <?php elseif ((int)$item['stock'] <= 5): ?>
                            <span class="badge off"><?= (int)$item['stock'] ?> und.</span>
                        <?php else: ?>
                            <strong><?= (int)$item['stock'] ?></strong> und.
                        <?php endif; ?>
                    </td>
                    <td data-label="Estado"><span class="badge <?= $item['status'] === 'published' ? 'ok' : 'off' ?>"><?= $item['status'] === 'published' ? 'Publicado' : 'Borrador' ?></span></td>
                    <td data-label="Destacado"><?= $item['is_featured'] ? '<span class="badge accent">★ Destacado</span>' : '<span class="text-muted">—</span>' ?></td>
                    <td class="actions products-actions">
                        <a class="button small action-edit" href="<?= e(url('/products/edit?id=' . $item['id'])) ?>"><?= icon('edit') ?> Editar</a>
                        <form method="post" action="<?= e(url('/products/delete')) ?>" data-confirm="¿Eliminar este producto?">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($item['id']) ?>">
                            <button class="button small action-delete" type="submit"><?= icon('trash') ?> Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$items && !array_filter($filters)): ?>
                <tr><td colspan="8" class="empty-state">
                    <div class="empty-icon"><?= icon('package') ?></div>
                    <p>Sin productos registrados.</p>
                    <a class="button primary" href="<?= e(url('/products/create')) ?>">Crear primer producto</a>
                </td></tr>
            <?php elseif (!$items): ?>
                <tr><td colspan="8" class="empty-state">
                    <div class="empty-icon"><?= icon('search') ?></div>
                    <p>Ningún producto coincide con los filtros aplicados.</p>
                    <a class="button ghost" href="<?= e(url('/products')) ?>">Limpiar filtros</a>
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Modal de previsualizacion e impresion de PDF -->
<div class="modal-overlay" id="pdfModal" style="display:none">
    <div class="modal-box modal-xl">
        <div class="modal-header">
            <h3>Listado de productos en PDF</h3>
            <button type="button" class="modal-close" id="pdfModalClose">&times;</button>
        </div>
        <div class="modal-body pdf-modal-body">
            <iframe id="pdfFrame" class="pdf-frame" title="Previsualizacion PDF"></iframe>
        </div>
        <div class="modal-footer">
            <a class="button ghost" id="pdfDownload" href="<?= e(url('/products/pdf')) ?>" download="productos.pdf"><?= icon('download') ?> Descargar</a>
            <button type="button" class="button primary" id="pdfPrint"><?= icon('printer') ?> Imprimir</button>
        </div>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('pdfModal');
    var frame = document.getElementById('pdfFrame');
    var openBtn = document.getElementById('openPdfModal');
    var closeBtn = document.getElementById('pdfModalClose');
    var printBtn = document.getElementById('pdfPrint');
    var pdfUrl = <?= json_encode(url('/products/pdf')) ?>;
    var loaded = false;

    openBtn.addEventListener('click', function () {
        if (!loaded) {
            frame.src = pdfUrl;
            loaded = true;
        }
        modal.style.display = 'flex';
    });
    closeBtn.addEventListener('click', function () { modal.style.display = 'none'; });
    modal.addEventListener('click', function (event) {
        if (event.target === modal) modal.style.display = 'none';
    });
    printBtn.addEventListener('click', function () {
        try {
            frame.contentWindow.focus();
            frame.contentWindow.print();
        } catch (error) {
            window.open(pdfUrl, '_blank');
        }
    });
})();
</script>
