<?php

final class PostController extends Controller
{
    private const MAX_IMAGE_BYTES = 5242880;
    private const IMAGE_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private const CATEGORIES = [
        'General',
        'Acopio',
        'Capacitacion',
        'Derivados',
        'Calidad',
        'Comunidad',
    ];

    public function index(): void
    {
        $items = Database::connection()->query(
            'SELECT p.*, u.name AS author_name, f.disk_path AS image_path
             FROM posts p
             JOIN users u ON u.id = p.author_id
             LEFT JOIN files f ON f.id = p.featured_image_id
             ORDER BY p.id DESC'
        )->fetchAll();
        render('posts/index', ['title' => 'Noticias', 'items' => $items]);
    }

    public function create(): void
    {
        render('posts/form', ['title' => 'Nueva noticia', 'item' => null, 'categories' => self::CATEGORIES]);
    }

    public function store(): void
    {
        $data = $this->validated();
        Database::connection()->prepare(
            'INSERT INTO posts (author_id, title, title_en, slug, excerpt, excerpt_en, category, category_en, content, content_en, featured_image_id, status, published_at, meta_title, meta_title_en, meta_description, meta_description_en, meta_keywords, meta_keywords_en)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            user()['id'],
            $data['title'],
            $data['title_en'],
            $this->uniqueSlug($data['slug'] ?: $data['title']),
            $data['excerpt'],
            $data['excerpt_en'],
            $data['category'],
            $data['category_en'],
            $data['content'],
            $data['content_en'],
            $this->resolveFeaturedImage(),
            $data['status'],
            $data['status'] === 'published' ? date('Y-m-d H:i:s') : null,
            $data['meta_title'],
            $data['meta_title_en'],
            $data['meta_description'],
            $data['meta_description_en'],
            $data['meta_keywords'],
            $data['meta_keywords_en'],
        ]);
        activity('Creo noticia ' . $data['title'], 'posts');
        flash('status', 'Noticia creada.');
        Response::redirect('/posts');
    }

    public function edit(): void
    {
        $item = $this->find((int)($_GET['id'] ?? 0));
        render('posts/form', ['title' => 'Editar noticia', 'item' => $item, 'categories' => self::CATEGORIES]);
    }

    public function update(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $item = $this->find($id);
        $data = $this->validated();
        $imageId = $this->resolveFeaturedImage($item['featured_image_id'] ? (int)$item['featured_image_id'] : null);
        Database::connection()->prepare(
            'UPDATE posts SET title = ?, title_en = ?, slug = ?, excerpt = ?, excerpt_en = ?, category = ?, category_en = ?, content = ?, content_en = ?, featured_image_id = ?, status = ?, published_at = IF(? = "published" AND published_at IS NULL, NOW(), published_at), meta_title = ?, meta_title_en = ?, meta_description = ?, meta_description_en = ?, meta_keywords = ?, meta_keywords_en = ?, updated_at = NOW() WHERE id = ?'
        )->execute([
            $data['title'],
            $data['title_en'],
            $this->uniqueSlug($data['slug'] ?: $data['title'], $id),
            $data['excerpt'],
            $data['excerpt_en'],
            $data['category'],
            $data['category_en'],
            $data['content'],
            $data['content_en'],
            $imageId,
            $data['status'],
            $data['status'],
            $data['meta_title'],
            $data['meta_title_en'],
            $data['meta_description'],
            $data['meta_description_en'],
            $data['meta_keywords'],
            $data['meta_keywords_en'],
            $id,
        ]);
        activity('Actualizo noticia ' . $data['title'], 'posts');
        flash('status', 'Noticia actualizada.');
        Response::redirect('/posts');
    }

    public function delete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $item = $this->find($id);
        Database::connection()->prepare('DELETE FROM posts WHERE id = ?')->execute([$id]);
        activity('Elimino noticia ' . $item['title'], 'posts');
        flash('status', 'Noticia eliminada.');
        Response::redirect('/posts');
    }

    private function validated(): array
    {
        $title = trim((string)($_POST['title'] ?? ''));
        $titleEn = trim((string)($_POST['title_en'] ?? ''));
        $excerpt = trim((string)($_POST['excerpt'] ?? ''));
        $excerptEn = trim((string)($_POST['excerpt_en'] ?? ''));
        $category = trim((string)($_POST['category'] ?? 'General'));
        $categoryEn = trim((string)($_POST['category_en'] ?? ''));
        $slug = trim((string)($_POST['slug'] ?? ''));
        $content = $this->sanitizeHtml(trim((string)($_POST['content'] ?? '')));
        $contentEn = $this->sanitizeHtml(trim((string)($_POST['content_en'] ?? '')));
        $status = in_array($_POST['status'] ?? 'draft', ['draft', 'published', 'scheduled'], true) ? $_POST['status'] : 'draft';
        $metaTitle = trim((string)($_POST['meta_title'] ?? ''));
        $metaTitleEn = trim((string)($_POST['meta_title_en'] ?? ''));
        $metaDescription = trim((string)($_POST['meta_description'] ?? ''));
        $metaDescriptionEn = trim((string)($_POST['meta_description_en'] ?? ''));
        $metaKeywords = trim((string)($_POST['meta_keywords'] ?? ''));
        $metaKeywordsEn = trim((string)($_POST['meta_keywords_en'] ?? ''));
        $errors = [];
        if ($title === '') $errors[] = 'El titulo es obligatorio.';
        if (in_array($status, ['published', 'scheduled'], true)) {
            if ($titleEn === '') $errors[] = 'El titulo en ingles es obligatorio para publicar.';
            if ($excerptEn === '') $errors[] = 'El resumen en ingles es obligatorio para publicar.';
            if ($contentEn === '') $errors[] = 'El contenido en ingles es obligatorio para publicar.';
        }
        if ($category === '') $category = 'General';
        if (!in_array($category, self::CATEGORIES, true)) $category = 'General';
        if ($slug !== '' && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            $errors[] = 'El slug solo puede contener letras minusculas, numeros y guiones.';
        }
        if ($errors) back_with_errors($errors, $_POST);
        return [
            'title' => $title,
            'title_en' => $titleEn ?: null,
            'slug' => $slug,
            'excerpt' => $excerpt ?: null,
            'excerpt_en' => $excerptEn ?: null,
            'category' => $category,
            'category_en' => $categoryEn ?: null,
            'content' => $content ?: null,
            'content_en' => $contentEn ?: null,
            'status' => $status,
            'meta_title' => $metaTitle ?: null,
            'meta_title_en' => $metaTitleEn ?: null,
            'meta_description' => $metaDescription ?: null,
            'meta_description_en' => $metaDescriptionEn ?: null,
            'meta_keywords' => $metaKeywords ?: null,
            'meta_keywords_en' => $metaKeywordsEn ?: null,
        ];
    }

    private function find(int $id): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT p.*, u.name AS author_name, f.disk_path AS image_path, f.original_name AS image_name
             FROM posts p
             JOIN users u ON u.id = p.author_id
             LEFT JOIN files f ON f.id = p.featured_image_id
             WHERE p.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) Response::abort(404, 'Noticia no encontrada.');
        return $item;
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = slugify($value);
        $slug = $base;
        $i = 2;
        $stmt = Database::connection()->prepare('SELECT id FROM posts WHERE slug = ? LIMIT 1');
        while (true) {
            $stmt->execute([$slug]);
            $found = $stmt->fetch();
            if (!$found || ($ignoreId !== null && (int)$found['id'] === $ignoreId)) return $slug;
            $slug = $base . '-' . $i++;
        }
    }

    private function storeFeaturedImage(?int $currentId = null): ?int
    {
        $file = $_FILES['featured_image'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $currentId;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            back_with_errors(['No se pudo subir la imagen destacada.'], $_POST);
        }

        if ((int)$file['size'] > self::MAX_IMAGE_BYTES) {
            back_with_errors(['La imagen destacada supera el limite de 5MB.'], $_POST);
        }

        $mime = mime_content_type($file['tmp_name']);
        if (!isset(self::IMAGE_MIMES[$mime])) {
            back_with_errors(['La imagen destacada debe ser JPG, PNG o WebP.'], $_POST);
        }

        $dir = dirname(__DIR__, 2) . '/public/uploads/posts';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $ext = self::IMAGE_MIMES[$mime];
        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        $target = $dir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            back_with_errors(['No se pudo guardar la imagen destacada.'], $_POST);
        }

        [$width, $height] = getimagesize($target) ?: [null, null];
        $pdo = Database::connection();
        $pdo->prepare(
            'INSERT INTO files (disk_path, original_name, mime_type, size_bytes, width, height, alt_text, uploaded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            'uploads/posts/' . $name,
            basename((string)$file['name']),
            $mime,
            (int)$file['size'],
            $width,
            $height,
            trim((string)($_POST['title'] ?? '')) ?: null,
            user()['id'] ?? null,
        ]);

        return (int)$pdo->lastInsertId();
    }

    private function resolveFeaturedImage(?int $currentId = null): ?int
    {
        $mediaId = (int)($_POST['featured_image_id'] ?? 0);
        if ($mediaId > 0) {
            $stmt = Database::connection()->prepare("SELECT id FROM files WHERE id = ? AND mime_type LIKE 'image/%' LIMIT 1");
            $stmt->execute([$mediaId]);
            if ($stmt->fetch()) {
                return $mediaId;
            }
        }

        return $this->storeFeaturedImage($currentId);
    }

    private function sanitizeHtml(string $html): string
    {
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? '';
        $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
        $html = preg_replace('/javascript\s*:/i', '', $html) ?? '';

        $html = preg_replace_callback('/<iframe\b[^>]*>/i', function (array $match): string {
            return preg_match('/src\s*=\s*["\']https:\/\/(www\.)?(youtube\.com|youtu\.be|player\.vimeo\.com)\//i', $match[0])
                ? $match[0]
                : '';
        }, $html) ?? '';

        return strip_tags($html, '<p><br><strong><b><em><i><u><s><ul><ol><li><blockquote><h2><h3><h4><a><img><figure><figcaption><iframe><table><thead><tbody><tr><th><td>');
    }
}
