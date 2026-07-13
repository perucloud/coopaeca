<?php

/**
 * Modulo HERO / SLIDERS del dashboard: administra los slides que se muestran
 * en el hero del landing page (imagen optimizada + textos bilingues ES/EN).
 * Usa las tablas sliders / slider_items (migracion 0014) ampliadas por la
 * migracion 0030 y registra las imagenes procesadas en la tabla files.
 */
final class SliderController extends Controller
{
    public const HERO_SLUG = 'hero';

    public function index(): void
    {
        $items = Database::connection()->prepare(
            'SELECT si.*, f.disk_path, f.size_bytes
             FROM slider_items si
             JOIN files f ON f.id = si.image_id
             WHERE si.slider_id = ?
             ORDER BY si.position ASC, si.id ASC'
        );
        $items->execute([$this->heroSliderId()]);

        render('sliders/index', ['title' => 'Hero / Sliders', 'items' => $items->fetchAll()]);
    }

    public function create(): void
    {
        render('sliders/form', ['title' => 'Nuevo slide', 'item' => null]);
    }

    public function store(): void
    {
        $data = $this->validated();

        $upload = $_FILES['image'] ?? null;
        if (!$upload || (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            back_with_errors(['Selecciona una imagen para el slide.'], $_POST);
        }
        $fileId = $this->storeProcessedImage($upload, $data['title']);

        $pdo = Database::connection();
        $position = (int)$pdo->query(
            'SELECT COALESCE(MAX(position), 0) + 1 FROM slider_items WHERE slider_id = ' . $this->heroSliderId()
        )->fetchColumn();

        try {
            $pdo->prepare(
                'INSERT INTO slider_items (slider_id, image_id, title, title_en, subtitle, subtitle_en, badge, badge_en, position, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $this->heroSliderId(),
                $fileId,
                $data['title'],
                $data['title_en'],
                $data['subtitle'],
                $data['subtitle_en'],
                $data['badge'],
                $data['badge_en'],
                $position,
                $data['is_active'],
            ]);
        } catch (Throwable $e) {
            // No dejar imagenes huerfanas (fisicas ni en files) si fallo el insert.
            $this->deleteImageIfUnused($fileId);
            throw $e;
        }

        activity('Creo slide del hero ' . $data['title'], 'sliders');
        flash('status', 'Slide creado.');
        Response::redirect('/sliders');
    }

    public function edit(): void
    {
        render('sliders/form', ['title' => 'Editar slide', 'item' => $this->find((int)($_GET['id'] ?? 0))]);
    }

    public function update(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $item = $this->find($id);
        $data = $this->validated();

        $imageId = (int)$item['image_id'];
        $upload = $_FILES['image'] ?? null;
        if ($upload && (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $imageId = $this->storeProcessedImage($upload, $data['title']);
        }

        try {
            Database::connection()->prepare(
                'UPDATE slider_items
                 SET image_id = ?, title = ?, title_en = ?, subtitle = ?, subtitle_en = ?, badge = ?, badge_en = ?, is_active = ?
                 WHERE id = ?'
            )->execute([
                $imageId,
                $data['title'],
                $data['title_en'],
                $data['subtitle'],
                $data['subtitle_en'],
                $data['badge'],
                $data['badge_en'],
                $data['is_active'],
                $id,
            ]);
        } catch (Throwable $e) {
            // Si fallo el update con imagen nueva, limpiar la imagen nueva.
            if ($imageId !== (int)$item['image_id']) {
                $this->deleteImageIfUnused($imageId);
            }
            throw $e;
        }

        // Si se reemplazo la imagen, eliminar la anterior (fisica y registro)
        // despues de actualizar para no dejar el slide sin imagen.
        if ($imageId !== (int)$item['image_id']) {
            $this->deleteImageIfUnused((int)$item['image_id']);
        }

        activity('Actualizo slide del hero ' . $data['title'], 'sliders');
        flash('status', 'Slide actualizado.');
        Response::redirect('/sliders');
    }

    public function toggle(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $item = $this->find($id);
        Database::connection()->prepare('UPDATE slider_items SET is_active = ? WHERE id = ?')
            ->execute([(int)!$item['is_active'], $id]);

        activity('Cambio estado de slide del hero ' . ($item['title'] ?: ('#' . $id)), 'sliders');
        flash('status', 'Estado del slide actualizado.');
        Response::redirect('/sliders');
    }

    public function move(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $dir = ($_POST['dir'] ?? '') === 'up' ? 'up' : 'down';
        $item = $this->find($id);

        $pdo = Database::connection();
        $operator = $dir === 'up' ? '<' : '>';
        $order = $dir === 'up' ? 'DESC' : 'ASC';
        $neighbor = $pdo->prepare(
            "SELECT id, position FROM slider_items
             WHERE slider_id = ? AND (position {$operator} ? OR (position = ? AND id {$operator} ?))
             ORDER BY position {$order}, id {$order} LIMIT 1"
        );
        $neighbor->execute([$this->heroSliderId(), $item['position'], $item['position'], $id]);
        $other = $neighbor->fetch();

        if ($other) {
            // Si ambos comparten posicion (datos antiguos), separarlos primero.
            $posA = (int)$item['position'];
            $posB = (int)$other['position'];
            if ($posA === $posB) {
                $posB = $dir === 'up' ? $posA - 1 : $posA + 1;
            }
            $swap = $pdo->prepare('UPDATE slider_items SET position = ? WHERE id = ?');
            $swap->execute([$posB, $id]);
            $swap->execute([$posA, (int)$other['id']]);
        }

        Response::redirect('/sliders');
    }

    public function delete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $item = $this->find($id);

        Database::connection()->prepare('DELETE FROM slider_items WHERE id = ?')->execute([$id]);
        $this->deleteImageIfUnused((int)$item['image_id']);

        activity('Elimino slide del hero ' . ($item['title'] ?: ('#' . $id)), 'sliders');
        flash('status', 'Slide eliminado.');
        Response::redirect('/sliders');
    }

