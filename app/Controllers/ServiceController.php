<?php

final class ServiceController extends Controller
{
    public function index(): void
    {
        $items = Database::connection()->query('SELECT * FROM services ORDER BY position ASC, id DESC')->fetchAll();
        render('services/index', ['title' => 'Servicios', 'items' => $items]);
    }

    public function create(): void
    {
        render('services/form', ['title' => 'Nuevo servicio', 'item' => null]);
    }

    public function store(): void
    {
        $data = $this->validated();
        Database::connection()->prepare(
            'INSERT INTO services (name, name_en, slug, icon_name, short_description, short_description_en, description, description_en, position, is_active, meta_title, meta_title_en, meta_description, meta_description_en)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $data['name'],
            $data['name_en'],
            $this->uniqueSlug($data['name']),
            $data['icon_name'],
            $data['short_description'],
            $data['short_description_en'],
            $data['description'],
            $data['description_en'],
            $data['position'],
            $data['is_active'],
            $data['meta_title'],
            $data['meta_title_en'],
            $data['meta_description'],
            $data['meta_description_en'],
        ]);
        activity('Creo servicio ' . $data['name'], 'services');
        flash('status', 'Servicio creado.');
        Response::redirect('/services');
    }

    public function edit(): void
    {
        render('services/form', ['title' => 'Editar servicio', 'item' => $this->find((int)($_GET['id'] ?? 0))]);
    }

    public function update(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $this->find($id);
        $data = $this->validated();
        Database::connection()->prepare(
            'UPDATE services SET name = ?, name_en = ?, icon_name = ?, short_description = ?, short_description_en = ?, description = ?, description_en = ?, position = ?, is_active = ?, meta_title = ?, meta_title_en = ?, meta_description = ?, meta_description_en = ?, updated_at = NOW() WHERE id = ?'
        )->execute([
            $data['name'],
            $data['name_en'],
            $data['icon_name'],
            $data['short_description'],
            $data['short_description_en'],
            $data['description'],
            $data['description_en'],
            $data['position'],
            $data['is_active'],
            $data['meta_title'],
            $data['meta_title_en'],
            $data['meta_description'],
            $data['meta_description_en'],
            $id,
        ]);
        activity('Actualizo servicio ' . $data['name'], 'services');
        flash('status', 'Servicio actualizado.');
        Response::redirect('/services');
    }

    public function toggle(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $item = $this->find($id);
        if ((int)$item['is_active'] === 0 && !$this->hasEnglishContent($item)) {
            back_with_errors(['Completa el nombre, descripcion corta y descripcion en ingles antes de activar el servicio.'], $_POST);
        }
        Database::connection()->prepare('UPDATE services SET is_active = ?, updated_at = NOW() WHERE id = ?')
            ->execute([(int)!$item['is_active'], $id]);
        activity('Cambio estado de servicio ' . $item['name'], 'services');
        flash('status', 'Estado del servicio actualizado.');
        Response::redirect('/services');
    }

    public function delete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $item = $this->find($id);
        Database::connection()->prepare('DELETE FROM services WHERE id = ?')->execute([$id]);
        activity('Elimino servicio ' . $item['name'], 'services');
        flash('status', 'Servicio eliminado.');
        Response::redirect('/services');
    }

    private function validated(): array
    {
        $name = trim((string)($_POST['name'] ?? ''));
        $nameEn = trim((string)($_POST['name_en'] ?? ''));
        $short = trim((string)($_POST['short_description'] ?? ''));
        $shortEn = trim((string)($_POST['short_description_en'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $descriptionEn = trim((string)($_POST['description_en'] ?? ''));
        $icon = trim((string)($_POST['icon_name'] ?? 'layers'));
        $position = (int)($_POST['position'] ?? 0);
        $active = !empty($_POST['is_active']) ? 1 : 0;
        $metaTitle = trim((string)($_POST['meta_title'] ?? ''));
        $metaTitleEn = trim((string)($_POST['meta_title_en'] ?? ''));
        $metaDescription = trim((string)($_POST['meta_description'] ?? ''));
        $metaDescriptionEn = trim((string)($_POST['meta_description_en'] ?? ''));
        $errors = [];
        if ($name === '') $errors[] = 'El nombre es obligatorio.';
        if ($active === 1) {
            if ($nameEn === '') $errors[] = 'El nombre en ingles es obligatorio para activar el servicio.';
            if ($shortEn === '') $errors[] = 'La descripcion corta en ingles es obligatoria para activar el servicio.';
            if ($descriptionEn === '') $errors[] = 'La descripcion en ingles es obligatoria para activar el servicio.';
        }
        if ($errors) back_with_errors($errors, $_POST);
        return [
            'name' => $name,
            'name_en' => $nameEn ?: null,
            'icon_name' => $this->allowedIcon($icon),
            'short_description' => $short ?: null,
            'short_description_en' => $shortEn ?: null,
            'description' => $description ?: null,
            'description_en' => $descriptionEn ?: null,
            'position' => $position,
            'is_active' => $active,
            'meta_title' => $metaTitle ?: null,
            'meta_title_en' => $metaTitleEn ?: null,
            'meta_description' => $metaDescription ?: null,
            'meta_description_en' => $metaDescriptionEn ?: null,
        ];
    }

    private function allowedIcon(string $icon): string
    {
        $allowed = ['layers', 'package', 'shield', 'activity', 'share', 'users', 'check-circle', 'tag', 'image'];
        return in_array($icon, $allowed, true) ? $icon : 'layers';
    }

    private function hasEnglishContent(array $item): bool
    {
        return trim((string)($item['name_en'] ?? '')) !== ''
            && trim((string)($item['short_description_en'] ?? '')) !== ''
            && trim((string)($item['description_en'] ?? '')) !== '';
    }

    private function find(int $id): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM services WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) Response::abort(404, 'Servicio no encontrado.');
        return $item;
    }

    private function uniqueSlug(string $value): string
    {
        $base = slugify($value);
        $slug = $base;
        $i = 2;
        $stmt = Database::connection()->prepare('SELECT 1 FROM services WHERE slug = ? LIMIT 1');
        while (true) {
            $stmt->execute([$slug]);
            if (!$stmt->fetch()) return $slug;
            $slug = $base . '-' . $i++;
        }
    }
}
