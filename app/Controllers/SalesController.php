<?php

use Dompdf\Dompdf;
use Dompdf\Options;

final class SalesController extends Controller
{
    private const SOURCE_LABELS = ['web' => 'Web', 'whatsapp' => 'WhatsApp', 'phone' => 'Telefono', 'manual' => 'Manual'];
    private const STATUS_LABELS = ['confirmada' => 'Confirmada', 'anulada' => 'Anulada', 'entregada' => 'Entregada'];

    public function index(): void
    {
        $filters = self::parseFilters();
        $sales = self::filteredSales($filters);

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
            'sales' => $sales,
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }

    public function pdf(): void
    {
        $filters = self::parseFilters();
        $sales = self::filteredSales($filters);
        if (!empty($filters['id']) && !$sales) {
            Response::abort(404, 'Venta no encontrada.');
        }
        $singleSale = !empty($filters['id']) && count($sales) === 1;
        $items = [];
        if ($singleSale) {
            $stmt = Database::connection()->prepare('SELECT * FROM sale_items WHERE sale_id = ? ORDER BY id ASC');
            $stmt->execute([(int)$sales[0]['id']]);
            $items = $stmt->fetchAll();
        }
        $html = self::buildPdfHtml($sales, $filters, $items);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Arial Narrow');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = !empty($filters['id']) && $sales
            ? display_code('VEN', (int)$sales[0]['id'], $sales[0]['code'] ?? null) . '.pdf'
            : 'ventas-' . date('Y-m-d') . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        echo $dompdf->output();
        exit;
    }

    private static function parseFilters(): array
    {
        $source = trim((string)($_GET['source'] ?? ''));
        if (!isset(self::SOURCE_LABELS[$source])) {
            $source = '';
        }
        $status = trim((string)($_GET['status'] ?? ''));
        if (!isset(self::STATUS_LABELS[$status])) {
            $status = '';
        }
        return [
            'id' => (int)($_GET['id'] ?? 0),
            'q' => trim((string)($_GET['q'] ?? '')),
            'source' => $source,
            'status' => $status,
            'from' => trim((string)($_GET['from'] ?? '')),
            'to' => trim((string)($_GET['to'] ?? '')),
        ];
    }

