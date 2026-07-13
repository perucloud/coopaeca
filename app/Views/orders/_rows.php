<?php
/**
 * Filas de la tabla de Pedidos. Parcial compartido entre el render inicial
 * (orders/index.php) y el auto-refresh (OrderController::rows), que
 * devuelve solo este fragmento para reemplazar el <tbody> sin F5.
 * Espera: $orders, $latestDeliveries, $statusLabels, $badgeClass,
 * $deliveryLabels, $deliveryBadges.
 */
$pendingStatuses = ['pendiente', 'voucher_enviado'];
?>
<?php foreach ($orders as $order): $delivery = $latestDeliveries[(int)($order['sale_id'] ?? 0)] ?? null; $products = array_filter(explode(', ', (string)($order['product_names'] ?? ''))); $isPending = in_array($order['status'], $pendingStatuses, true); ?>
  <tr data-order-id="<?= (int)$order['id'] ?>" class="<?= $isPending ? 'order-row-pending' : '' ?>">
   <td data-label="Pedido / cliente"><strong><?= e(display_code('PED', (int)$order['id'], $order['code'] ?? null)) ?></strong><div><?= e($order['customer_name']) ?></div><small><?= e($order['document_type'] . ' ' . $order['document_number']) ?></small></td>
   <td data-label="Contacto"><div><?= e($order['whatsapp'] ?: $order['phone']) ?></div><small><?= e($order['email'] ?: 'Sin correo') ?></small></td>
   <td data-label="Compra"><div><?= (int)$order['units_count'] ?> und. · <?= e($products[0] ?? '-') ?></div><?php if (count($products) > 1): ?><small>+<?= count($products) - 1 ?> producto(s)</small><?php endif ?><small><?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></small></td>
   <td data-label="Pago"><div><?= e($order['payment_method']) ?></div><small>N.º <?= e($order['payment_operation_number']) ?></small></td>
   <td data-label="Total"><strong>S/ <?= number_format((float)$order['total'], 2) ?></strong></td>
   <td data-label="Estado">
    <?php if ($isPending): ?>
    <span class="badge alert"><?= icon('bell') ?> <?= $order['status'] === 'pendiente' ? 'Por revisar' : 'Voucher · Aprobar pedido' ?></span>
    <?php elseif ($order['status'] === 'aprobado'): ?>
    <span class="badge badge-strong-ok"><?= icon('check-circle') ?> Aprobado</span>
    <?php elseif ($order['status'] === 'cancelado'): ?>
    <span class="badge badge-strong-off"><?= icon('x') ?> Anulado</span>
    <?php else: ?>
    <span class="badge <?= e($badgeClass[$order['status']] ?? 'muted') ?>"><?= e($statusLabels[$order['status']] ?? $order['status']) ?></span>
    <?php endif; ?>
   </td>
   <td data-label="Último envío"><?php if ($delivery): ?><span class="badge <?= e($deliveryBadges[$delivery['status']] ?? 'muted') ?>"><?= e($deliveryLabels[$delivery['status']] ?? $delivery['status']) ?></span><small><?= $delivery['channel'] === 'email' ? 'Correo' : 'WhatsApp' ?> · <?= e(date('d/m/Y H:i', strtotime($delivery['attempted_at']))) ?></small><?php else: ?><span class="text-muted">Sin intentos</span><?php endif ?></td>
   <td class="actions order-actions" data-label="Acciones"><a class="button small action-view <?= $isPending ? 'action-view-siren' : '' ?>" href="<?= e(url('/orders/show?id=' . (int)$order['id'])) ?>" data-order-open data-order-url="<?= e(url('/orders/detail?id=' . (int)$order['id'])) ?>" data-order-code="<?= e(display_code('PED', (int)$order['id'], $order['code'] ?? null)) ?>"><?= icon('eye') ?> Ver pedido</a><button class="button small action-voucher" type="button" data-voucher-open data-voucher-url="<?= e(url('/orders/voucher/view?id=' . (int)$order['id'])) ?>" data-voucher-code="<?= e(display_code('PED', (int)$order['id'], $order['code'] ?? null)) ?>" data-voucher-mime="<?= e((string)($order['voucher_mime'] ?? '')) ?>"><?= icon('file') ?> Voucher</button><?php if ($order['sale_id']): ?><?php $saleCancelled = $order['status'] === 'cancelado'; ?><button class="button small action-cancel-sale" type="button" <?= $saleCancelled ? 'disabled aria-disabled="true"' : '' ?> data-cancel-sale-open data-cancel-sale-id="<?= (int)$order['sale_id'] ?>" data-cancel-sale-code="<?= e(display_code('VEN', (int)$order['sale_id'], $order['sale_code'] ?? null)) ?>"><?= icon('x') ?> Anular</button><?php endif ?><a class="button small action-receipt" href="<?= e(url('/orders/ticket/view?id=' . (int)$order['id'])) ?>" target="_blank" rel="noopener"><?= icon('file') ?> TCK-PEDIDO</a></td>
  </tr>
 <?php endforeach ?><?php if (!$orders): ?><tr><td colspan="8" class="empty-state">No hay pedidos para los filtros seleccionados.</td></tr><?php endif ?>
