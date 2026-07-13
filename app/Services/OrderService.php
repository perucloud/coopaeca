<?php

final class OrderService
{
    public static function createFromCheckout(array $input, array $voucher, Request $request): array
    {
        $items = self::normalizeCart($input['items'] ?? '');
        $customer = self::validateCustomer($input);
        self::ensurePaymentMethodAvailable($customer['payment_method']);
        self::ensurePaymentOperationAvailable($customer['payment_operation_number']);

        $products = self::loadProductsForCart($items);
        $subtotal = 0.0;
        $orderItems = [];

        foreach ($items as $item) {
            $product = $products[$item['product_id']] ?? null;
            if (!$product) {
                throw new RuntimeException('Uno de los productos ya no esta disponible.');
            }
            $unitPrice = self::productPrice($product);
            if ($unitPrice <= 0) {
                throw new RuntimeException('El producto ' . $product['name'] . ' no tiene precio valido.');
            }
            $lineSubtotal = $unitPrice * $item['quantity'];
            $subtotal += $lineSubtotal;
            $orderItems[] = [
                'product_id' => (int)$product['id'],
                'product_name' => (string)$product['name'],
                'product_sku' => $product['sku'] ?: null,
                'presentation' => $product['presentation'] ?: null,
                'quantity' => $item['quantity'],
                'unit_price' => $unitPrice,
                'subtotal' => $lineSubtotal,
            ];
        }

        $voucherFileId = VoucherStorageService::store($voucher);
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            foreach ($orderItems as $item) {
                InventoryService::assertAvailable($item['product_id'], $item['quantity']);
            }

            // Codigo temporal unico; el definitivo (PED-000007-10-07-26) se
            // fija con el id autoincremental dentro de la misma transaccion.
            $code = 'TMP-' . strtoupper(bin2hex(random_bytes(12)));
            $pdo->prepare(
                'INSERT INTO orders
                 (code, source, status, customer_name, document_type, document_number, phone, whatsapp, email,
                  region, province, district, address, address_reference, payment_method, payment_operation_number,
                  voucher_file_id, subtotal, total, customer_notes, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $code,
                'web',
                'voucher_enviado',
                $customer['customer_name'],
                $customer['document_type'],
                $customer['document_number'],
                $customer['phone'],
                $customer['whatsapp'],
                $customer['email'],
                $customer['region'],
                $customer['province'],
                $customer['district'],
                $customer['address'],
                $customer['address_reference'],
                $customer['payment_method'],
                $customer['payment_operation_number'],
                $voucherFileId,
                $subtotal,
                $subtotal,
                $customer['customer_notes'],
                $request->ip() ?: null,
                substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255) ?: null,
            ]);