    /**
     * Consulta compartida por el listado y el reporte PDF: mismos filtros
     * aplicados en pantalla (busqueda, origen, estado, fechas), asi que
     * imprimir el reporte tras buscar un comprador entrega solo sus compras.
     * Si $filters['id'] viene informado (icono "Imprimir" de una fila), el
     * reporte se limita exclusivamente a esa venta, sin importar su estado.
     */
    private static function filteredSales(array $filters): array
    {
        if (!empty($filters['id'])) {
            $where = ['s.id = ?'];
            $params = [(int)$filters['id']];
            return self::runSalesQuery($where, $params);
        }

        $where = ['1=1'];
        $params = [];
        if ($filters['q'] !== '') {
            $where[] = '(s.code LIKE ? OR s.customer_name LIKE ? OR s.document_number LIKE ? OR s.whatsapp LIKE ? OR s.payment_operation_number LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }
        if ($filters['source'] !== '') {
            $where[] = 's.source = ?';
            $params[] = $filters['source'];
        }
        if ($filters['status'] !== '') {
            $where[] = 's.status = ?';
            $params[] = $filters['status'];
        }
        if ($filters['from'] !== '') {
            $where[] = 'DATE(s.created_at) >= ?';
            $params[] = $filters['from'];
        }
        if ($filters['to'] !== '') {
            $where[] = 'DATE(s.created_at) <= ?';
            $params[] = $filters['to'];
        }

        return self::runSalesQuery($where, $params);
    }

    private static function runSalesQuery(array $where, array $params): array
    {
        $sql = 'SELECT s.*, o.code AS order_code, f.disk_path AS voucher_path, f.mime_type AS voucher_mime,
                       COUNT(si.id) AS items_count, COALESCE(SUM(si.quantity), 0) AS units_count,
                       GROUP_CONCAT(DISTINCT si.product_name ORDER BY si.id SEPARATOR ", ") AS product_names
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
        return $stmt->fetchAll();
    }

    private static function buildPdfHtml(array $sales, array $filters, array $items = []): string
    {
        $fontPath = dirname(__DIR__, 2) . '/public/assets/fonts/ARIALN.TTF';
        $fontBase64 = base64_encode((string)file_get_contents($fontPath));

        $badgeColor = ['confirmada' => '#166534', 'anulada' => '#991b1b', 'entregada' => '#1d4ed8'];

        // Impresion de una sola venta (icono "Imprimir" de la fila): en vez de
        // la tabla-resumen de multiples ventas, se muestra un detalle tipo
        // comprobante con cada producto, su cantidad, precio y subtotal.
        $singleSale = !empty($filters['id']) && count($sales) === 1;

        if ($singleSale) {
            $sale = $sales[0];
            $color = $badgeColor[$sale['status']] ?? '#475569';

            $itemRows = '';
            foreach ($items as $item) {
                $itemRows .= '<tr>'
                    . '<td>' . e((string)$item['product_name']) . '</td>'
                    . '<td>' . e((string)($item['product_sku'] ?: '-')) . '</td>'
                    . '<td>' . e((string)($item['presentation'] ?: '-')) . '</td>'
                    . '<td>' . (int)$item['quantity'] . '</td>'
                    . '<td>S/ ' . number_format((float)$item['unit_price'], 2) . '</td>'
                    . '<td>S/ ' . number_format((float)$item['subtotal'], 2) . '</td>'
                    . '</tr>';
            }

            $filterLine = 'Venta ' . e(display_code('VEN', (int)$sale['id'], $sale['code'] ?? null)) . ' &middot; impresion individual';
            $generatedAt = date('d/m/Y H:i');
            $totalAmount = number_format((float)$sale['total'], 2);
            $reportTitle = 'COOPAECA - Reporte de venta';
            $orderRef = $sale['order_id']
                ? e(display_code('PED', (int)$sale['order_id'], $sale['order_code'] ?? null))
                : '-';

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
    .info-grid { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    .info-grid td { padding: 4px 6px; font-size: 9.5px; border: none; vertical-align: top; }
    .info-grid dt { color: #64748b; font-size: 8.5px; text-transform: uppercase; margin: 0; }
    .info-grid dd { margin: 0 0 4px; font-weight: bold; }
    table.items { width: 100%; border-collapse: collapse; }
    th { background: #14532d; color: #fff; text-align: left; padding: 6px 6px; font-size: 9px; }
    td { padding: 5px 6px; border-bottom: 1px solid #e2e8f0; vertical-align: top; font-size: 9.5px; }
    tr:nth-child(even) td { background: #f8fafc; }
    .muted { color: #94a3b8; font-size: 8.5px; }
    .badge { font-weight: bold; }
    .totals { width: 100%; margin-top: 10px; }
    .totals td { border: none; font-size: 11px; padding: 4px 6px; }
    .totals .label { text-align: right; color: #475569; }
    .totals .amount { text-align: right; font-weight: bold; color: #14532d; font-size: 13px; width: 120px; }
    .footer { margin-top: 14px; color: #94a3b8; font-size: 9px; }
</style>
</head>
<body>
    <h1>{$reportTitle}</h1>
    <p class="subtitle">Generado el {$generatedAt}</p>
    <p class="filters">{$filterLine}</p>
    <table class="info-grid">
        <tr>
            <td style="width:33%">
                <dt>Comprador</dt><dd>{$sale['customer_name']}</dd>
                <dt>Documento</dt><dd>{$sale['document_type']} {$sale['document_number']}</dd>
                <dt>WhatsApp</dt><dd>{$sale['whatsapp']}</dd>
            </td>
            <td style="width:33%">
                <dt>Pedido origen</dt><dd>{$orderRef}</dd>
                <dt>Tipo de pago</dt><dd>{$sale['payment_method']} &middot; N&deg; {$sale['payment_operation_number']}</dd>
                <dt>Fecha</dt><dd>{$sale['created_at']}</dd>
            </td>
            <td style="width:33%">
                <dt>Estado</dt><dd><span class="badge" style="color:{$color}">{$sale['status']}</span></dd>
                <dt>Origen</dt><dd>{$sale['source']}</dd>
            </td>
        </tr>
    </table>
    <table class="items">
        <thead>
            <tr><th>Producto</th><th>SKU</th><th>Presentacion</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th></tr>
        </thead>
        <tbody>
            {$itemRows}
        </tbody>
    </table>
    <table class="totals">
        <tr><td class="label">Total:</td><td class="amount">S/ {$totalAmount}</td></tr>
    </table>
    <p class="footer">Documento generado automaticamente desde el panel administrativo de COOPAECA.</p>
</body>
</html>
HTML;
        }

        $rows = '';
        foreach ($sales as $sale) {
            $productNames = array_filter(explode(', ', (string)($sale['product_names'] ?? '')));
            $product = e((string)($productNames[0] ?? '-'));
            $extra = count($productNames) - 1;
            if ($extra > 0) {
                $product .= ' +' . $extra . ' mas';
            }
            $color = $badgeColor[$sale['status']] ?? '#475569';

            $rows .= '<tr>'
                . '<td>' . e(display_code('VEN', (int)$sale['id'], $sale['code'] ?? null)) . '</td>'
                . '<td>' . e((string)$sale['customer_name']) . '</td>'
                . '<td>' . e((string)$sale['document_type']) . ' ' . e((string)$sale['document_number']) . '</td>'
                . '<td>' . e((string)($sale['whatsapp'] ?: '-')) . '</td>'
                . '<td>' . (int)$sale['units_count'] . ' und.</td>'
                . '<td>' . $product . '</td>'
                . '<td>' . e((string)$sale['payment_method']) . '<br><span class="muted">N&deg; ' . e((string)$sale['payment_operation_number']) . '</span></td>'
                . '<td>S/ ' . number_format((float)$sale['total'], 2) . '</td>'
                . '<td><span class="badge" style="color:' . $color . '">' . e(self::STATUS_LABELS[$sale['status']] ?? (string)$sale['status']) . '</span></td>'
                . '<td>' . e(date('d/m/Y H:i', strtotime((string)$sale['created_at']))) . '</td>'
                . '</tr>';
        }

        $activeFilters = array_filter($filters, fn ($v) => $v !== '' && $v !== 0);
        $filterLabels = [];
        if (!empty($activeFilters['q'])) $filterLabels[] = 'Busqueda: "' . e((string)$activeFilters['q']) . '"';
        if (!empty($activeFilters['source'])) $filterLabels[] = 'Origen: ' . e(self::SOURCE_LABELS[$activeFilters['source']] ?? (string)$activeFilters['source']);
        if (!empty($activeFilters['status'])) $filterLabels[] = 'Estado: ' . e(self::STATUS_LABELS[$activeFilters['status']] ?? (string)$activeFilters['status']);
        if (!empty($activeFilters['from'])) $filterLabels[] = 'Desde: ' . e((string)$activeFilters['from']);
        if (!empty($activeFilters['to'])) $filterLabels[] = 'Hasta: ' . e((string)$activeFilters['to']);
        $filterLine = $filterLabels ? implode(' &middot; ', $filterLabels) : 'Sin filtros aplicados';

        $generatedAt = date('d/m/Y H:i');
        $total = count($sales);
        $totalAmount = number_format(array_sum(array_map(fn ($s) => (float)$s['total'], $sales)), 2);
        $reportTitle = 'COOPAECA - Reporte de ventas';

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
    <h1>{$reportTitle}</h1>
    <p class="subtitle">Generado el {$generatedAt} - {$total} venta(s) - Total: S/ {$totalAmount}</p>
    <p class="filters">{$filterLine}</p>
    <table>
        <thead>
            <tr>
                <th>Venta</th><th>Apellidos y nombres</th><th>DNI/RUC</th><th>WhatsApp</th>
                <th>Cantidad</th><th>Producto</th><th>Tipo de pago</th><th>Total</th><th>Estado</th><th>Fecha</th>
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
            'title' => 'Venta ' . display_code('VEN', (int)$sale['id'], $sale['code'] ?? null),
            'sale' => $sale,
            'items' => $items->fetchAll(),
        ]);
    }

    public function create(): void
    {
        $products = Database::connection()->query(
            "SELECT id, name, sku, presentation, price, sale_price, stock
             FROM products
             WHERE status = 'published'
             ORDER BY name ASC"
        )->fetchAll();

        $paymentMethods = Database::connection()->query(
            'SELECT name FROM payment_methods WHERE is_active = 1 ORDER BY position ASC, id ASC'
        )->fetchAll();

        render('sales/create', [
            'title' => 'Nueva venta',
            'products' => $products,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function store(): void
    {
        try {
            $sale = SaleService::createManual($_POST, $_FILES['voucher'] ?? [], (int)user()['id']);
            flash('status', 'Venta registrada y stock actualizado.');
            Response::redirect('/sales/show?id=' . (int)$sale['id']);
        } catch (Throwable $e) {
            back_with_errors(array_filter(array_map('trim', explode("\n", $e->getMessage()))) ?: [$e->getMessage()], $_POST);
        }
    }

    public function cancel(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        try {
            SaleService::cancel($id, (int)user()['id'], (string)($_POST['notes'] ?? ''));
            self::respondSuccess('/sales/show?id=' . $id, 'Venta anulada y stock revertido.');
        } catch (Throwable $e) {
            self::respondError('/sales/show?id=' . $id, [$e->getMessage()]);
        }
    }

    /**
     * Respuesta de una accion (ej. anular venta): si viene del modal de
     * confirmacion en /orders (fetch AJAX), responde JSON sin recargar
     * nada; si viene de un formulario normal, sigue el flujo clasico de
     * flash + redirect. Mismo patron que OrderController.
     */
    private static function respondSuccess(string $redirect, string $message): void
    {
        if (is_ajax()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true, 'message' => $message]);
            exit;
        }
        flash('status', $message);
        Response::redirect($redirect);
    }

    private static function respondError(string $redirect, array $errors): void
    {
        if (is_ajax()) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'errors' => $errors]);
            exit;
        }
        back_with_errors($errors, []);
    }

    public function issueReceipt(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $redirect = self::safeRedirect((string)($_POST['redirect'] ?? ''), '/sales/show?id=' . $id);
        try {
            ReceiptService::ensureIssued($id, (int)user()['id']);
            flash('status', 'Ticket emitido correctamente.');
        } catch (Throwable $e) {
            back_with_errors([$e->getMessage()], []);
        }
        Response::redirect($redirect);
    }

    public function viewReceipt(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $sale = SaleService::find($id);
        if (!$sale['receipt_file_id']) {
            Response::abort(404, 'Este ticket aun no ha sido emitido.');
        }
        SecureDocumentService::stream((int)$sale['receipt_file_id'], 'receipt', false, ReceiptService::referenceCode($sale) . '.pdf');
    }

    public function viewVoucher(): void
    {
        $sale = SaleService::find((int)($_GET['id'] ?? 0));
        if (!$sale['voucher_file_id']) Response::abort(404, 'Voucher no encontrado.');
        SecureDocumentService::stream((int)$sale['voucher_file_id'], 'voucher', false, 'voucher');
    }

    public function emailReceipt(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $redirect = self::safeRedirect((string)($_POST['redirect'] ?? ''), '/sales/show?id=' . $id);
        $email = trim((string)($_POST['email'] ?? ''));
        try {
            $delivery = ReceiptDeliveryService::resendEmail($id, $email, (int)user()['id']);
            if (($delivery['status'] ?? '') !== 'sent') {
                throw new RuntimeException((string)($delivery['error_message'] ?? 'No se pudo enviar el comprobante.'));
            }
            flash('status', 'Ticket enviado a ' . $email . '.');
        } catch (Throwable $e) {
            back_with_errors([$e->getMessage()], []);
        }
        Response::redirect($redirect);
    }

    /** Solo permite redirigir dentro del propio panel (evita open redirect via el campo oculto "redirect"). */
    private static function safeRedirect(string $path, string $fallback): string
    {
        return (str_starts_with($path, '/') && !str_starts_with($path, '//')) ? $path : $fallback;
    }
}
