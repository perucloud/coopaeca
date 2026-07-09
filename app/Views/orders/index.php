<?php
$statusLabels = [
    'pendiente' => 'Pendiente',
    'voucher_enviado' => 'Voucher enviado',
    'en_revision' => 'En revision',
    'aprobado' => 'Aprobado',
    'rechazado' => 'Rechazado',
    'cancelado' => 'Cancelado',
];
$badgeClass = [
    'aprobado' => 'ok',
    'rechazado' => 'off',
    'cancelado' => 'off',
    'en_revision' => 'warn',
    'voucher_enviado' => 'muted',
    'pendiente' => 'muted',
];
?>

<section class="page-card">
    <div class="page-header">
        <div>
            <h2>Pedidos</h2>
            <span>Valida compras web, vouchers y disponibilidad antes de generar ventas.</span>
        </div>
    </div>

    <div class="stats-grid compact">
        <?php foreach ($statusLabels as $key => $label): ?>
        <div class="stat-card soft">
            <span><?= e($label) ?></span>
            <strong><?= (int)($stats[$key] ?? 0) ?></strong>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="filter-panel">
        <div class="filter-panel-head">
            <span class="filter-panel-icon"><?= icon('search') ?></span>
            <div>
                <strong>Filtros de búsqueda</strong>
                <span>Ubica pedidos por código, comprador, estado o fecha.</span>
            </div>
        </div>
        <form method="get" action="<?= e(url('/orders')) ?>" class="filter-grid">
            <label class="filter-field wide">
                <span>Buscar</span>
                <input class="form-control" type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="Código, comprador, DNI/RUC, WhatsApp u operación">
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
                <a class="button ghost" href="<?= e(url('/orders')) ?>">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Pedido</th>
                <th>Comprador</th>
                <th>Items</th>
                <th>Pago</th>
                <th>Total</th>
                <th>Estado</th>
                <th>Fecha</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td data-label="Pedido"><strong><?= e($order['code']) ?></strong></td>
                <td data-label="Comprador">
                    <strong><?= e($order['customer_name']) ?></strong>
                    <span class="text-muted"><?= e($order['document_type'] . ' ' . $order['document_number']) ?> · <?= e($order['whatsapp']) ?></span>
                </td>
                <td data-label="Items"><?= (int)$order['items_count'] ?> prod. / <?= (int)$order['units_count'] ?> und.</td>
                <td data-label="Pago">
                    <?= e($order['payment_method']) ?><br>
                    <span class="text-muted"><?= e($order['payment_operation_number']) ?></span>
                </td>
                <td data-label="Total"><strong>S/ <?= number_format((float)$order['total'], 2) ?></strong></td>
                <td data-label="Estado"><span class="badge <?= e($badgeClass[$order['status']] ?? 'muted') ?>"><?= e($statusLabels[$order['status']] ?? $order['status']) ?></span></td>
                <td data-label="Fecha"><?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></td>
                <td class="actions"><a class="button small" href="<?= e(url('/orders/show?id=' . (int)$order['id'])) ?>">Ver</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$orders): ?>
            <tr><td colspan="8" class="empty-state">No hay pedidos para los filtros seleccionados.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
