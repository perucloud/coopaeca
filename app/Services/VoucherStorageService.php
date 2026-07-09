<?php

final class VoucherStorageService
{
    private const MAX_IMAGE_BYTES = 5242880;
    private const MAX_PDF_BYTES = 20971520;
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    public static function store(array $file, ?int $uploadedBy = null): int
    {
        $originalName = basename((string)($file['name'] ?? 'voucher'));
        $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No se pudo subir el voucher.');
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('El archivo de voucher es invalido.');
        }

        $mime = self::detectMime($tmp, $originalName);
        if (!isset(self::ALLOWED[$mime])) {
            throw new RuntimeException('Voucher no permitido. Usa JPG, PNG, WebP o PDF.');
        }

        $size = (int)($file['size'] ?? 0);
        $isImage = str_starts_with($mime, 'image/');
        $limit = $isImage ? self::MAX_IMAGE_BYTES : self::MAX_PDF_BYTES;
        if ($size <= 0 || $size > $limit) {
            throw new RuntimeException($isImage ? 'El voucher supera 5 MB.' : 'El PDF supera 20 MB.');
        }

        $dir = dirname(__DIR__, 2) . '/public/uploads/vouchers';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $name = bin2hex(random_bytes(18)) . '.' . self::ALLOWED[$mime];
        $target = $dir . '/' . $name;
        if (!move_uploaded_file($tmp, $target)) {
            throw new RuntimeException('No se pudo guardar el voucher.');
        }

        [$width, $height] = $isImage ? (getimagesize($target) ?: [null, null]) : [null, null];
        $pdo = Database::connection();
        $pdo->prepare(
            'INSERT INTO files (disk_path, original_name, mime_type, size_bytes, width, height, alt_text, uploaded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            'uploads/vouchers/' . $name,
            $originalName,
            $mime,
            $size,
            $width,
            $height,
            'Voucher de pago',
            $uploadedBy,
        ]);

        return (int)$pdo->lastInsertId();
    }

    private static function detectMime(string $tmp, string $originalName): string
    {
        $mime = mime_content_type($tmp) ?: '';
        if (isset(self::ALLOWED[$mime])) {
            return $mime;
        }

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            default => $mime,
        };
    }
}
