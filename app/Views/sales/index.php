<?php
$sourceLabels = ['web' => 'Web', 'whatsapp' => 'WhatsApp', 'phone' => 'Telefono', 'manual' => 'Manual'];
$statusLabels = ['confirmada' => 'Confirmada', 'anulada' => 'Anulada', 'entregada' => 'Entregada'];
?>

<section class="page-card">
    <div class="page-header">
        <div>
            <h2>Ventas</h2>
            <span>Registro comercial definitivo generado por pedidos aprobados o ventas confirmadas.</span>
        </div>
    </div>

    <div class="stats-grid compact">
        <div class="stat-card soft"><span>Ventas</span><strong><?= (int)($stats['total_sales'] ?? 0) ?></strong></div>
        <div class="stat-card soft"><span>Confirmado</span><strong>S/ <?= number_format((float)($stats['total_confirmed'] ?? 0), 2) ?></strong></div>
        <div class="stat-card soft"><span>Web</span><strong>S/ <?= number_format((float)($stats['total_web'] ?? 0), 2) ?></strong></div>
        <div class="stat-card soft"><span>WhatsApp</span><strong>S/ <?= number_format((float)($stats['total_whatsapp'] ?? 0), 2) ?></strong></div>
    </div>

    <form method="get" action="<?= e(url('/sales')) ?>" class="filters-bar">
        <input class="form-control" type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="Buscar venta, comprador, documento, WhatsApp u operacion">
        <input class="form-control" type="date" name="from" value="<?= e($filters['from']) ?>">
        <input class="form-control" type="date" name="to" value="<?= e($filters['to']) ?>">
        <select class="form-control" name="source">
            <option value="">Todos los origenes</option>
            <?php foreach ($sourceLabels as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= $filters['source'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <select class="form-control" name="status">
            <option value="">Todos los estados</option>
            <?php foreach ($statusLabels as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= $filters['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="button primary" type="submit"><?= icon('search') ?> Filtrar</button>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
            <tr><th>Venta</th><th>Comprador</th><th>Origen</th><th>Items</th><th>Pago</th><th>Total</th><th>Estado</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($sales as $sale): ?>
            <tr>
                <td data-label="Venta"><strong><?= e($sale['code']) ?></strong><br><span class="text-muted"><?= e($sale['order_code'] ?: 'Sin pedido') ?></span></td>
                <td data-label="Comprador"><strong><?= e($sale['customer_name']) ?></strong><br><span class="text-muted"><?= e($sale['document_type'] . ' ' . $sale['document_number']) ?></span></td>
                <td data-label="Origen"><?= e($sourceLabels[$sale['source']] ?? $sale['source']) ?></td>
                <td data-label="Items"><?= (int)$sale['items_count'] ?> prod. / <?= (int)$sale['units_count'] ?> und.</td>
                <td data-label="Pago"><?= e($sale['payment_method']) ?><br><span class="text-muted"><?= e($sale['payment_operation_number']) ?></span></td>
                <td data-label="Total"><strong>S/ <?= number_format((float)$sale['total'], 2) ?></strong></td>
                <td data-label="Estado"><span class="badge <?= $sale['status'] === 'confirmada' ? 'ok' : 'off' ?>"><?= e($statusLabels[$sale['status']] ?? $sale['status']) ?></span></td>
                <td class="actions"><a class="button small" href="<?= e(url('/sales/show?id=' . (int)$sale['id'])) ?>">Ver</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$sales): ?>
            <tr><td colspan="8" class="empty-state">No hay ventas registradas para los filtros seleccionados.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
