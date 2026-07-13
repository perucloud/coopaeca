<?php

final class SaleService
{
    public static function createManual(array $input, array $voucher, int $userId): array
    {
        $customer = self::validateCustomer($input);
        $items = self::normalizeItems($input);
        self::ensurePaymentMethodAvailable($customer['payment_method']);
        self::ensurePaymentOperationAvailable($customer['payment_operation_number']);

        $voucherFileId = VoucherStorageService::store($voucher, $userId);
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $products = self::loadProducts($items);
            $saleItems = [];
            $subtotal = 0.0;

            foreach ($items as $item) {
                $product = $products[$item['product_id']] ?? null;
                if (!$product) {
                    throw new RuntimeException('Uno de los productos seleccionados no existe o no esta publicado.');
                }
                InventoryService::assertAvailable($item['product_id'], $item['quantity']);
                $unitPrice = $item['unit_price'] ?? self::productPrice($product);
                if ($unitPrice <= 0) {
                    throw new RuntimeException('El precio de venta debe ser mayor a cero.');
                }
                $lineSubtotal = $unitPrice * $item['quantity'];
                $subtotal += $lineSubtotal;
                $saleItems[] = [
                    'product_id' => (int)$product['id'],
                    'product_name' => (string)$product['name'],
                    'product_sku' => $product['sku'] ?: null,
                    'presentation' => $product['presentation'] ?: null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'subtotal' => $lineSubtotal,
                ];
            }

            $code = 'TMP-' . strtoupper(bin2hex(random_bytes(12)));
            $pdo->prepare(
                'INSERT INTO sales
                 (code, order_id, source, status, customer_name, document_type, document_number, phone, whatsapp, email,
                  payment_method, payment_operation_number, voucher_file_id, subtotal, total, confirmed_by, confirmed_at)
                 VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            )->execute([
                $code,
                $customer['source'],
                'confirmada',
                $customer['customer_name'],
                $customer['document_type'],
                $customer['document_number'],
                $customer['phone'],
                $customer['whatsapp'],
                $customer['email'],
                $customer['payment_method'],
                $customer['payment_operation_number'],
                $voucherFileId,
                $subtotal,
                $subtotal,
                $userId,
            ]);

            $saleId = (int)$pdo->lastInsertId();
            $pdo->prepare('UPDATE sales SET code = ? WHERE id = ?')
                ->execute([new_entity_code('VEN', $saleId), $saleId]);
            $stmt = $pdo->prepare(
                'INSERT INTO sale_items (sale_id, product_id, product_name, product_sku, presentation, quantity, unit_price, subtotal)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($saleItems as $item) {
                $stmt->execute([
                    $saleId,
                    $item['product_id'],
                    $item['product_name'],
                    $item['product_sku'],
                    $item['presentation'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['subtotal'],
                ]);
                InventoryService::decreaseForSale((int)$item['product_id'], (int)$item['quantity'], $saleId, $userId);
            }

            $pdo->commit();
            activity('Registro venta manual ' . $code, 'sales');
            return self::find($saleId);
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

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

    public static function find(int $saleId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM sales WHERE id = ? LIMIT 1');
        $stmt->execute([$saleId]);
        $sale = $stmt->fetch();
        if (!$sale) {
            throw new RuntimeException('Venta no encontrada.');
        }
        return $sale;
    }

    private static function validateCustomer(array $input): array
    {
        $documentType = strtoupper(trim((string)($input['document_type'] ?? 'DNI')));
        $documentNumber = preg_replace('/\D+/', '', (string)($input['document_number'] ?? '')) ?: '';
        $whatsapp = preg_replace('/\D+/', '', (string)($input['whatsapp'] ?? '')) ?: '';
        $phone = preg_replace('/\D+/', '', (string)($input['phone'] ?? '')) ?: null;
        $email = strtolower(trim((string)($input['email'] ?? '')));
        $source = trim((string)($input['source'] ?? 'manual'));

        $data = [
            'source' => in_array($source, ['web', 'whatsapp', 'phone', 'manual'], true) ? $source : 'manual',
            'customer_name' => trim((string)($input['customer_name'] ?? '')),
            'document_type' => in_array($documentType, ['DNI', 'RUC'], true) ? $documentType : 'DNI',
            'document_number' => $documentNumber,
            'phone' => $phone,
            'whatsapp' => $whatsapp,
            'email' => $email !== '' ? $email : null,
            'payment_method' => trim((string)($input['payment_method'] ?? '')),
            'payment_operation_number' => self::normalizeOperation((string)($input['payment_operation_number'] ?? '')),
        ];

        $errors = [];
        if (mb_strlen($data['customer_name']) < 3) $errors[] = 'Ingresa el nombre o razon social.';
        if ($data['document_type'] === 'DNI' && strlen($documentNumber) !== 8) $errors[] = 'El DNI debe tener 8 digitos.';
        if ($data['document_type'] === 'RUC' && strlen($documentNumber) !== 11) $errors[] = 'El RUC debe tener 11 digitos.';
        if (strlen($whatsapp) < 9) $errors[] = 'Ingresa un WhatsApp valido.';
        if ($data['email'] !== null && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Correo invalido.';
        if ($data['payment_method'] === '') $errors[] = 'Selecciona un metodo de pago.';
        if ($data['payment_operation_number'] === '') $errors[] = 'Ingresa el numero de operacion.';

        if ($errors) {
            throw new InvalidArgumentException(implode("\n", array_unique($errors)));
        }

        return $data;
    }

    private static function normalizeItems(array $input): array
    {
        $productIds = $input['product_id'] ?? [];
        $quantities = $input['quantity'] ?? [];
        $unitPrices = $input['unit_price'] ?? [];
        if (!is_array($productIds)) $productIds = [$productIds];
        if (!is_array($quantities)) $quantities = [$quantities];
        if (!is_array($unitPrices)) $unitPrices = [$unitPrices];

        $items = [];
        foreach ($productIds as $i => $productId) {
            $id = (int)$productId;
            $quantity = (int)($quantities[$i] ?? 0);
            $unitPriceRaw = trim((string)($unitPrices[$i] ?? ''));
            $unitPrice = $unitPriceRaw !== '' ? (float)$unitPriceRaw : null;
            if ($id <= 0 || $quantity <= 0) {
                continue;
            }
            if ($unitPrice !== null && $unitPrice <= 0) {
                throw new InvalidArgumentException('El precio unitario debe ser mayor a cero.');
            }
            $items[] = ['product_id' => $id, 'quantity' => $quantity, 'unit_price' => $unitPrice];
        }

        if (!$items) {
            throw new InvalidArgumentException('Agrega al menos un producto a la venta.');
        }

        return $items;
    }

    private static function loadProducts(array $items): array
    {
        $ids = array_values(array_unique(array_column($items, 'product_id')));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT id, sku, name, presentation, price, sale_price, stock
             FROM products
             WHERE status = 'published' AND id IN ($placeholders)"
        );
        $stmt->execute($ids);
        $products = [];
        foreach ($stmt->fetchAll() as $product) {
            $products[(int)$product['id']] = $product;
        }
        return $products;
    }

    private static function productPrice(array $product): float
    {
        return $product['sale_price'] !== null ? (float)$product['sale_price'] : (float)$product['price'];
    }

    private static function ensurePaymentOperationAvailable(string $operation): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare("SELECT id FROM sales WHERE payment_operation_number = ? AND status != 'anulada' LIMIT 1");
        $stmt->execute([$operation]);
        if ($stmt->fetch()) {
            throw new RuntimeException('El numero de operacion ya fue registrado en ventas.');
        }
        $stmt = $pdo->prepare("SELECT id FROM orders WHERE payment_operation_number = ? AND status NOT IN ('rechazado','cancelado') LIMIT 1");
        $stmt->execute([$operation]);
        if ($stmt->fetch()) {
            throw new RuntimeException('El numero de operacion ya fue registrado en pedidos.');
        }
    }

    private static function ensurePaymentMethodAvailable(string $name): void
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM payment_methods WHERE name = ? AND is_active = 1 LIMIT 1'
        );
        $stmt->execute([$name]);
        if (!$stmt->fetch()) {
            throw new RuntimeException('Metodo de pago no disponible.');
        }
    }

    private static function normalizeOperation(string $value): string
    {
        $value = strtoupper(trim($value));
        $value = preg_replace('/[^A-Z0-9\-]/', '', $value) ?? '';
        return substr($value, 0, 60);
    }
}
