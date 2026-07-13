<?php

final class CheckoutController extends Controller
{
    public function cart(): void
    {
        $settings = Database::connection()
            ->query('SELECT setting_key, setting_value FROM settings')
            ->fetchAll(PDO::FETCH_KEY_PAIR);

        $paymentMethods = [];
        try {
            $paymentMethods = Database::connection()->query(
                'SELECT pm.*, f.disk_path AS qr_path
                 FROM payment_methods pm
                 LEFT JOIN files f ON f.id = pm.qr_image_id
                 WHERE pm.is_active = 1
                 ORDER BY pm.position ASC, pm.id ASC'
            )->fetchAll();
        } catch (Throwable) {
            $paymentMethods = [
                ['name' => 'Transferencia bancaria', 'instructions' => 'Adjunta el voucher de la operacion realizada.'],
                ['name' => 'Yape', 'instructions' => 'Adjunta la captura del pago realizado.'],
                ['name' => 'Plin', 'instructions' => 'Adjunta la captura del pago realizado.'],
            ];
        }

        render('landing/checkout', [
            'title' => landing_lang() === 'en' ? 'Checkout' : 'Finalizar compra',
            'settings' => $settings,
            'socials' => $this->socialesActivos(),
            'paymentMethods' => $paymentMethods,
            'departments' => UbigeoService::departments(),
            'ubigeoCount' => UbigeoService::coverageCount(),
        ], 'layouts/landing');
    }

    public function store(): void
    {
        try {
            $order = OrderService::createFromCheckout($_POST, $_FILES['voucher'] ?? [], Request::capture());
            $_SESSION['_last_order_id'] = (int)$order['id'];

            // Confirmacion inmediata al cliente (si dejo correo). No debe
            // frenar el checkout si el envio falla: el pedido ya quedo creado.
            try {
                OrderConfirmationService::send($order, OrderService::orderItems((int)$order['id']), landing_lang() === 'en');
            } catch (Throwable $mailError) {
                app_log('order_confirmation_failed', $mailError->getMessage(), ['order_id' => (int)$order['id']]);
            }

            Response::redirect(lurl('/checkout/success?code=' . urlencode((string)$order['code'])));
        } catch (Throwable $e) {
            $errors = array_filter(array_map('trim', explode("\n", $e->getMessage())));
            back_with_errors($errors ?: ['No se pudo registrar el pedido.'], $_POST);
        }
    }

    public function success(): void
    {
        $code = trim((string)($_GET['code'] ?? ''));
        if ($code === '') {
            Response::redirect(lurl('/'));
        }

        $stmt = Database::connection()->prepare(
            'SELECT o.*, f.disk_path AS voucher_path
             FROM orders o
             JOIN files f ON f.id = o.voucher_file_id
             WHERE o.code = ? LIMIT 1'
        );
        $stmt->execute([$code]);
        $order = $stmt->fetch();
        if (!$order) {
            Response::abort(404, 'Pedido no encontrado.');
        }

        $settings = Database::connection()
            ->query('SELECT setting_key, setting_value FROM settings')
            ->fetchAll(PDO::FETCH_KEY_PAIR);

        render('landing/checkout-success', [
            'title' => landing_lang() === 'en' ? 'Order created' : 'Pedido generado',
            'settings' => $settings,
            'socials' => $this->socialesActivos(),
            'order' => $order,
            'items' => OrderService::orderItems((int)$order['id']),
        ], 'layouts/landing');
    }

    private function socialesActivos(): array
    {
        return Database::connection()
            ->query("SELECT * FROM social_networks WHERE is_active = 1 ORDER BY position ASC")
            ->fetchAll();
    }
}
