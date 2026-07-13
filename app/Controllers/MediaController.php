<?php

final class MediaController extends Controller
{
    private const MAX_IMAGE_BYTES = 5242880;
    private const MAX_PDF_BYTES = 20971520;
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'application/pdf' => 'pdf',
    ];

    public function index(): void
    {
        $pdo = Database::connection();
        // Se excluyen las imagenes del modulo Hero / Sliders: son derivados
        // autogenerados que se administran (y eliminan) desde ese modulo.
        $items = $pdo->query(
            "SELECT f.*, u.name AS uploader_name
             FROM files f
             LEFT JOIN users u ON u.id = f.uploaded_by
             WHERE f.disk_path NOT LIKE 'uploads/sliders/%'
             ORDER BY f.id DESC
             LIMIT 400"
        )->fetchAll();

        $stats = [
            'total' => count($items),
            'images' => count(array_filter($items, fn ($item) => str_starts_with((string)$item['mime_type'], 'image/'))),
            'pdfs' => count(array_filter($items, fn ($item) => $item['mime_type'] === 'application/pdf')),
            'bytes' => array_sum(array_map(fn ($item) => (int)$item['size_bytes'], $items)),
        ];

        render('media/index', ['title' => 'Archivos y Media', 'items' => $items, 'stats' => $stats]);
    }

    public function store(): void
    {
        $files = $this->normalizeFiles($_FILES['files'] ?? $_FILES['file'] ?? null);
        if (!$files) {
            back_with_errors(['Selecciona al menos un archivo.'], $_POST);
        }

        $uploaded = 0;
        $errors = [];
        foreach ($files as $file) {
            $result = $this->storeOne($file);
            if ($result === true) {
                $uploaded++;
            } else {
                $errors[] = $result;
            }
        }

        if ($uploaded > 0) {
            activity('Subio ' . $uploaded . ' archivo(s) a media', 'files');
        }

        if ($uploaded > 0 && !$errors) {
            flash('status', 'Se subieron ' . $uploaded . ' archivo(s) correctamente.');
        } elseif ($uploaded > 0) {
            flash('status', 'Se subieron ' . $uploaded . ' archivo(s). Algunos no se procesaron.');
            $_SESSION['_errors'] = $errors;
        } else {
            back_with_errors($errors ?: ['No se pudo subir ningun archivo.'], $_POST);
        }

        Response::redirect('/media');
    }

    public function delete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = Database::connection()->prepare('SELECT * FROM files WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if ($item) {
            // Las imagenes del hero se eliminan desde el modulo Hero / Sliders:
            // borrarlas aqui eliminaria el slide en cascada (FK image_id).
            if (str_starts_with(str_replace('\\', '/', (string)$item['disk_path']), 'uploads/sliders/')) {
                back_with_errors(['Esta imagen pertenece al módulo Hero / Sliders. Elimínala desde ese módulo.'], []);
            }
            $this->deletePhysicalFile((string)$item['disk_path']);
            Database::connection()->prepare('DELETE FROM files WHERE id = ?')->execute([$id]);
            activity('Elimino archivo ' . $item['original_name'], 'files');
        }
        flash('status', 'Archivo eliminado.');
        Response::redirect('/media');
    }

    public function picker(): void
    {
        $type = trim((string)($_GET['type'] ?? 'image'));
        $where = ($type === 'file' ? '1=1' : "mime_type LIKE 'image/%'")
            . " AND disk_path NOT LIKE 'uploads/sliders/%'";
        $items = Database::connection()->query(
            "SELECT id, disk_path, original_name, mime_type, size_bytes, width, height, alt_text, created_at
             FROM files
             WHERE {$where}
             ORDER BY id DESC
             LIMIT 200"
        )->fetchAll();

        $this->json([
            'items' => array_map(fn ($item) => [
                'id' => (int)$item['id'],
                'url' => absolute_url('/' . $item['disk_path']),
                'path' => url('/' . $item['disk_path']),
                'name' => $item['original_name'],
                'mime' => $item['mime_type'],
                'size' => (int)$item['size_bytes'],
                'width' => $item['width'] ? (int)$item['width'] : null,
                'height' => $item['height'] ? (int)$item['height'] : null,
                'alt' => $item['alt_text'] ?: $item['original_name'],
                'created_at' => $item['created_at'],
            ], $items),
        ]);
    }

    public function uploadJson(): void
    {
        $files = $this->normalizeFiles($_FILES['file'] ?? $_FILES['files'] ?? null);
        if (!$files) {
            $this->json(['error' => 'Selecciona un archivo.'], 422);
        }

        $result = $this->storeOne($files[0], true);
        if (!is_array($result)) {
            $this->json(['error' => $result], 422);
        }

        activity('Subio archivo desde editor ' . $result['name'], 'files');
        $this->json([
            'location' => $result['url'],
            'item' => $result,
        ]);
    }

    private function storeOne(array $file, bool $returnItem = false): bool|string|array
    {
        $originalName = basename((string)($file['name'] ?? 'archivo'));
        $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            return $originalName . ': no se pudo subir el archivo.';
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return $originalName . ': archivo temporal invalido.';
        }

        $mime = mime_content_type($tmp) ?: '';
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!isset(self::ALLOWED[$mime])) {
            $mimeByExt = match ($ext) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
                'gif' => 'image/gif',
                'pdf' => 'application/pdf',
                default => '',
            };
            $mime = isset(self::ALLOWED[$mimeByExt]) ? $mimeByExt : $mime;
        }

        if (!isset(self::ALLOWED[$mime])) {
            return $originalName . ': tipo no permitido. Usa JPG, PNG, WebP, GIF o PDF.';
        }

        $size = (int)($file['size'] ?? 0);
        $isImage = str_starts_with($mime, 'image/');
        $limit = $isImage ? self::MAX_IMAGE_BYTES : self::MAX_PDF_BYTES;
        if ($size <= 0 || $size > $limit) {
            return $originalName . ': supera el limite de ' . ($isImage ? '5 MB para imagenes.' : '20 MB para PDF.');
        }

        $dir = dirname(__DIR__, 2) . '/public/uploads/media';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $name = bin2hex(random_bytes(16)) . '.' . self::ALLOWED[$mime];
        $target = $dir . '/' . $name;
        if (!move_uploaded_file($tmp, $target)) {
            return $originalName . ': no se pudo guardar en el servidor.';
        }

        [$width, $height] = $isImage ? (getimagesize($target) ?: [null, null]) : [null, null];
        Database::connection()->prepare(
            'INSERT INTO files (disk_path, original_name, mime_type, size_bytes, width, height, alt_text, uploaded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            'uploads/media/' . $name,
            $originalName,
            $mime,
            $size,
            $width,
            $height,
            trim((string)($_POST['alt_text'] ?? '')) ?: null,
            user()['id'] ?? null,
        ]);

            $id = (int)Database::connection()->lastInsertId();
            if ($returnItem) {
                return [
                    'id' => $id,
                    'url' => absolute_url('/uploads/media/' . $name),
                    'path' => url('/uploads/media/' . $name),
                    'name' => $originalName,
                    'mime' => $mime,
                    'size' => $size,
                    'width' => $width,
                    'height' => $height,
                    'alt' => trim((string)($_POST['alt_text'] ?? '')) ?: $originalName,
                ];
            }

            return true;
    }

    private function normalizeFiles(?array $raw): array
    {
        if (!$raw) {
            return [];
        }

        if (is_array($raw['name'] ?? null)) {
            $files = [];
            foreach ($raw['name'] as $i => $name) {
                $files[] = [
                    'name' => $name,
                    'type' => $raw['type'][$i] ?? null,
                    'tmp_name' => $raw['tmp_name'][$i] ?? null,
                    'error' => $raw['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $raw['size'][$i] ?? 0,
                ];
            }
            return $files;
        }

        return [$raw];
    }

    private function deletePhysicalFile(string $diskPath): void
    {
        delete_public_upload($diskPath);
    }

    private function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
