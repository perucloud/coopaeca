<?php

final class PaymentMethodController extends Controller
{
    private const MAX_QR_BYTES = 3145728;
    private const ALLOWED_QR = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function index(): void
    {
        $methods = Database::connection()->query(
            'SELECT pm.*, f.disk_path AS qr_path, f.original_name AS qr_name
             FROM payment_methods pm
             LEFT JOIN files f ON f.id = pm.qr_image_id
             ORDER BY pm.position ASC, pm.id ASC'
        )->fetchAll();

        render('payment_methods/index', [
            'title' => 'Metodos de pago',
            'methods' => $methods,
        ]);
    }

    public function store(): void
    {
        $data = $this->validate($_POST);
        $qrImageId = $this->storeQr($_FILES['qr_image'] ?? null);
        Database::connection()->prepare(
            'INSERT INTO payment_methods
             (name, type, account_label, account_number, holder_name, bank_name, cci, currency, instructions, qr_image_id, is_active, position)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $data['name'],
            $data['type'],
            $data['account_label'],
            $data['account_number'],
            $data['holder_name'],
            $data['bank_name'],
            $data['cci'],
            $data['currency'],
            $data['instructions'],
            $qrImageId,
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
        $oldQrImageId = (int)(Database::connection()
            ->query('SELECT qr_image_id FROM payment_methods WHERE id = ' . $id)
            ->fetchColumn() ?: 0);
        $qrImageId = $this->storeQr($_FILES['qr_image'] ?? null);
        $qrSql = $qrImageId !== null ? ', qr_image_id = ?' : '';
        $params = [
            $data['name'],
            $data['type'],
            $data['account_label'],
            $data['account_number'],
            $data['holder_name'],
            $data['bank_name'],
            $data['cci'],
            $data['currency'],
            $data['instructions'],
            $data['is_active'],
            $data['position'],
        ];
        if ($qrImageId !== null) {
            $params[] = $qrImageId;
        }
        $params[] = $id;

        Database::connection()->prepare(
            'UPDATE payment_methods
             SET name = ?, type = ?, account_label = ?, account_number = ?, holder_name = ?,
                 bank_name = ?, cci = ?, currency = ?,
                 instructions = ?, is_active = ?, position = ?' . $qrSql . ', updated_at = NOW()
             WHERE id = ?'
        )->execute($params);

        // Si se reemplazo el QR, eliminar el archivo anterior.
        if ($qrImageId !== null && $oldQrImageId > 0 && $oldQrImageId !== $qrImageId) {
            $this->deleteQrFile($oldQrImageId);
        }

        flash('status', 'Metodo de pago actualizado.');
        Response::redirect('/payment-methods');
    }

    /** Quita el QR de un metodo: limpia la referencia y borra el archivo. */
    public function deleteQr(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = Database::connection()->prepare('SELECT id, name, qr_image_id FROM payment_methods WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $method = $stmt->fetch();
        if (!$method) {
            Response::abort(404, 'Metodo de pago no encontrado.');
        }

        if ($method['qr_image_id']) {
            Database::connection()->prepare('UPDATE payment_methods SET qr_image_id = NULL, updated_at = NOW() WHERE id = ?')
                ->execute([$id]);
            $this->deleteQrFile((int)$method['qr_image_id']);
            activity('Quito el QR del metodo de pago ' . $method['name'], 'payment_methods');
        }

        flash('status', 'QR eliminado del metodo de pago.');
        Response::redirect('/payment-methods');
    }

    /** Borra registro y archivo fisico de un QR si ningun otro metodo lo usa. */
    private function deleteQrFile(int $fileId): void
    {
        $pdo = Database::connection();
        $inUse = $pdo->prepare('SELECT 1 FROM payment_methods WHERE qr_image_id = ? LIMIT 1');
        $inUse->execute([$fileId]);
        if ($inUse->fetch()) {
            return;
        }

        $stmt = $pdo->prepare('SELECT disk_path FROM files WHERE id = ? LIMIT 1');
        $stmt->execute([$fileId]);
        $diskPath = (string)($stmt->fetchColumn() ?: '');
        if ($diskPath !== '' && str_starts_with(str_replace('\\', '/', $diskPath), 'uploads/payment-methods/')) {
            delete_public_upload($diskPath);
            $pdo->prepare('DELETE FROM files WHERE id = ?')->execute([$fileId]);
        }
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

        $currency = trim((string)($input['currency'] ?? ''));
        if (!in_array($currency, ['PEN', 'USD'], true)) {
            $currency = null;
        }

        // Los datos bancarios solo aplican a transferencias.
        $isBank = $type === 'bank_transfer';

        return [
            'name' => $name,
            'type' => $type,
            'account_label' => trim((string)($input['account_label'] ?? '')) ?: null,
            'account_number' => trim((string)($input['account_number'] ?? '')) ?: null,
            'holder_name' => trim((string)($input['holder_name'] ?? '')) ?: null,
            'bank_name' => $isBank ? (trim((string)($input['bank_name'] ?? '')) ?: null) : null,
            'cci' => $isBank ? (substr(preg_replace('/[^0-9\- ]/', '', (string)($input['cci'] ?? '')) ?? '', 0, 40) ?: null) : null,
            'currency' => $isBank ? $currency : null,
            'instructions' => trim((string)($input['instructions'] ?? '')) ?: null,
            'is_active' => !empty($input['is_active']) ? 1 : 0,
            'position' => (int)($input['position'] ?? 0),
        ];
    }

    private function storeQr(?array $file): ?int
    {
        if (!$file || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $originalName = basename((string)($file['name'] ?? 'qr'));
        if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            back_with_errors(['No se pudo subir el QR de pago.'], $_POST);
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            back_with_errors(['El archivo QR es invalido.'], $_POST);
        }

        $mime = mime_content_type($tmp) ?: '';
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!isset(self::ALLOWED_QR[$mime])) {
            $mimeByExt = match ($ext) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
                default => '',
            };
            $mime = isset(self::ALLOWED_QR[$mimeByExt]) ? $mimeByExt : $mime;
        }

        if (!isset(self::ALLOWED_QR[$mime])) {
            back_with_errors(['El QR debe ser JPG, PNG o WebP.'], $_POST);
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_QR_BYTES) {
            back_with_errors(['El QR no debe superar 3 MB.'], $_POST);
        }

        $dir = dirname(__DIR__, 2) . '/public/uploads/payment-methods';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $name = bin2hex(random_bytes(16)) . '.' . self::ALLOWED_QR[$mime];
        $target = $dir . '/' . $name;
        if (!move_uploaded_file($tmp, $target)) {
            back_with_errors(['No se pudo guardar el QR.'], $_POST);
        }

        [$width, $height] = getimagesize($target) ?: [null, null];
        Database::connection()->prepare(
            'INSERT INTO files (disk_path, original_name, mime_type, size_bytes, width, height, alt_text, uploaded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            'uploads/payment-methods/' . $name,
            $originalName,
            $mime,
            $size,
            $width,
            $height,
            'QR metodo de pago',
            user()['id'] ?? null,
        ]);

        return (int)Database::connection()->lastInsertId();
    }
}
