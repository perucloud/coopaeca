<?php

use Dompdf\Dompdf;
use Dompdf\Options;

final class ProductController extends Controller
{
    public function index(): void
    {
        $q = trim((string)($_GET['q'] ?? ''));
        $status = trim((string)($_GET['status'] ?? ''));
        $categoryId = (int)($_GET['category_id'] ?? 0);
        $featured = trim((string)($_GET['featured'] ?? ''));

        $where = ['1=1'];
        $params = [];
        if ($q !== '') {
            $where[] = '(p.name LIKE ? OR p.sku LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like);
        }
        if ($status !== '' && in_array($status, ['draft', 'published'], true)) {
            $where[] = 'p.status = ?';
            $params[] = $status;
        }
        if ($featured === '1') {
            $where[] = 'p.is_featured = 1';
        }
        if ($categoryId > 0) {
            $where[] = 'EXISTS (SELECT 1 FROM product_category pc2 WHERE pc2.product_id = p.id AND pc2.category_id = ?)';
            $params[] = $categoryId;
        }

        $stmt = Database::connection()->prepare(
            'SELECT p.*, f.disk_path AS cover_path, f.original_name AS cover_name
             FROM products p
             LEFT JOIN files f ON f.id = p.cover_image_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY p.is_featured DESC, p.id DESC'
        );
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        $categories = Database::connection()->query(
            "SELECT pc.product_id, c.name AS cat_name, c.slug AS cat_slug
             FROM product_category pc
             JOIN categories c ON c.id = pc.category_id
             WHERE c.type = 'product'"
        )->fetchAll(PDO::FETCH_GROUP);

        $allCategories = Database::connection()->query(
            "SELECT id, name FROM categories WHERE type = 'product' AND is_active = 1 ORDER BY position ASC"
        )->fetchAll();

        render('products/index', [
            'title' => 'Productos',
            'items' => $items,
            'categories' => $categories,
            'allCategories' => $allCategories,
            'filters' => ['q' => $q, 'status' => $status, 'category_id' => $categoryId, 'featured' => $featured],
        ]);
    }

