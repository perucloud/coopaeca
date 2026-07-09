<?php

final class SaleService
{
    public static function cancel(int $saleId, int $userId, string $notes): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT * FROM sales WHERE id = ? FOR UPDATE');
            $stmt->execute([$saleId]);
            $sale = $stmt->fetch();
            if (!$sale) {
                throw new RuntimeException('Venta no encontrada.');
            }
            if ($sale['status'] === 'anulada') {
                throw new RuntimeException('La venta ya esta anulada.');
            }

            $items = $pdo->prepare('SELECT * FROM sale_items WHERE sale_id = ? ORDER BY id ASC');
            $items->execute([$saleId]);
            foreach ($items->fetchAll() as $item) {
                InventoryService::reverseSale((int)$item['product_id'], (int)$item['quantity'], $saleId, $userId);
            }

            $adminNotes = trim($notes) ?: 'Venta anulada desde dashboard.';
            $pdo->prepare(
                "UPDATE sales
                 SET status = 'anulada', cancelled_by = ?, cancelled_at = NOW(), updated_at = NOW()
                 WHERE id = ?"
            )->execute([$userId, $saleId]);

            if (!empty($sale['order_id'])) {
                $pdo->prepare(
                    "UPDATE orders
                     SET status = 'cancelado', admin_notes = ?, cancelled_at = NOW(), updated_at = NOW()
                     WHERE id = ?"
                )->execute([$adminNotes, (int)$sale['order_id']]);
            }

            $pdo->commit();
            activity('Anulo venta #' . $saleId, 'sales');
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