            $orderId = (int)$pdo->lastInsertId();
            $pdo->prepare('UPDATE orders SET code = ? WHERE id = ?')
                ->execute([new_entity_code('PED', $orderId), $orderId]);
            $stmt = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, product_name, product_sku, presentation, quantity, unit_price, subtotal)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($orderItems as $item) {
                $stmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['product_name'],
                    $item['product_sku'],
                    $item['presentation'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['subtotal'],
                ]);
            }

            $pdo->commit();
            return self::findOrder($orderId);
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function approve(int $orderId, int $adminId): array
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $order = self::findOrderForUpdate($orderId);
            if (!in_array($order['status'], ['voucher_enviado', 'en_revision', 'pendiente'], true)) {
                throw new RuntimeException('Este pedido ya fue procesado.');
            }

            $items = self::orderItems($orderId);
            if (!$items) {
                throw new RuntimeException('El pedido no tiene productos.');
            }

            foreach ($items as $item) {
                InventoryService::assertAvailable((int)$item['product_id'], (int)$item['quantity']);
            }

            $saleCode = 'TMP-' . strtoupper(bin2hex(random_bytes(12)));
            $pdo->prepare(
                'INSERT INTO sales
                 (code, order_id, source, status, customer_name, document_type, document_number, phone, whatsapp, email,
                  payment_method, payment_operation_number, voucher_file_id, subtotal, total, confirmed_by, confirmed_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            )->execute([
                $saleCode,
                $orderId,
                $order['source'],
                'confirmada',
                $order['customer_name'],
                $order['document_type'],
                $order['document_number'],
                $order['phone'],
                $order['whatsapp'],
                $order['email'],
                $order['payment_method'],
                $order['payment_operation_number'],
                $order['voucher_file_id'],
                $order['subtotal'],
                $order['total'],
                $adminId,
            ]);

            $saleId = (int)$pdo->lastInsertId();
            $pdo->prepare('UPDATE sales SET code = ? WHERE id = ?')
                ->execute([new_entity_code('VEN', $saleId), $saleId]);
            $saleItem = $pdo->prepare(
                'INSERT INTO sale_items (sale_id, product_id, product_name, product_sku, presentation, quantity, unit_price, subtotal)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );

            foreach ($items as $item) {
                $saleItem->execute([
                    $saleId,
                    $item['product_id'],
                    $item['product_name'],
                    $item['product_sku'],
                    $item['presentation'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['subtotal'],
                ]);
                InventoryService::decreaseForSale((int)$item['product_id'], (int)$item['quantity'], $saleId, $adminId);
            }

            $pdo->prepare(
                "UPDATE orders
                 SET status = 'aprobado', approved_by = ?, approved_at = NOW(), updated_at = NOW()
                 WHERE id = ?"
            )->execute([$adminId, $orderId]);

            $pdo->commit();
            return ['order' => self::findOrder($orderId), 'sale_id' => $saleId];
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function reject(int $orderId, int $adminId, string $notes): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE orders
             SET status = 'rechazado', admin_notes = ?, rejected_at = NOW(), updated_at = NOW()
             WHERE id = ? AND status NOT IN ('aprobado','rechazado','cancelado')"
        );
        $stmt->execute([trim($notes) ?: 'Pago no validado.', $orderId]);
        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('No se pudo rechazar el pedido.');
        }
        activity('Rechazo pedido #' . $orderId, 'orders');
    }

    public static function markReview(int $orderId): void
    {
        Database::connection()->prepare(
            "UPDATE orders SET status = 'en_revision', updated_at = NOW() WHERE id = ? AND status = 'voucher_enviado'"
        )->execute([$orderId]);
    }

    public static function findOrder(int $orderId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT o.*, f.disk_path AS voucher_path, f.mime_type AS voucher_mime, f.original_name AS voucher_name,
                    u.name AS approved_by_name
             FROM orders o
             JOIN files f ON f.id = o.voucher_file_id
             LEFT JOIN users u ON u.id = o.approved_by
             WHERE o.id = ? LIMIT 1'
        );
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        if (!$order) {
            throw new RuntimeException('Pedido no encontrado.');
        }
        return $order;
    }

    public static function orderItems(int $orderId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC');
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    private static function findOrderForUpdate(int $orderId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM orders WHERE id = ? FOR UPDATE');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        if (!$order) {
            throw new RuntimeException('Pedido no encontrado.');
        }
        return $order;
    }

    private static function normalizeCart(string|array $raw): array
    {
        $items = is_array($raw) ? $raw : json_decode($raw, true);
        if (!is_array($items) || !$items) {
            throw new RuntimeException('El carrito esta vacio.');
        }

        $normalized = [];
        foreach ($items as $item) {
            $productId = (int)($item['product_id'] ?? $item['id'] ?? 0);
            $quantity = (int)($item['quantity'] ?? 0);
            if ($productId <= 0 || $quantity <= 0) {
                throw new RuntimeException('El carrito tiene cantidades invalidas.');
            }
            $normalized[$productId] = [
                'product_id' => $productId,
                'quantity' => ($normalized[$productId]['quantity'] ?? 0) + $quantity,
            ];
        }

        return array_values($normalized);
    }

    private static function validateCustomer(array $input): array
    {
        $documentType = strtoupper(trim((string)($input['document_type'] ?? 'DNI')));
        $documentNumber = preg_replace('/\D+/', '', (string)($input['document_number'] ?? '')) ?: '';
        $whatsapp = preg_replace('/\D+/', '', (string)($input['whatsapp'] ?? '')) ?: '';
        $phone = preg_replace('/\D+/', '', (string)($input['phone'] ?? '')) ?: null;
        $email = strtolower(trim((string)($input['email'] ?? '')));

        $data = [
            'customer_name' => trim((string)($input['customer_name'] ?? '')),
            'document_type' => in_array($documentType, ['DNI', 'RUC'], true) ? $documentType : 'DNI',
            'document_number' => $documentNumber,
            'phone' => $phone,
            'whatsapp' => $whatsapp,
            'email' => $email !== '' ? $email : null,
            'region' => trim((string)($input['region'] ?? '')),
            'province' => trim((string)($input['province'] ?? '')),
            'district' => trim((string)($input['district'] ?? '')),
            'address' => trim((string)($input['address'] ?? '')),
            'address_reference' => trim((string)($input['address_reference'] ?? '')) ?: null,
            'payment_method' => trim((string)($input['payment_method'] ?? '')),
            'payment_operation_number' => self::normalizeOperation((string)($input['payment_operation_number'] ?? '')),
            'customer_notes' => trim((string)($input['customer_notes'] ?? '')) ?: null,
        ];

        $errors = [];
        if (mb_strlen($data['customer_name']) < 3) $errors[] = 'Ingresa el nombre o razon social.';
        if ($data['document_type'] === 'DNI' && strlen($documentNumber) !== 8) $errors[] = 'El DNI debe tener 8 digitos.';
        if ($data['document_type'] === 'RUC' && strlen($documentNumber) !== 11) $errors[] = 'El RUC debe tener 11 digitos.';
        if (strlen($whatsapp) < 9) $errors[] = 'Ingresa un WhatsApp valido.';
        if ($data['email'] !== null && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Correo invalido.';
        foreach (['region', 'province', 'district', 'address'] as $key) {
            if ($data[$key] === '') $errors[] = 'Completa la direccion de entrega.';
        }
        if ($data['payment_method'] === '') $errors[] = 'Selecciona un metodo de pago.';
        if ($data['payment_operation_number'] === '') $errors[] = 'Ingresa el numero de operacion.';

        if ($errors) {
            throw new InvalidArgumentException(implode("\n", array_unique($errors)));
        }

        return $data;
    }

    private static function loadProductsForCart(array $items): array
    {
        $ids = array_column($items, 'product_id');
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

    private static function productPrice(array $product): float
    {
        return $product['sale_price'] !== null ? (float)$product['sale_price'] : (float)$product['price'];
    }

    private static function ensurePaymentOperationAvailable(string $operation): void
    {
        $stmt = Database::connection()->prepare(
            "SELECT id FROM orders
             WHERE payment_operation_number = ?
               AND status NOT IN ('rechazado','cancelado')
             LIMIT 1"
        );
        $stmt->execute([$operation]);
        if ($stmt->fetch()) {
            throw new RuntimeException('El numero de operacion ya fue registrado.');
        }
    }

    private static function normalizeOperation(string $value): string
    {
        $value = strtoupper(trim($value));
        $value = preg_replace('/[^A-Z0-9\-]/', '', $value) ?? '';
        return substr($value, 0, 60);
    }
}
