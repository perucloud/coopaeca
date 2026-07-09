<?php

final class PaymentMethodController extends Controller
{
    public function index(): void
    {
        $methods = Database::connection()->query(
            'SELECT * FROM payment_methods ORDER BY position ASC, id ASC'
        )->fetchAll();

        render('payment_methods/index', [
            'title' => 'Metodos de pago',
            'methods' => $methods,
        ]);
    }

    public function store(): void
    {
        $data = $this->validate($_POST);
        Database::connection()->prepare(
            'INSERT INTO payment_methods
             (name, type, account_label, account_number, holder_name, instructions, is_active, position)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $data['name'],
            $data['type'],
            $data['account_label'],
            $data['account_number'],
            $data['holder_name'],
            $data['instructions'],
            $data['is_active'],
            $data['position'],
        ]);

        flash('status', 'Metodo de pago creado.');
        Response::redirect('/payment-methods');
    }

    public function update(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $data = $this->validate($_POST);
        Database::connection()->prepare(
            'UPDATE payment_methods
             SET name = ?, type = ?, account_label = ?, account_number = ?, holder_name = ?,
                 instructions = ?, is_active = ?, position = ?, updated_at = NOW()
             WHERE id = ?'
        )->execute([
            $data['name'],
            $data['type'],
            $data['account_label'],
            $data['account_number'],
            $data['holder_name'],
            $data['instructions'],
            $data['is_active'],
            $data['position'],
            $id,
        ]);

        flash('status', 'Metodo de pago actualizado.');
        Response::redirect('/payment-methods');
    }

    private function validate(array $input): array
    {
        $name = trim((string)($input['name'] ?? ''));
        $type = trim((string)($input['type'] ?? 'other'));
        if (!in_array($type, ['bank_transfer', 'digital_wallet', 'other'], true)) {
            $type = 'other';
        }

        $errors = [];
        if ($name === '') {
            $errors[] = 'El nombre del metodo de pago es obligatorio.';
        }

        if ($errors) {
            back_with_errors($errors, $input);
        }

        return [
            'name' => $name,
            'type' => $type,
            'account_label' => trim((string)($input['account_label'] ?? '')) ?: null,
            'account_number' => trim((string)($input['account_number'] ?? '')) ?: null,
            'holder_name' => trim((string)($input['holder_name'] ?? '')) ?: null,
            'instructions' => trim((string)($input['instructions'] ?? '')) ?: null,
            'is_active' => !empty($input['is_active']) ? 1 : 0,
            'position' => (int)($input['position'] ?? 0),
        ];
    }
}
