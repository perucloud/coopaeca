<?php

use Dompdf\Dompdf;
use Dompdf\Options;

final class OrderController extends Controller
{
    private const STATUS_LABELS = [
        'pendiente' => 'Pendiente',
        'voucher_enviado' => 'Voucher enviado',
        'en_revision' => 'En revision',
        'aprobado' => 'Aprobado',
        'rechazado' => 'Rechazado',
        'cancelado' => 'Cancelado',
    ];

    public function index(): void
    {
        $filters = self::parseFilters();
        $orders = self::filteredOrders($filters);
        $latestDeliveries = ReceiptDeliveryService::latestBySaleIds(array_column($orders, 'sale_id'));

        $stats = Database::connection()->query(
            "SELECT status, COUNT(*) AS total FROM orders GROUP BY status"
        )->fetchAll(PDO::FETCH_KEY_PAIR);

        render('orders/index', [
            'title' => 'Pedidos',
            'orders' => $orders,
            'stats' => $stats,
            'status' => $filters['status'],
            'q' => $filters['q'],
            'filters' => $filters,
            'latestDeliveries' => $latestDeliveries,
        ]);
    }

    /**
     * Cantidad de pedidos que aun no fueron atendidos (pendiente/voucher_enviado).
     * Usado por el badge del sidebar y el polling de auto-actualizacion.
     */
    public function pendingCount(): void
    {
        $count = (int)Database::connection()->query(
            "SELECT COUNT(*) FROM orders WHERE status IN ('pendiente', 'voucher_enviado')"
        )->fetchColumn();

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['count' => $count]);
    }

    /**
     * Fragmento HTML (solo filas de la tabla) para el auto-refresh de
     * Pedidos sin recargar la pagina. Respeta los mismos filtros que index().
     */
    public function rows(): void
    {
        $filters = self::parseFilters();
        $orders = self::filteredOrders($filters);
        $latestDeliveries = ReceiptDeliveryService::latestBySaleIds(array_column($orders, 'sale_id'));

        header('Content-Type: text/html; charset=utf-8');
        view('orders/_rows', [
            'orders' => $orders,
            'latestDeliveries' => $latestDeliveries,
            'statusLabels' => self::STATUS_LABELS,
            'badgeClass' => ['aprobado' => 'ok', 'rechazado' => 'off', 'cancelado' => 'off', 'en_revision' => 'warn', 'voucher_enviado' => 'muted', 'pendiente' => 'muted'],
            'deliveryLabels' => ['sent' => 'Enviado', 'failed' => 'Fallido', 'pending' => 'Pendiente', 'prepared' => 'WhatsApp preparado'],
            'deliveryBadges' => ['sent' => 'ok', 'failed' => 'off', 'pending' => 'warn', 'prepared' => 'muted'],
        ]);
    }

    public function pdf(): void
    {
        $filters = self::parseFilters();
        $orders = self::filteredOrders($filters);
        $html = self::buildPdfHtml($orders, $filters);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Arial Narrow');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="pedidos-' . date('Y-m-d') . '.pdf"');
        echo $dompdf->output();
        exit;
    }

    private static function parseFilters(): array
    {
        $status = trim((string)($_GET['status'] ?? ''));
        if (!isset(self::STATUS_LABELS[$status])) {
            $status = '';
        }
        return [
            'q' => trim((string)($_GET['q'] ?? '')),
            'status' => $status,
            'from' => trim((string)($_GET['from'] ?? '')),
            'to' => trim((string)($_GET['to'] ?? '')),
        ];
    }

    private static function filteredOrders(array $filters): array
    {
        $where = ['1=1'];
        $params = [];

        if ($filters['status'] !== '') {
            $where[] = 'o.status = ?';
            $params[] = $filters['status'];
        }
        if ($filters['q'] !== '') {
            $where[] = '(o.code LIKE ? OR o.customer_name LIKE ? OR o.document_number LIKE ? OR o.whatsapp LIKE ? OR o.payment_operation_number LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }
        if ($filters['from'] !== '') {
            $where[] = 'DATE(o.created_at) >= ?';
            $params[] = $filters['from'];
        }
        if ($filters['to'] !== '') {
            $where[] = 'DATE(o.created_at) <= ?';
            $params[] = $filters['to'];
        }

        $sql = 'SELECT o.*, f.disk_path AS voucher_path, f.mime_type AS voucher_mime,
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
        return $stmt->fetchAll();
    }

    private static function buildPdfHtml(array $orders, array $filters): string
    {
        $fontPath = dirname(__DIR__, 2) . '/public/assets/fonts/ARIALN.TTF';
        $fontBase64 = base64_encode((string)file_get_contents($fontPath));

        $badgeColor = [
            'aprobado' => '#166534',
            'rechazado' => '#991b1b',
            'cancelado' => '#991b1b',
            'en_revision' => '#92400e',
            'voucher_enviado' => '#475569',
            'pendiente' => '#475569',
        ];

        $rows = '';
        foreach ($orders as $order) {
            $productNames = array_filter(explode(', ', (string)($order['product_names'] ?? '')));
            $product = e((string)($productNames[0] ?? '-'));
            $extra = count($productNames) - 1;
            if ($extra > 0) {
                $product .= ' +' . $extra . ' mas';
            }
            $phoneLine = $order['phone'] && $order['phone'] !== $order['whatsapp']
                ? e($order['phone']) . ' / ' . e($order['whatsapp'])
                : e((string)$order['whatsapp']);
            $color = $badgeColor[$order['status']] ?? '#475569';

            $rows .= '<tr>'
                . '<td>' . e(display_code('PED', (int)$order['id'], $order['code'] ?? null)) . '</td>'
                . '<td>' . e((string)$order['customer_name']) . '</td>'
                . '<td>' . e((string)$order['document_type']) . ' ' . e((string)$order['document_number']) . '</td>'
                . '<td>' . $phoneLine . '</td>'
                . '<td>' . (int)$order['units_count'] . ' und.</td>'
                . '<td>' . $product . '</td>'
                . '<td>' . e((string)$order['payment_method']) . '<br><span class="muted">N&deg; ' . e((string)$order['payment_operation_number']) . '</span></td>'
                . '<td>S/ ' . number_format((float)$order['total'], 2) . '</td>'
                . '<td><span class="badge" style="color:' . $color . '">' . e(self::STATUS_LABELS[$order['status']] ?? (string)$order['status']) . '</span></td>'
                . '<td>' . e(date('d/m/Y H:i', strtotime((string)$order['created_at']))) . '</td>'
                . '</tr>';
        }

        $activeFilters = array_filter($filters, fn ($v) => $v !== '');
        $filterLabels = [];
        if (!empty($activeFilters['q'])) $filterLabels[] = 'Busqueda: "' . e((string)$activeFilters['q']) . '"';
        if (!empty($activeFilters['status'])) $filterLabels[] = 'Estado: ' . e(self::STATUS_LABELS[$activeFilters['status']] ?? (string)$activeFilters['status']);
        if (!empty($activeFilters['from'])) $filterLabels[] = 'Desde: ' . e((string)$activeFilters['from']);
        if (!empty($activeFilters['to'])) $filterLabels[] = 'Hasta: ' . e((string)$activeFilters['to']);
        $filterLine = $filterLabels ? implode(' &middot; ', $filterLabels) : 'Sin filtros aplicados';

        $generatedAt = date('d/m/Y H:i');
        $total = count($orders);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @font-face {
        font-family: 'Arial Narrow';
        src: url(data:font/truetype;charset=utf-8;base64,{$fontBase64}) format('truetype');
        font-weight: normal;
        font-style: normal;
    }
    body { font-family: 'Arial Narrow', Arial, sans-serif; color: #1e293b; font-size: 10px; }
    h1 { font-size: 16px; margin: 0 0 4px; color: #14532d; }
    .subtitle { color: #64748b; margin: 0 0 4px; font-size: 10px; }
    .filters { color: #475569; margin: 0 0 14px; font-size: 9.5px; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #14532d; color: #fff; text-align: left; padding: 6px 6px; font-size: 9px; }
    td { padding: 5px 6px; border-bottom: 1px solid #e2e8f0; vertical-align: top; font-size: 9.5px; }
    tr:nth-child(even) td { background: #f8fafc; }
    .muted { color: #94a3b8; font-size: 8.5px; }
    .badge { font-weight: bold; }
    .footer { margin-top: 14px; color: #94a3b8; font-size: 9px; }
</style>
</head>
<body>
    <h1>COOPAECA - Reporte de pedidos</h1>
    <p class="subtitle">Generado el {$generatedAt} - {$total} pedido(s)</p>
    <p class="filters">{$filterLine}</p>
    <table>
        <thead>
            <tr>
                <th>Pedido</th><th>Apellidos y nombres</th><th>DNI/RUC</th><th>Telefono / WhatsApp</th>
                <th>Cantidad</th><th>Producto</th><th>Tipo de pago</th><th>Total</th><th>Estado</th><th>Fecha compra</th>
            </tr>
        </thead>
        <tbody>
            {$rows}
        </tbody>
    </table>
    <p class="footer">Documento generado automaticamente desde el panel administrativo de COOPAECA.</p>
</body>
</html>
HTML;
    }

    /** Datos completos del detalle de un pedido, reutilizados por show() (pagina completa) y detail() (fragmento AJAX para el modal). */
    private function orderDetailData(int $id): array
    {
        $order = OrderService::findOrder($id);

        $saleStmt = Database::connection()->prepare(
            'SELECT id, code, receipt_file_id, receipt_issued_at, email, whatsapp FROM sales WHERE order_id = ? LIMIT 1'
        );
        $saleStmt->execute([$id]);
        $sale = $saleStmt->fetch() ?: null;
        $receiptDeliveries = $sale ? ReceiptDeliveryService::history((int)$sale['id']) : [];

        return [
            'order' => $order,
            'items' => OrderService::orderItems($id),
            'sale' => $sale,
            'receiptDeliveries' => $receiptDeliveries,
            'latestReceiptDelivery' => $receiptDeliveries[0] ?? null,
        ];
    }

    public function show(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $data = $this->orderDetailData($id);

        render('orders/show', ['title' => 'Pedido ' . display_code('PED', $id, $data['order']['code'] ?? null)] + $data);
    }

    /** Fragmento HTML (sin layout) del detalle de un pedido, para el modal "Ver pedido" de /orders. */
    public function detail(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        header('Content-Type: text/html; charset=utf-8');
        view('orders/_detail', $this->orderDetailData($id));
    }

    public function markReview(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        try {
            OrderService::markReview($id);
            self::respondSuccess($id, 'Pedido marcado en revision.');
        } catch (Throwable $e) {
            self::respondError($id, [$e->getMessage()]);
        }
    }

    public function approve(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $result = OrderService::approve($id, (int)user()['id']);
            activity('Aprobo pedido ' . $result['order']['code'], 'orders');
            $saleId = (int)$result['sale_id'];
            $userId = (int)user()['id'];
            $notes = [];
            try {
                $delivery = ReceiptDeliveryService::automaticEmail($saleId, $userId);
                $notes[] = !empty($delivery['receipt_file_id']) ? 'Nota de venta emitida.' : 'La nota de venta requiere reintento.';
                $notes[] = ($delivery['status'] ?? '') === 'sent'
                    ? 'Enviado automaticamente por correo.'
                    : 'No se pudo enviar por correo; queda disponible para reintento o WhatsApp manual.';
            } catch (Throwable $postCommitError) {
                app_log('receipt_post_approval', $postCommitError->getMessage(), ['order_id' => $id, 'sale_id' => $saleId]);
                $notes[] = 'La venta quedo aprobada, pero la nota de venta requiere reintento.';
            }
            self::respondSuccess($id, 'Pedido aprobado. Venta generada correctamente. ' . implode(' ', $notes));
        } catch (Throwable $e) {
            self::respondError($id, [$e->getMessage()]);
        }
    }

    public function emailReceipt(): void
    {
        $orderId = (int)($_POST['id'] ?? 0);
        $email = trim((string)($_POST['email'] ?? ''));
        try {
            $delivery = ReceiptDeliveryService::resendEmail(self::saleIdForOrder($orderId), $email, (int)user()['id']);
            if (($delivery['status'] ?? '') !== 'sent') {
                throw new RuntimeException((string)($delivery['error_message'] ?? 'No se pudo enviar la nota de venta.'));
            }
            flash('status', 'Nota de venta enviada a ' . $email . '.');
        } catch (Throwable $e) {
            back_with_errors([$e->getMessage()], []);
        }
        Response::redirect('/orders/show?id=' . $orderId);
    }

    public function whatsappReceipt(): void
    {
        $orderId = (int)($_POST['id'] ?? 0);
        try {
            $result = ReceiptDeliveryService::prepareWhatsApp(
                self::saleIdForOrder($orderId),
                (string)($_POST['whatsapp'] ?? ''),
                (int)user()['id']
            );
            // Es una preparacion manual: el administrador debe adjuntar el PDF y pulsar Enviar.
            Response::redirect($result['url']);
        } catch (Throwable $e) {
            back_with_errors([$e->getMessage()], []);
        }
        Response::redirect('/orders/show?id=' . $orderId);
    }

    /**
     * Ticket de pedido (boton "TCK-PEDIDO" en /orders): resumen del pedido
     * tal como lo registro el cliente. Se regenera al vuelo con los datos
     * actuales; disponible para cualquier pedido, sin depender de que exista
     * una venta aprobada. No confundir con la nota de venta (viewReceipt).
     */
    public function viewTicket(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $order = OrderService::findOrder($id);
        $pdf = OrderConfirmationService::renderPdfBytes($order, OrderService::orderItems($id));

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . display_code('PED', $id, $order['code'] ?? null) . '.pdf"');
        echo $pdf;
        exit;
    }

    public function downloadReceipt(): void
    {
        $orderId = (int)($_GET['id'] ?? 0);
        $saleId = self::saleIdForOrder($orderId);
        $sale = SaleService::find($saleId);
        if (!$sale['receipt_file_id']) Response::abort(404, 'El comprobante aun no ha sido emitido.');
        SecureDocumentService::stream((int)$sale['receipt_file_id'], 'receipt', true, ReceiptService::referenceCode($sale) . '.pdf');
    }

    public function viewReceipt(): void
    {
        $saleId = self::saleIdForOrder((int)($_GET['id'] ?? 0));
        $sale = SaleService::find($saleId);
        if (!$sale['receipt_file_id']) Response::abort(404, 'El comprobante aun no ha sido emitido.');
        SecureDocumentService::stream((int)$sale['receipt_file_id'], 'receipt', false, ReceiptService::referenceCode($sale) . '.pdf');
    }

    public function viewVoucher(): void
    {
        $order = OrderService::findOrder((int)($_GET['id'] ?? 0));
        SecureDocumentService::stream((int)$order['voucher_file_id'], 'voucher', false, (string)$order['voucher_name']);
    }

    private static function saleIdForOrder(int $orderId): int
    {
        $stmt = Database::connection()->prepare('SELECT id FROM sales WHERE order_id = ? LIMIT 1');
        $stmt->execute([$orderId]);
        $saleId = (int)($stmt->fetchColumn() ?: 0);
        if ($saleId <= 0) throw new RuntimeException('El pedido aun no tiene una venta asociada.');
        return $saleId;
    }

    public function reject(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        try {
            OrderService::reject($id, (int)user()['id'], (string)($_POST['admin_notes'] ?? ''));
            self::respondSuccess($id, 'Pedido rechazado.');
        } catch (Throwable $e) {
            self::respondError($id, [$e->getMessage()]);
        }
    }

    /**
     * Respuesta de una accion (aprobar/rechazar/marcar en revision): si viene
     * del modal "Ver pedido" (fetch AJAX), responde JSON sin recargar nada;
     * si viene de un formulario normal (pagina completa /orders/show), sigue
     * el flujo clasico de flash + redirect.
     */
    private static function respondSuccess(int $orderId, string $message): void
    {
        if (is_ajax()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true, 'message' => $message]);
            exit;
        }
        flash('status', $message);
        Response::redirect('/orders/show?id=' . $orderId);
    }

    private static function respondError(int $orderId, array $errors): void
    {
        if (is_ajax()) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'errors' => $errors]);
            exit;
        }
        back_with_errors($errors, []);
    }
}