    public function pdf(): void
    {
        $items = Database::connection()->query(
            'SELECT p.*, f.disk_path AS cover_path
             FROM products p
             LEFT JOIN files f ON f.id = p.cover_image_id
             ORDER BY p.is_featured DESC, p.name ASC'
        )->fetchAll();

        $categories = Database::connection()->query(
            "SELECT pc.product_id, c.name AS cat_name
             FROM product_category pc
             JOIN categories c ON c.id = pc.category_id
             WHERE c.type = 'product'"
        )->fetchAll(PDO::FETCH_GROUP);

        $settings = Database::connection()
            ->query('SELECT setting_key, setting_value FROM settings')
            ->fetchAll(PDO::FETCH_KEY_PAIR);

        $rows = '';
        foreach ($items as $item) {
            $catNames = array_map(fn ($c) => $c['cat_name'], $categories[(int)$item['id']] ?? []);
            $priceHtml = $item['sale_price'] !== null
                ? '<span style="text-decoration:line-through;color:#94a3b8">S/ ' . number_format((float)$item['price'], 2) . '</span> S/ ' . number_format((float)$item['sale_price'], 2)
                : 'S/ ' . number_format((float)$item['price'], 2);
            $stockLabel = $item['stock'] === null ? 'Sin control' : (int)$item['stock'] . ' und.';
            $statusLabel = $item['status'] === 'published' ? 'Publicado' : 'Borrador';

            $rows .= '<tr>'
                . '<td>' . e((string)$item['name']) . '<br><span class="muted">' . e((string)($item['sku'] ?: 'Sin SKU')) . '</span></td>'
                . '<td>' . e($catNames ? implode(', ', $catNames) : '-') . '</td>'
                . '<td>' . $priceHtml . '</td>'
                . '<td>' . e($stockLabel) . '</td>'
                . '<td>' . e($statusLabel) . '</td>'
                . '</tr>';
        }

        $siteName = e((string)($settings['site_name'] ?? 'COOPAECA'));
        $generatedAt = date('d/m/Y H:i');
        $total = count($items);

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: 'Helvetica', sans-serif; color: #1e293b; font-size: 12px; }
    h1 { font-size: 18px; margin: 0 0 4px; color: #14532d; }
    .subtitle { color: #64748b; margin: 0 0 18px; font-size: 11px; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #14532d; color: #fff; text-align: left; padding: 8px 10px; font-size: 11px; }
    td { padding: 7px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
    tr:nth-child(even) td { background: #f8fafc; }
    .muted { color: #94a3b8; font-size: 10px; }
    .footer { margin-top: 16px; color: #94a3b8; font-size: 10px; }
</style>
</head>
<body>
    <h1>{$siteName} - Listado de productos</h1>
    <p class="subtitle">Generado el {$generatedAt} - {$total} producto(s)</p>
    <table>
        <thead>
            <tr><th>Producto</th><th>Categoria</th><th>Precio</th><th>Stock</th><th>Estado</th></tr>
        </thead>
        <tbody>
            {$rows}
        </tbody>
    </table>
    <p class="footer">Documento generado automaticamente desde el panel administrativo de {$siteName}.</p>
</body>
</html>
HTML;

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="productos-' . date('Y-m-d') . '.pdf"');
        echo $dompdf->output();
        exit;
    }

    public function create(): void
    {
        $productCats = Database::connection()->query(
            "SELECT id, name, slug FROM categories WHERE type = 'product' AND is_active = 1 ORDER BY position ASC"
        )->fetchAll();
        $allImages = Database::connection()->query(
            "SELECT id, disk_path, original_name FROM files WHERE mime_type LIKE 'image/%' ORDER BY id DESC LIMIT 100"
        )->fetchAll();
        render('products/form', [
            'title' => 'Nuevo producto',
            'item' => null,
            'productCats' => $productCats,
            'allImages' => $allImages,
        ]);
    }

    public function store(): void
    {
        $data = $this->validated();
        $initialStock = $data['stock'];
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'INSERT INTO products (sku, name, name_en, slug, short_description, short_description_en, description, description_en, origin, origin_en, variety, variety_en, fermentation, fermentation_en, humidity, altitude, altitude_en, grain_count, grain_index, certification, certification_en, presentation, presentation_en, price, sale_price, stock, cover_image_id, is_featured, status, meta_title, meta_title_en, meta_description, meta_description_en)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $data['sku'],
                $data['name'],
                $data['name_en'],
                $this->uniqueSlug($data['name']),
                $data['short_description'],
                $data['short_description_en'],
                $data['description'],
                $data['description_en'],
                $data['origin'],
                $data['origin_en'],
                $data['variety'],
                $data['variety_en'],
                $data['fermentation'],
                $data['fermentation_en'],
                $data['humidity'],
                $data['altitude'],
                $data['altitude_en'],
                $data['grain_count'],
                $data['grain_index'],
                $data['certification'],
                $data['certification_en'],
                $data['presentation'],
                $data['presentation_en'],
                $data['price'],
                $data['sale_price'],
                $initialStock !== null ? 0 : null,
                $data['cover_image_id'],
                $data['is_featured'],
                $data['status'],
                $data['meta_title'],
                $data['meta_title_en'],
                $data['meta_description'],
                $data['meta_description_en'],
            ]);
            $productId = (int)$pdo->lastInsertId();
            $this->syncCategories($productId, $data['category_ids']);

            if ($initialStock !== null && $initialStock > 0) {
                InventoryService::setInitialStock($productId, $initialStock, (int)user()['id']);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            back_with_errors([$e->getMessage()], $_POST);
        }

        activity('Creo producto ' . $data['name'], 'products');
        flash('status', 'Producto creado.');
        Response::redirect('/products');
    }

    public function edit(): void
    {
        $item = $this->find((int)($_GET['id'] ?? 0));
        $productCats = Database::connection()->query(
            "SELECT id, name, slug FROM categories WHERE type = 'product' AND is_active = 1 ORDER BY position ASC"
        )->fetchAll();
        $selectedCatIds = Database::connection()->prepare(
            'SELECT category_id FROM product_category WHERE product_id = ?'
        );
        $selectedCatIds->execute([(int)$item['id']]);
        $selectedCatIds = array_column($selectedCatIds->fetchAll(), 'category_id');
        $allImages = Database::connection()->query(
            "SELECT id, disk_path, original_name FROM files WHERE mime_type LIKE 'image/%' ORDER BY id DESC LIMIT 100"
        )->fetchAll();
        render('products/form', [
            'title' => 'Editar producto',
            'item' => $item,
            'productCats' => $productCats,
            'selectedCatIds' => $selectedCatIds,
            'allImages' => $allImages,
        ]);
    }

    public function update(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $this->find($id);
        $data = $this->validated($id);
        Database::connection()->prepare(
            'UPDATE products SET sku = ?, name = ?, name_en = ?, short_description = ?, short_description_en = ?, description = ?, description_en = ?, origin = ?, origin_en = ?, variety = ?, variety_en = ?, fermentation = ?, fermentation_en = ?, humidity = ?, altitude = ?, altitude_en = ?, grain_count = ?, grain_index = ?, certification = ?, certification_en = ?, presentation = ?, presentation_en = ?, price = ?, sale_price = ?, cover_image_id = ?, is_featured = ?, status = ?, meta_title = ?, meta_title_en = ?, meta_description = ?, meta_description_en = ?, updated_at = NOW() WHERE id = ?'
        )->execute([
            $data['sku'],
            $data['name'],
            $data['name_en'],
            $data['short_description'],
            $data['short_description_en'],
            $data['description'],
            $data['description_en'],
            $data['origin'],
            $data['origin_en'],
            $data['variety'],
            $data['variety_en'],
            $data['fermentation'],
            $data['fermentation_en'],
            $data['humidity'],
            $data['altitude'],
            $data['altitude_en'],
            $data['grain_count'],
            $data['grain_index'],
            $data['certification'],
            $data['certification_en'],
            $data['presentation'],
            $data['presentation_en'],
            $data['price'],
            $data['sale_price'],
            $data['cover_image_id'],
            $data['is_featured'],
            $data['status'],
            $data['meta_title'],
            $data['meta_title_en'],
            $data['meta_description'],
            $data['meta_description_en'],
            $id,
        ]);
        $this->syncCategories($id, $data['category_ids']);

        activity('Actualizo producto ' . $data['name'], 'products');
        flash('status', 'Producto actualizado.');
        Response::redirect('/products');
    }

    public function delete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $item = $this->find($id);
        Database::connection()->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
        activity('Elimino producto ' . $item['name'], 'products');
        flash('status', 'Producto eliminado.');
        Response::redirect('/products');
    }

    private function validated(int $ignoreId = 0): array
    {
        $sku = trim((string)($_POST['sku'] ?? ''));
        $name = trim((string)($_POST['name'] ?? ''));
        $nameEn = trim((string)($_POST['name_en'] ?? ''));
        $short = trim((string)($_POST['short_description'] ?? ''));
        $shortEn = trim((string)($_POST['short_description_en'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $descriptionEn = trim((string)($_POST['description_en'] ?? ''));
        $price = (float)($_POST['price'] ?? 0);
        $salePrice = $_POST['sale_price'] !== '' ? (float)$_POST['sale_price'] : null;
        $stock = $_POST['stock'] !== '' ? (int)$_POST['stock'] : null;
        $coverImageId = !empty($_POST['cover_image_id']) ? (int)$_POST['cover_image_id'] : null;
        $featured = !empty($_POST['is_featured']) ? 1 : 0;
        $status = in_array($_POST['status'] ?? 'draft', ['draft', 'published'], true) ? $_POST['status'] : 'draft';
        $metaTitle = trim((string)($_POST['meta_title'] ?? ''));
        $metaTitleEn = trim((string)($_POST['meta_title_en'] ?? ''));
        $metaDescription = trim((string)($_POST['meta_description'] ?? ''));
        $metaDescriptionEn = trim((string)($_POST['meta_description_en'] ?? ''));
        $origin = trim((string)($_POST['origin'] ?? ''));
        $originEn = trim((string)($_POST['origin_en'] ?? ''));
        $variety = trim((string)($_POST['variety'] ?? ''));
        $varietyEn = trim((string)($_POST['variety_en'] ?? ''));
        $fermentation = trim((string)($_POST['fermentation'] ?? ''));
        $fermentationEn = trim((string)($_POST['fermentation_en'] ?? ''));
        $humidity = trim((string)($_POST['humidity'] ?? ''));
        $altitude = trim((string)($_POST['altitude'] ?? ''));
        $altitudeEn = trim((string)($_POST['altitude_en'] ?? ''));
        $grainCount = trim((string)($_POST['grain_count'] ?? ''));
        $grainIndex = trim((string)($_POST['grain_index'] ?? ''));
        $certification = trim((string)($_POST['certification'] ?? ''));
        $certificationEn = trim((string)($_POST['certification_en'] ?? ''));
        $presentation = trim((string)($_POST['presentation'] ?? ''));
        $presentationEn = trim((string)($_POST['presentation_en'] ?? ''));
        $catIds = array_map('intval', $_POST['category_ids'] ?? []);
        $errors = [];
        if ($name === '') $errors[] = 'El nombre es obligatorio.';
        if ($status === 'published') {
            if ($nameEn === '') $errors[] = 'El nombre en ingles es obligatorio para publicar.';
            if ($shortEn === '') $errors[] = 'La descripcion corta en ingles es obligatoria para publicar.';
            if ($descriptionEn === '') $errors[] = 'La descripcion completa en ingles es obligatoria para publicar.';
            foreach ([
                'origen' => [$origin, $originEn],
                'variedad' => [$variety, $varietyEn],
                'fermentacion' => [$fermentation, $fermentationEn],
                'altitud' => [$altitude, $altitudeEn],
                'certificacion' => [$certification, $certificationEn],
                'presentacion' => [$presentation, $presentationEn],
            ] as $label => [$es, $en]) {
                if ($es !== '' && $en === '') {
                    $errors[] = 'La traduccion en ingles de ' . $label . ' es obligatoria para publicar.';
                }
            }
        }
        if ($sku !== '') {
            $stmt = Database::connection()->prepare('SELECT id FROM products WHERE sku = ? AND id <> ? LIMIT 1');
            $stmt->execute([$sku, $ignoreId]);
            if ($stmt->fetch()) $errors[] = 'El SKU ya existe.';
        }
        if ($coverImageId) {
            $stmt = Database::connection()->prepare('SELECT 1 FROM files WHERE id = ? LIMIT 1');
            $stmt->execute([$coverImageId]);
            if (!$stmt->fetch()) $errors[] = 'La imagen de portada no existe.';
        }
        if ($errors) back_with_errors($errors, $_POST);
        return [
            'sku' => $sku ?: null,
            'name' => $name,
            'name_en' => $nameEn ?: null,
            'short_description' => $short ?: null,
            'short_description_en' => $shortEn ?: null,
            'description' => $description ?: null,
            'description_en' => $descriptionEn ?: null,
            'price' => $price,
            'sale_price' => $salePrice,
            'stock' => $stock,
            'cover_image_id' => $coverImageId,
            'is_featured' => $featured,
            'status' => $status,
            'meta_title' => $metaTitle ?: null,
            'meta_title_en' => $metaTitleEn ?: null,
            'meta_description' => $metaDescription ?: null,
            'meta_description_en' => $metaDescriptionEn ?: null,
            'origin' => $origin ?: null,
            'origin_en' => $originEn ?: null,
            'variety' => $variety ?: null,
            'variety_en' => $varietyEn ?: null,
            'fermentation' => $fermentation ?: null,
            'fermentation_en' => $fermentationEn ?: null,
            'humidity' => $humidity ?: null,
            'altitude' => $altitude ?: null,
            'altitude_en' => $altitudeEn ?: null,
            'grain_count' => $grainCount ?: null,
            'grain_index' => $grainIndex ?: null,
            'certification' => $certification ?: null,
            'certification_en' => $certificationEn ?: null,
            'presentation' => $presentation ?: null,
            'presentation_en' => $presentationEn ?: null,
            'category_ids' => $catIds,
        ];
    }

    private function syncCategories(int $productId, array $catIds): void
    {
        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM product_category WHERE product_id = ?')->execute([$productId]);
        if ($catIds) {
            $stmt = $pdo->prepare('INSERT INTO product_category (product_id, category_id) VALUES (?, ?)');
            foreach ($catIds as $catId) {
                $stmt->execute([$productId, $catId]);
            }
        }
    }

    private function find(int $id): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) Response::abort(404, 'Producto no encontrado.');
        return $item;
    }

    private function uniqueSlug(string $value): string
    {
        $base = slugify($value);
        $slug = $base;
        $i = 2;
        $stmt = Database::connection()->prepare('SELECT 1 FROM products WHERE slug = ? LIMIT 1');
        while (true) {
            $stmt->execute([$slug]);
            if (!$stmt->fetch()) return $slug;
            $slug = $base . '-' . $i++;
        }
    }
}
