<section class="card">
    <div class="section-title row">
        <div>
            <h2>Productos</h2>
            <span>Gestiona los productos mostrados en el landing page.</span>
        </div>
        <a class="button primary" href="<?= e(url('/products/create')) ?>"><?= icon('package') ?> Nuevo producto</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:64px"></th>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Precio</th>
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
                    <td data-label="Estado"><span class="badge <?= $item['status'] === 'published' ? 'ok' : 'off' ?>"><?= $item['status'] === 'published' ? 'Publicado' : 'Borrador' ?></span></td>
                    <td data-label="Destacado"><?= $item['is_featured'] ? '<span class="badge accent">★ Destacado</span>' : '<span class="text-muted">—</span>' ?></td>
                    <td class="actions">
                        <a class="button small" href="<?= e(url('/products/edit?id=' . $item['id'])) ?>">Editar</a>
                        <form method="post" action="<?= e(url('/products/delete')) ?>" data-confirm="¿Eliminar este producto?">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($item['id']) ?>">
                            <button class="button small ghost" type="submit">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$items): ?>
                <tr><td colspan="7" class="empty-state">
                    <div class="empty-icon"><?= icon('package') ?></div>
                    <p>Sin productos registrados.</p>
                    <a class="button primary" href="<?= e(url('/products/create')) ?>">Crear primer producto</a>
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