    /**
     * Procesa la imagen subida (crop + resize + WebP + variantes) y la
     * registra en la tabla files. Devuelve el id del registro.
     */
    private function storeProcessedImage(array $upload, string $altText): int
    {
        try {
            $processed = (new ImageOptimizerService())->processHeroImage($upload);
        } catch (RuntimeException $e) {
            back_with_errors([$e->getMessage()], $_POST);
        }

        Database::connection()->prepare(
            'INSERT INTO files (disk_path, original_name, mime_type, size_bytes, width, height, alt_text, uploaded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $processed['disk_path'],
            basename((string)($upload['name'] ?? 'slider')),
            $processed['mime'],
            $processed['size_bytes'],
            $processed['width'],
            $processed['height'],
            $altText ?: null,
            user()['id'] ?? null,
        ]);

        return (int)Database::connection()->lastInsertId();
    }

    /**
     * Elimina el registro en files y los archivos fisicos (variantes) de una
     * imagen de slider, solo si ningun otro slide la sigue usando.
     */
    private function deleteImageIfUnused(int $fileId): void
    {
        $pdo = Database::connection();
        $inUse = $pdo->prepare('SELECT 1 FROM slider_items WHERE image_id = ? LIMIT 1');
        $inUse->execute([$fileId]);
        if ($inUse->fetch()) {
            return;
        }

        $stmt = $pdo->prepare('SELECT disk_path FROM files WHERE id = ? LIMIT 1');
        $stmt->execute([$fileId]);
        $diskPath = (string)($stmt->fetchColumn() ?: '');
        if ($diskPath !== '' && str_starts_with(str_replace('\\', '/', $diskPath), 'uploads/sliders/')) {
            (new ImageOptimizerService())->deleteHeroImage($diskPath);
        }
        $pdo->prepare('DELETE FROM files WHERE id = ?')->execute([$fileId]);
    }

    private function validated(): array
    {
        // trim + null solo cuando queda vacio ('' -> null, pero "0" se conserva).
        $text = static function (string $field): ?string {
            $value = trim((string)($_POST[$field] ?? ''));
            return $value === '' ? null : $value;
        };

        $data = [
            'title' => trim((string)($_POST['title'] ?? '')),
            'title_en' => $text('title_en'),
            'subtitle' => $text('subtitle'),
            'subtitle_en' => $text('subtitle_en'),
            'badge' => $text('badge'),
            'badge_en' => $text('badge_en'),
            'is_active' => !empty($_POST['is_active']) ? 1 : 0,
        ];

        $errors = [];
        if ($data['title'] === '') {
            $errors[] = 'El título del slide es obligatorio.';
        }
        foreach (['title', 'title_en', 'subtitle', 'subtitle_en', 'badge', 'badge_en'] as $field) {
            if ($data[$field] !== null && mb_strlen($data[$field]) > 255) {
                $errors[] = 'El campo ' . $field . ' supera los 255 caracteres.';
            }
        }
        if ($errors) {
            back_with_errors($errors, $_POST);
        }

        return $data;
    }

    private function find(int $id): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT si.*, f.disk_path FROM slider_items si
             JOIN files f ON f.id = si.image_id
             WHERE si.id = ? AND si.slider_id = ? LIMIT 1'
        );
        $stmt->execute([$id, $this->heroSliderId()]);
        $item = $stmt->fetch();
        if (!$item) {
            Response::abort(404, 'Slide no encontrado.');
        }
        return $item;
    }

    private function heroSliderId(): int
    {
        static $id = null;
        if ($id !== null) {
            return $id;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id FROM sliders WHERE slug = ? LIMIT 1');
        $stmt->execute([self::HERO_SLUG]);
        $found = $stmt->fetchColumn();
        if ($found === false) {
            $pdo->prepare('INSERT INTO sliders (name, slug) VALUES (?, ?)')->execute(['Hero principal', self::HERO_SLUG]);
            $found = $pdo->lastInsertId();
        }

        return $id = (int)$found;
    }
}
