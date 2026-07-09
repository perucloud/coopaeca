<?php

final class InventoryService
{
    public static function assertAvailable(int $productId, int $quantity): void
    {
        $stmt = Database::connection()->prepare('SELECT id, name, stock FROM products WHERE id = ? FOR UPDATE');
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        if (!$product) {
            throw new RuntimeException('Producto no encontrado.');
        }

        if ($product['stock'] !== null && (int)$product['stock'] < $quantity) {
            throw new RuntimeException('Stock insuficiente para ' . $product['name'] . '.');
        }
    }

    public static function decreaseForSale(int $productId, int $quantity, int $saleId, ?int $userId): void
    {
        $stmt = Database::connection()->prepare('SELECT id, stock FROM products WHERE id = ? FOR UPDATE');
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        if (!$product) {
            throw new RuntimeException('Producto no encontrado.');
        }

        $before = $product['stock'] === null ? null : (int)$product['stock'];
        $after = $before === null ? null : $before - $quantity;
        if ($after !== null && $after < 0) {
            throw new RuntimeException('La venta dejaria stock negativo.');
        }

        if ($after !== null) {
            Database::connection()->prepare('UPDATE products SET stock = ?, updated_at = NOW() WHERE id = ?')
                ->execute([$after, $productId]);
        }

        self::record($productId, 'salida_venta', $quantity, $before, $after, 'sale', $saleId, 'Salida por venta confirmada.', $userId);
    }

    public static function reverseSale(int $productId, int $quantity, int $saleId, ?int $userId): void
    {
        $stmt = Database::connection()->prepare('SELECT id, stock FROM products WHERE id = ? FOR UPDATE');
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        if (!$product) {
            throw new RuntimeException('Producto no encontrado.');
        }

        $before = $product['stock'] === null ? null : (int)$product['stock'];
        $after = $before === null ? null : $before + $quantity;
        if ($after !== null) {
            Database::connection()->prepare('UPDATE products SET stock = ?, updated_at = NOW() WHERE id = ?')
                ->execute([$after, $productId]);
        }

        self::record($productId, 'anulacion_venta', $quantity, $before, $after, 'sale', $saleId, 'Reversion por venta anulada.', $userId);
    }

    public static function adjust(int $productId, int $delta, string $notes, ?int $userId): void
    {
        if ($delta === 0) {
            throw new RuntimeException('El ajuste debe ser diferente de cero.');
        }

        $stmt = Database::connection()->prepare('SELECT id, stock FROM products WHERE id = ? FOR UPDATE');
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        if (!$product) {
            throw new RuntimeException('Producto no encontrado.');
        }

        $before = $product['stock'] === null ? 0 : (int)$product['stock'];
        $after = $before + $delta;
        if ($after < 0) {
            throw new RuntimeException('El ajuste dejaria stock negativo.');
        }

        Database::connection()->prepare('UPDATE products SET stock = ?, updated_at = NOW() WHERE id = ?')
            ->execute([$after, $productId]);

        $type = $delta > 0 ? 'entrada_manual' : 'ajuste_manual';
        self::record($productId, $type, abs($delta), $before, $after, 'manual', null, $notes, $userId);
    }

    private static function record(
        int $productId,
        string $type,
        int $quantity,
        ?int $before,
        ?int $after,
        string $referenceType,
        ?int $referenceId,
        string $notes,
        ?int $userId
    ): void {
        Database::connection()->prepare(
            'INSERT INTO stock_movements (product_id, movement_type, quantity, stock_before, stock_after, reference_type, reference_id, notes, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([$productId, $type, $quantity, $before, $after, $referenceType, $referenceId, $notes, $userId]);
    }
}
