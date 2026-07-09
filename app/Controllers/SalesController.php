<?php

final class SalesController extends Controller
{
    public function index(): void
    {
        $q = trim((string)($_GET['q'] ?? ''));
        $source = trim((string)($_GET['source'] ?? ''));
        $status = trim((string)($_GET['status'] ?? ''));
        $from = trim((string)($_GET['from'] ?? ''));
        $to = trim((string)($_GET['to'] ?? ''));

        $where = ['1=1'];
        $params = [];
        if ($q !== '') {
            $where[] = '(s.code LIKE ? OR s.customer_name LIKE ? OR s.document_number LIKE ? OR s.whatsapp LIKE ? OR s.payment_operation_number LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }
        if ($source !== '' && in_array($source, ['web', 'whatsapp', 'phone', 'manual'], true)) {
            $where[] = 's.source = ?';
            $params[] = $source;
        }
        if ($status !== '' && in_array($status, ['confirmada', 'anulada', 'entregada'], true)) {
            $where[] = 's.status = ?';
            $params[] = $status;
        }
        if ($from !== '') {
            $where[] = 'DATE(s.created_at) >= ?';
            $params[] = $from;
        }
        if ($to !== '') {
            $where[] = 'DATE(s.created_at) <= ?';
            $params[] = $to;
        }

        $sql = 'SELECT s.*, o.code AS order_code, f.disk_path AS voucher_path,
                       COUNT(si.id) AS items_count, COALESCE(SUM(si.quantity), 0) AS units_count
                FROM sales s
                LEFT JOIN orders o ON o.id = s.order_id
                LEFT JOIN files f ON f.id = s.voucher_file_id
                LEFT JOIN sale_items si ON si.sale_id = s.id
                WHERE ' . implode(' AND ', $where) . '
                GROUP BY s.id
                ORDER BY s.created_at DESC
                LIMIT 250';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        $stats = Database::connection()->query(
            "SELECT
                COUNT(*) AS total_sales,
                COALESCE(SUM(CASE WHEN status = 'confirmada' THEN total ELSE 0 END), 0) AS total_confirmed,
                COALESCE(SUM(CASE WHEN source = 'web' THEN total ELSE 0 END), 0) AS total_web,
                COALESCE(SUM(CASE WHEN source = 'whatsapp' THEN total ELSE 0 END), 0) AS total_whatsapp
             FROM sales"
        )->fetch() ?: [];

        render('sales/index', [
            'title' => 'Ventas',
            'sales' => $stmt->fetchAll(),
            'stats' => $stats,
            'filters' => compact('q', 'source', 'status', 'from', 'to'),
        ]);
    }

    public function show(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = Database::connection()->prepare(
            'SELECT s.*, o.code AS order_code, f.disk_path AS voucher_path, f.mime_type AS voucher_mime
             FROM sales s
             LEFT JOIN orders o ON o.id = s.order_id
             LEFT JOIN files f ON f.id = s.voucher_file_id
             WHERE s.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $sale = $stmt->fetch();
        if (!$sale) {
            Response::abort(404, 'Venta no encontrada.');
        }

        $items = Database::connection()->prepare('SELECT * FROM sale_items WHERE sale_id = ? ORDER BY id ASC');
        $items->execute([$id]);

        render('sales/show', [
            'title' => 'Venta ' . $sale['code'],
            'sale' => $sale,
            'items' => $items->fetchAll(),
        ]);
    }

    public function cancel(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        try {
            SaleService::cancel($id, (int)user()['id'], (string)($_POST['notes'] ?? ''));
            flash('status', 'Venta anulada y stock revertido.');
        } catch (Throwable $e) {
            back_with_errors([$e->getMessage()], []);
        }
        Response::redirect('/sales/show?id=' . $id);
    }
}
