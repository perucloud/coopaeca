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
        <a class="button primary" href="<?= e(url('/sales/create')) ?>"><?= icon('plus') ?> Nueva venta</a>
    </div>

    <div class="stats-grid compact">
        <div class="stat-card soft"><span>Ventas</span><strong><?= (int)($stats['total_sales'] ?? 0) ?></strong></div>
        <div class="stat-card soft"><span>Confirmado</span><strong>S/ <?= number_format((float)($stats['total_confirmed'] ?? 0), 2) ?></strong></div>
        <div class="stat-card soft"><span>Web</span><strong>S/ <?= number_format((float)($stats['total_web'] ?? 0), 2) ?></strong></div>
        <div class="stat-card soft"><span>WhatsApp</span><strong>S/ <?= number_format((float)($stats['total_whatsapp'] ?? 0), 2) ?></strong></div>
    </div>

    <div class="filter-panel">
        <div class="filter-panel-head">
            <span class="filter-panel-icon"><?= icon('search') ?></span>
            <div>
                <strong>Filtros de búsqueda</strong>
                <span>Ubica ventas por código, comprador, origen, estado o fecha.</span>
            </div>
        </div>
        <form method="get" action="<?= e(url('/sales')) ?>" class="filter-grid">
            <label class="filter-field wide">
                <span>Buscar</span>
                <input class="form-control" type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="Venta, comprador, documento, WhatsApp u operación">
            </label>
            <label class="filter-field">
                <span>Origen</span>
                <select class="form-control" name="source">
                    <option value="">Todos</option>
                    <?php foreach ($sourceLabels as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $filters['source'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="filter-field">
                <span>Estado</span>
                <select class="form-control" name="status">
                    <option value="">Todos</option>
                    <?php foreach ($statusLabels as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $filters['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="filter-field">
                <span>Desde</span>
                <input class="form-control" type="date" name="from" value="<?= e($filters['from']) ?>">
            </label>
            <label class="filter-field">
                <span>Hasta</span>
                <input class="form-control" type="date" name="to" value="<?= e($filters['to']) ?>">
            </label>
            <div class="filter-actions">
                <button class="button primary" type="submit"><?= icon('search') ?> Filtrar</button>
                <a class="button ghost" href="<?= e(url('/sales')) ?>">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr><th>Venta</th><th>Comprador</th><th>Origen</th><th>Items</th><th>Pago</th><th>Total</th><th>Estado</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($sales as $sale): ?>
            <tr>
                <td data-label="Venta">
                    <strong><?= e(short_code('VEN', (int)$sale['id'])) ?></strong><br>
                    <span class="text-muted"><?= $sale['order_id'] ? e(short_code('PED', (int)$sale['order_id'])) : 'Sin pedido' ?></span>
                </td>
                <td data-label="Comprador"><strong><?= e($sale['customer_name']) ?></strong><br><span class="text-muted"><?= e($sale['document_type'] . ' ' . $sale['document_number']) ?></span></td>
                <td data-label="Origen"><?= e($sourceLabels[$sale['source']] ?? $sale['source']) ?></td>
                <td data-label="Items"><?= (int)$sale['items_count'] ?> prod. / <?= (int)$sale['units_count'] ?> und.</td>
                <td data-label="Pago"><?= e($sale['payment_method']) ?><br><span class="text-muted"><?= e($sale['payment_operation_number']) ?></span></td>
                <td data-label="Total"><strong>S/ <?= number_format((float)$sale['total'], 2) ?></strong></td>
                <td data-label="Estado"><span class="badge <?= $sale['status'] === 'confirmada' ? 'ok' : 'off' ?>"><?= e($statusLabels[$sale['status']] ?? $sale['status']) ?></span></td>
                <td class="actions">
                    <a class="button small" href="<?= e(url('/sales/show?id=' . (int)$sale['id'])) ?>">Ver</a>
                    <?php if ($sale['status'] === 'confirmada'): ?>
                        <?php if ($sale['receipt_file_id']): ?>
                        <a class="button small info" href="<?= e(url('/sales/receipt/view?id=' . (int)$sale['id'])) ?>" target="_blank" rel="noopener"><?= icon('file') ?> Ticket</a>
                        <?php elseif (can('sales', 'create')): ?>
                        <form method="post" action="<?= e(url('/sales/receipt/issue')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$sale['id'] ?>">
                            <input type="hidden" name="redirect" value="<?= e(url('/sales')) ?>">
                            <button class="button small info" type="submit"><?= icon('printer') ?> Emitir</button>
                        </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$sales): ?>
            <tr><td colspan="8" class="empty-state">No hay ventas registradas para los filtros seleccionados.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
