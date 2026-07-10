<?php

final class OrderController extends Controller
{
    public function index(): void
    {
        $status = trim((string)($_GET['status'] ?? ''));
        $q = trim((string)($_GET['q'] ?? ''));
        $from = trim((string)($_GET['from'] ?? ''));
        $to = trim((string)($_GET['to'] ?? ''));
        $where = ['1=1'];
        $params = [];

        if ($status !== '' && in_array($status, ['pendiente', 'voucher_enviado', 'en_revision', 'aprobado', 'rechazado', 'cancelado'], true)) {
            $where[] = 'o.status = ?';
            $params[] = $status;
        }
        if ($q !== '') {
            $where[] = '(o.code LIKE ? OR o.customer_name LIKE ? OR o.document_number LIKE ? OR o.whatsapp LIKE ? OR o.payment_operation_number LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }
        if ($from !== '') {
            $where[] = 'DATE(o.created_at) >= ?';
            $params[] = $from;
        }
        if ($to !== '') {
            $where[] = 'DATE(o.created_at) <= ?';
            $params[] = $to;
        }

        $sql = 'SELECT o.*, f.disk_path AS voucher_path,
                       COUNT(oi.id) AS items_count, COALESCE(SUM(oi.quantity), 0) AS units_count,
                       GROUP_CONCAT(DISTINCT oi.product_name ORDER BY oi.id SEPARATOR ", ") AS product_names,
                       s.id AS sale_id, s.code AS sale_code, s.receipt_file_id AS sale_receipt_file_id
                FROM orders o
                JOIN files f ON f.id = o.voucher_file_id
                LEFT JOIN order_items oi ON oi.order_id = o.id
                LEFT JOIN sales s ON s.order_id = o.id
                WHERE ' . implode(' AND ', $where) . '
                GROUP BY o.id
                ORDER BY o.created_at DESC
                LIMIT 200';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        $stats = Database::connection()->query(
            "SELECT status, COUNT(*) AS total FROM orders GROUP BY status"
        )->fetchAll(PDO::FETCH_KEY_PAIR);

        render('orders/index', [
            'title' => 'Pedidos',
            'orders' => $stmt->fetchAll(),
            'stats' => $stats,
            'status' => $status,
            'q' => $q,
            'filters' => ['q' => $q, 'status' => $status, 'from' => $from, 'to' => $to],
        ]);
    }

    public function show(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $order = OrderService::findOrder($id);

        $saleStmt = Database::connection()->prepare('SELECT id, code, receipt_file_id FROM sales WHERE order_id = ? LIMIT 1');
        $saleStmt->execute([$id]);
        $sale = $saleStmt->fetch() ?: null;

        render('orders/show', [
            'title' => 'Pedido ' . $order['code'],
            'order' => $order,
            'items' => OrderService::orderItems($id),
            'sale' => $sale,
        ]);
    }

    public function markReview(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        try {
            OrderService::markReview($id);
            flash('status', 'Pedido marcado en revision.');
        } catch (Throwable $e) {
            back_with_errors([$e->getMessage()], []);
        }
        Response::redirect('/orders/show?id=' . $id);
    }

    public function approve(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $result = OrderService::approve($id, (int)user()['id']);
            activity('Aprobo pedido ' . $result['order']['code'], 'orders');
            flash('status', 'Pedido aprobado. Venta generada correctamente.');
        } catch (Throwable $e) {
            back_with_errors([$e->getMessage()], []);
        }
        Response::redirect('/orders/show?id=' . $id);
    }

    public function reject(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        try {
            OrderService::reject($id, (int)user()['id'], (string)($_POST['admin_notes'] ?? ''));
            flash('status', 'Pedido rechazado.');
        } catch (Throwable $e) {
            back_with_errors([$e->getMessage()], []);
        }
        Response::redirect('/orders/show?id=' . $id);
    }
}
