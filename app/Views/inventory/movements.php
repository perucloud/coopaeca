<section class="page-card">
    <div class="page-header">
        <div>
            <h2><?= e($product['name']) ?></h2>
            <span>Stock actual: <?= $product['stock'] === null ? 'sin control' : (int)$product['stock'] . ' unidades' ?></span>
        </div>
        <a class="button ghost" href="<?= e(url('/inventory')) ?>"><?= icon('arrow-left') ?> Volver</a>
    </div>

    <?php if (can('inventory', 'adjust')): ?>
    <form method="post" action="<?= e(url('/inventory/adjust')) ?>" class="inventory-adjust">
        <?= csrf_field() ?>
        <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
        <div>
            <label>Ajuste</label>
            <input class="form-control" type="number" name="delta" placeholder="+10 o -3" required>
        </div>
        <div>
            <label>Observacion</label>
            <input class="form-control" type="text" name="notes" placeholder="Motivo del ajuste" required>
        </div>
        <button class="button primary" type="submit">Registrar ajuste</button>
    </form>
    <?php endif; ?>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Fecha</th><th>Tipo</th><th>Cantidad</th><th>Antes</th><th>Despues</th><th>Referencia</th><th>Usuario</th><th>Observacion</th></tr></thead>
            <tbody>
            <?php foreach ($movements as $movement): ?>
            <tr>
                <td><?= e(date('d/m/Y H:i', strtotime($movement['created_at']))) ?></td>
                <td><span class="badge muted"><?= e($movement['movement_type']) ?></span></td>
                <td><?= (int)$movement['quantity'] ?></td>
                <td><?= $movement['stock_before'] === null ? '-' : (int)$movement['stock_before'] ?></td>
                <td><?= $movement['stock_after'] === null ? '-' : (int)$movement['stock_after'] ?></td>
                <td><?= e($movement['reference_type'] . ($movement['reference_id'] ? ' #' . $movement['reference_id'] : '')) ?></td>
                <td><?= e($movement['user_name'] ?: '-') ?></td>
                <td><?= e($movement['notes'] ?: '-') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$movements): ?>
            <tr><td colspan="8" class="empty-state">Este producto todavia no tiene movimientos.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
