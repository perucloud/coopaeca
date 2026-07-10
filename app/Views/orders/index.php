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
                <th>Apellidos y nombres</th>
                <th>DNI/RUC</th>
                <th>Telefono / WhatsApp</th>
                <th>Cantidad</th>
                <th>Producto</th>
                <th>Tipo de pago</th>
                <th>Total</th>
                <th>Estado</th>
                <th>Fecha compra</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td data-label="Pedido">
                    <strong>PED-<?= str_pad((string)$order['id'], 6, '0', STR_PAD_LEFT) ?></strong>
                    <span class="text-muted" title="<?= e($order['code']) ?>"><?= e($order['code']) ?></span>
                </td>
                <td data-label="Apellidos y nombres"><?= e($order['customer_name']) ?></td>
                <td data-label="DNI/RUC"><?= e($order['document_type'] . ' ' . $order['document_number']) ?></td>
                <td data-label="Telefono / WhatsApp">
                    <?php if ($order['phone'] && $order['phone'] !== $order['whatsapp']): ?>
                        <?= e($order['phone']) ?> / <?= e($order['whatsapp']) ?>
                    <?php else: ?>
                        <?= e($order['whatsapp']) ?>
                    <?php endif; ?>
                </td>
                <td data-label="Cantidad"><?= (int)$order['units_count'] ?> und.</td>
                <td data-label="Producto">
                    <?php
                        $productNames = array_filter(explode(', ', (string)($order['product_names'] ?? '')));
                        $firstProduct = $productNames[0] ?? '-';
                        $extraCount = count($productNames) - 1;
                    ?>
                    <?= e($firstProduct) ?><?php if ($extraCount > 0): ?> <span class="text-muted">+<?= $extraCount ?> mas</span><?php endif; ?>
                </td>
                <td data-label="Tipo de pago">
                    <?= e($order['payment_method']) ?><br>
                    <span class="text-muted">N° <?= e($order['payment_operation_number']) ?></span>
                </td>
                <td data-label="Total"><strong>S/ <?= number_format((float)$order['total'], 2) ?></strong></td>
                <td data-label="Estado"><span class="badge <?= e($badgeClass[$order['status']] ?? 'muted') ?>"><?= e($statusLabels[$order['status']] ?? $order['status']) ?></span></td>
                <td data-label="Fecha compra"><?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></td>
                <td class="actions">
                    <a class="button small" href="<?= e(url('/orders/show?id=' . (int)$order['id'])) ?>">Ver</a>
                    <?php if ($order['status'] === 'aprobado' && $order['sale_id']): ?>
                        <?php if ($order['sale_receipt_file_id']): ?>
                        <a class="button small info" href="<?= e(url('/sales/receipt/view?id=' . (int)$order['sale_id'])) ?>" target="_blank" rel="noopener"><?= icon('file') ?> Ticket</a>
                        <?php elseif (can('sales', 'create')): ?>
                        <form method="post" action="<?= e(url('/sales/receipt/issue')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$order['sale_id'] ?>">
                            <input type="hidden" name="redirect" value="<?= e(url('/orders')) ?>">
                            <button class="button small info" type="submit"><?= icon('printer') ?> Emitir</button>
                        </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$orders): ?>
            <tr><td colspan="11" class="empty-state">No hay pedidos para los filtros seleccionados.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
