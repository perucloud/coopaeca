<?php

final class GalleryController extends Controller
{
    public function index(): void
    {
        $items = Database::connection()->query(
            "SELECT g.*, f.disk_path AS cover_path,
                    (SELECT COUNT(*) FROM gallery_images gi WHERE gi.gallery_id = g.id) AS image_count
             FROM galleries g
             LEFT JOIN files f ON f.id = g.cover_image_id
             ORDER BY g.position ASC, g.id DESC"
        )->fetchAll();

        render('galleries/index', ['title' => 'Galería', 'items' => $items]);
    }

    public function create(): void
    {
        $allImages = $this->allImages();
        render('galleries/form', ['title' => 'Nuevo álbum', 'item' => null, 'images' => [], 'allImages' => $allImages]);
    }

    public function store(): void
    {
        $data = $this->validated();
        $pdo = Database::connection();
        $pdo->prepare(
            'INSERT INTO galleries (title, slug, description, cover_image_id, is_active, position) VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([
            $data['title'],
            $this->uniqueSlug($data['title']),
            $data['description'],
            $data['cover_image_id'],
            $data['is_active'],
            $data['position'],
        ]);
        $id = (int)$pdo->lastInsertId();
        $this->syncImages($id, $data['image_ids'], $data['captions']);

        activity('Creo álbum de galería ' . $data['title'], 'galleries');
        flash('status', 'Álbum creado.');
        Response::redirect('/galleries');
    }

    public function edit(): void
    {
        $item = $this->find((int)($_GET['id'] ?? 0));
        render('galleries/form', [
            'title' => 'Editar álbum',
            'item' => $item,
            'images' => $this->imagesDe((int)$item['id']),
            'allImages' => $this->allImages(),
        ]);
    }

    public function update(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $this->find($id);
        $data = $this->validated();
        Database::connection()->prepare(
            'UPDATE galleries SET title = ?, description = ?, cover_image_id = ?, is_active = ?, position = ? WHERE id = ?'
        )->execute([
            $data['title'],
            $data['description'],
            $data['cover_image_id'],
            $data['is_active'],
            $data['position'],
            $id,
        ]);
        $this->syncImages($id, $data['image_ids'], $data['captions']);

        activity('Actualizo álbum de galería ' . $data['title'], 'galleries');
        flash('status', 'Álbum actualizado.');
        Response::redirect('/galleries');
    }

    public function toggle(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $item = $this->find($id);
        Database::connection()->prepare('UPDATE galleries SET is_active = ? WHERE id = ?')
            ->execute([(int)!$item['is_active'], $id]);
        activity('Cambio estado de álbum ' . $item['title'], 'galleries');
        flash('status', 'Estado del álbum actualizado.');
        Response::redirect('/galleries');
    }

    public function delete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $item = $this->find($id);
        Database::connection()->prepare('DELETE FROM galleries WHERE id = ?')->execute([$id]);
        activity('Elimino álbum de galería ' . $item['title'], 'galleries');
        flash('status', 'Álbum eliminado.');
        Response::redirect('/galleries');
    }

    private function syncImages(int $galleryId, array $imageIds, array $captions): void
    {
        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM gallery_images WHERE gallery_id = ?')->execute([$galleryId]);
        if ($imageIds) {
            $stmt = $pdo->prepare('INSERT INTO gallery_images (gallery_id, file_id, caption, position) VALUES (?, ?, ?, ?)');
            foreach ($imageIds as $position => $fileId) {
                $stmt->execute([$galleryId, $fileId, $captions[$fileId] ?? null, $position]);
            }
        }
    }

    private function imagesDe(int $galleryId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT gi.*, f.disk_path, f.original_name FROM gallery_images gi
             JOIN files f ON f.id = gi.file_id
             WHERE gi.gallery_id = ? ORDER BY gi.position ASC'
        );
        $stmt->execute([$galleryId]);
        return $stmt->fetchAll();
    }

    private function allImages(): array
    {
        return Database::connection()->query(
            "SELECT id, disk_path, original_name FROM files WHERE mime_type LIKE 'image/%' ORDER BY id DESC LIMIT 300"
        )->fetchAll();
    }

    private function validated(): array
    {
        $title = trim((string)($_POST['title'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $coverImageId = !empty($_POST['cover_image_id']) ? (int)$_POST['cover_image_id'] : null;
        $position = (int)($_POST['position'] ?? 0);
        $active = !empty($_POST['is_active']) ? 1 : 0;
        $imageIds = array_values(array_unique(array_map('intval', $_POST['image_ids'] ?? [])));
        $captionsRaw = $_POST['captions'] ?? [];
        $captions = [];
        foreach ($captionsRaw as $fileId => $caption) {
            $captions[(int)$fileId] = trim((string)$caption) ?: null;
        }

        if ($title === '') back_with_errors(['El título es obligatorio.'], $_POST);

        if ($coverImageId) {
            $stmt = Database::connection()->prepare('SELECT 1 FROM files WHERE id = ? LIMIT 1');
            $stmt->execute([$coverImageId]);
            if (!$stmt->fetch()) back_with_errors(['La imagen de portada no existe.'], $_POST);
        }

        return [
            'title' => $title,
            'description' => $description ?: null,
            'cover_image_id' => $coverImageId,
            'is_active' => $active,
            'position' => $position,
            'image_ids' => $imageIds,
            'captions' => $captions,
        ];
    }

    private function find(int $id): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM galleries WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) Response::abort(404, 'Álbum no encontrado.');
        return $item;
    }

    private function uniqueSlug(string $value): string
    {
        $base = slugify($value);
        $slug = $base;
        $i = 2;
        $stmt = Database::connection()->prepare('SELECT 1 FROM galleries WHERE slug = ? LIMIT 1');
        while (true) {
            $stmt->execute([$slug]);
            if (!$stmt->fetch()) return $slug;
            $slug = $base . '-' . $i++;
        }
    }
}
