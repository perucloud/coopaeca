<?php

/** Sirve documentos privados desde public/uploads sin exponer su ruta fisica. */
final class SecureDocumentService
{
    private const TYPES = [
        'voucher' => [
            'prefix' => 'uploads/vouchers/',
            'mimes' => ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],
        ],
        'receipt' => [
            'prefix' => 'uploads/receipts/',
            'mimes' => ['application/pdf'],
        ],
    ];

    public static function stream(int $fileId, string $type, bool $download = false, ?string $downloadName = null): never
    {
        $contract = self::TYPES[$type] ?? null;
        if ($fileId <= 0 || $contract === null) {
            Response::abort(404, 'Documento no encontrado.');
        }

        $stmt = Database::connection()->prepare(
            'SELECT disk_path, original_name, mime_type FROM files WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$fileId]);
        $file = $stmt->fetch();
        $relative = str_replace('\\', '/', ltrim((string)($file['disk_path'] ?? ''), '/'));
        if (!$file || !str_starts_with($relative, $contract['prefix'])) {
            Response::abort(404, 'Documento no encontrado.');
        }

        $publicRoot = realpath(dirname(__DIR__, 2) . '/public');
        $expectedRoot = realpath(dirname(__DIR__, 2) . '/public/' . rtrim($contract['prefix'], '/'));
        $path = realpath(dirname(__DIR__, 2) . '/public/' . $relative);
        if ($publicRoot === false || $expectedRoot === false || $path === false
            || !str_starts_with($expectedRoot, $publicRoot . DIRECTORY_SEPARATOR)
            || !str_starts_with($path, $expectedRoot . DIRECTORY_SEPARATOR)
            || !is_file($path)) {
            Response::abort(404, 'Documento no encontrado.');
        }

        $detected = (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: '';
        $stored = strtolower(trim((string)$file['mime_type']));
        if (!in_array($detected, $contract['mimes'], true) || !in_array($stored, $contract['mimes'], true)) {
            app_log('private_document_mime_mismatch', 'Tipo de documento rechazado.', [
                'file_id' => $fileId, 'type' => $type, 'stored_mime' => $stored, 'detected_mime' => $detected,
            ]);
            Response::abort(404, 'Documento no encontrado.');
        }

        $fallback = $type === 'receipt' ? 'comprobante.pdf' : 'voucher.' . self::extension($detected);
        $name = self::safeFilename($downloadName ?: (string)$file['original_name'], $fallback);
        $disposition = $download ? 'attachment' : 'inline';
        header('Content-Type: ' . $detected);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: ' . $disposition . '; filename="' . $name . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, private, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        readfile($path);
        exit;
    }

    private static function safeFilename(string $name, string $fallback): string
    {
        $name = basename(str_replace(['"', "\r", "\n"], '', trim($name)));
        $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name) ?: '';
        return $name !== '' ? substr($name, 0, 120) : $fallback;
    }

    private static function extension(string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', default => 'pdf',
        };
    }
}
