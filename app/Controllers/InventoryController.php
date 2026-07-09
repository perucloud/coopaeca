<?php

final class InventoryController extends Controller
{
    public function index(): void
    {
        $q = trim((string)($_GET['q'] ?? ''));
        $where = ["p.status IN ('draft','published')"];
        $params = [];
        if ($q !== '') {
            $where[] = '(p.name LIKE ? OR p.sku LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like);
        }

        $stmt = Database::connection()->prepare(
            'SELECT p.*, f.disk_path AS cover_path,
                    sm.movement_type AS last_movement_type,
                    sm.created_at AS last_movement_at
             FROM products p
             LEFT JOIN files f ON f.id = p.cover_image_id
             LEFT JOIN stock_movements sm ON sm.id = (
                SELECT sm2.id FROM stock_movements sm2
                WHERE sm2.product_id = p.id
                ORDER BY sm2.created_at DESC, sm2.id DESC
                LIMIT 1
             )
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY p.name ASC
             LIMIT 300'
        );
        $stmt->execute($params);

        render('inventory/index', [
            'title' => 'Inventario',
            'products' => $stmt->fetchAll(),
            'q' => $q,
        ]);
    }

    public function movements(): void
    {
        $productId = (int)($_GET['product_id'] ?? 0);
        $productStmt = Database::connection()->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
        $productStmt->execute([$productId]);
        $product = $productStmt->fetch();
        if (!$product) {
            Response::abort(404, 'Producto no encontrado.');
        }

        $stmt = Database::connection()->prepare(
            'SELECT sm.*, u.name AS user_name
             FROM stock_movements sm
             LEFT JOIN users u ON u.id = sm.created_by
             WHERE sm.product_id = ?
             ORDER BY sm.created_at DESC, sm.id DESC
             LIMIT 300'
        );
        $stmt->execute([$productId]);

        render('inventory/movements', [
            'title' => 'Movimientos de inventario',
            'product' => $product,
            'movements' => $stmt->fetchAll(),
        ]);
    }

    public function bulkForm(): void
    {
        $stmt = Database::connection()->query(
            "SELECT id, name, sku, presentation, stock
             FROM products
             WHERE status IN ('draft','published')
             ORDER BY name ASC"
        );

        render('inventory/bulk', [
            'title' => 'Ingreso masivo de stock',
            'products' => $stmt->fetchAll(),
        ]);
    }

    public function bulkStore(): void
    {
        $notes = trim((string)($_POST['notes'] ?? ''));
        $productIds = $_POST['product_id'] ?? [];
        $quantities = $_POST['quantity'] ?? [];

        try {
            if ($notes === '') {
                throw new RuntimeException('Ingresa una observacion para el ingreso.');
            }

            $lines = [];
            foreach ($productIds as $index => $productId) {
                $productId = (int)$productId;
                $quantity = (int)($quantities[$index] ?? 0);
                if ($productId <= 0 || $quantity <= 0) {
                    continue;
                }
                $lines[$productId] = ($lines[$productId] ?? 0) + $quantity;
            }

            if (!$lines) {
                throw new RuntimeException('Agrega al menos un producto con cantidad valida.');
            }

            $pdo = Database::connection();
            $pdo->beginTransaction();
            foreach ($lines as $productId => $quantity) {
                InventoryService::adjust($productId, $quantity, $notes, (int)user()['id']);
            }
            $pdo->commit();

            activity('Ingreso masivo de inventario (' . count($lines) . ' productos)', 'inventory');
            flash('status', 'Stock actualizado para ' . count($lines) . ' producto(s).');
            Response::redirect('/inventory');
        } catch (Throwable $e) {
            if (Database::connection()->inTransaction()) {
                Database::connection()->rollBack();
            }
            back_with_errors([$e->getMessage()], []);
        }
    }

    public function adjust(): void
    {
        $productId = (int)($_POST['product_id'] ?? 0);
        $delta = (int)($_POST['delta'] ?? 0);
        $notes = trim((string)($_POST['notes'] ?? ''));
        try {
            if ($notes === '') {
                throw new RuntimeException('Ingresa una observacion para el ajuste.');
            }
            $pdo = Database::connection();
            $pdo->beginTransaction();
            InventoryService::adjust($productId, $delta, $notes, (int)user()['id']);
            $pdo->commit();
            activity('Ajusto inventario producto #' . $productId, 'inventory');
            flash('status', 'Inventario actualizado.');
        } catch (Throwable $e) {
            if (Database::connection()->inTransaction()) {
                Database::connection()->rollBack();
            }
            back_with_errors([$e->getMessage()], []);
        }
        Response::redirect('/inventory/movements?product_id=' . $productId);
    }
}
