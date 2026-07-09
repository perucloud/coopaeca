<?php

final class LandingController extends Controller
{
    public function index(): void
    {
        $products = Database::connection()->query(
            "SELECT p.*, f.disk_path AS cover_path
             FROM products p
             LEFT JOIN files f ON f.id = p.cover_image_id
             WHERE p.status = 'published'
             ORDER BY p.is_featured DESC, p.id DESC
             LIMIT 6"
        )->fetchAll();

        // Attach categories per product
        if ($products) {
            $ids = array_column($products, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $catRows = Database::connection()->prepare(
                "SELECT pc.product_id, c.name AS cat_name, c.slug AS cat_slug
                 FROM product_category pc
                 JOIN categories c ON c.id = pc.category_id
                 WHERE pc.product_id IN ({$placeholders}) AND c.type = 'product'
                 ORDER BY c.position ASC"
            );
            $catRows->execute($ids);
            $catMap = [];
            foreach ($catRows->fetchAll() as $r) {
                $catMap[(int)$r['product_id']][] = ['name' => $r['cat_name'], 'slug' => $r['cat_slug']];
            }
            foreach ($products as &$p) {
                $p['categories'] = $catMap[(int)$p['id']] ?? [];
            }
            unset($p);
        }

        $services = Database::connection()->query(
            "SELECT * FROM services WHERE is_active = 1 ORDER BY position ASC LIMIT 6"
        )->fetchAll();

        $socials = $this->socialesActivos();

        $posts = Database::connection()->query(
            "SELECT p.*, f.disk_path AS image_path
             FROM posts p
             LEFT JOIN files f ON f.id = p.featured_image_id
             WHERE p.status = 'published' AND (p.published_at IS NULL OR p.published_at <= NOW())
             ORDER BY COALESCE(p.published_at, p.created_at) DESC
             LIMIT 6"
        )->fetchAll();

        $settings = Database::connection()
            ->query('SELECT setting_key, setting_value FROM settings')
            ->fetchAll(PDO::FETCH_KEY_PAIR);

        render('landing/index', [
            'title'    => $settings['site_title'] ?? config_app('name'),
            'products' => $products,
            'services' => $services,
            'posts'    => $posts,
            'socials'  => $socials,
            'settings' => $settings,
        ], 'layouts/landing');
    }

    public function about(): void
    {
        $settings = Database::connection()
            ->query('SELECT setting_key, setting_value FROM settings')
            ->fetchAll(PDO::FETCH_KEY_PAIR);

        render('landing/about', [
            'title' => t('nav.about'),
            'settings' => $settings,
            'socials' => $this->socialesActivos(),
        ], 'layouts/landing');
    }

    public function productDetail(): void
    {
        $slug = trim((string)($_GET['slug'] ?? ''));
        if ($slug === '') {
            Response::abort(404, t('search.product') . ' no encontrado.');
        }

        $stmt = Database::connection()->prepare(
            'SELECT p.*, f.disk_path AS cover_path
             FROM products p
             LEFT JOIN files f ON f.id = p.cover_image_id
             WHERE p.slug = ? AND p.status = ? LIMIT 1'
        );
        $stmt->execute([$slug, 'published']);
        $product = $stmt->fetch();

        if (!$product) {
            Response::abort(404, t('search.product') . ' no encontrado.');
        }

        // Categories
        $cats = Database::connection()->prepare(
            "SELECT c.name, c.slug FROM product_category pc
             JOIN categories c ON c.id = pc.category_id
             WHERE pc.product_id = ? AND c.type = 'product' ORDER BY c.position ASC"
        );
        $cats->execute([(int)$product['id']]);
        $product['categories'] = $cats->fetchAll();

        // Related products (same category or latest published, excluding current)
        $related = Database::connection()->prepare(
            'SELECT p.id, p.name, p.name_en, p.slug, p.short_description, p.short_description_en, p.price, p.sale_price, p.is_featured,
                    f.disk_path AS cover_path
             FROM products p
             LEFT JOIN files f ON f.id = p.cover_image_id
             WHERE p.status = ? AND p.id != ?
             ORDER BY p.is_featured DESC, p.id DESC
             LIMIT 3'
        );
        $related->execute(['published', (int)$product['id']]);
        $relatedProducts = $related->fetchAll();

        $settings = Database::connection()
            ->query('SELECT setting_key, setting_value FROM settings')
            ->fetchAll(PDO::FETCH_KEY_PAIR);

        render('landing/product-detail', [
            'title'    => localized_value($product, 'meta_title') ?: localized_value($product, 'name'),
            'product'  => $product,
            'related'  => $relatedProducts,
            'settings' => $settings,
            'socials'  => $this->socialesActivos(),
        ], 'layouts/landing');
    }

    public function postDetail(): void
    {
        $slug = trim((string)($_GET['slug'] ?? ''));
        if ($slug === '') {
            Response::abort(404, 'Publicación no encontrada.');
        }

        $stmt = Database::connection()->prepare(
            'SELECT p.*, f.disk_path AS image_path
             FROM posts p
             LEFT JOIN files f ON f.id = p.featured_image_id
             WHERE p.slug = ? AND p.status = ? AND (p.published_at IS NULL OR p.published_at <= NOW()) LIMIT 1'
        );
        $stmt->execute([$slug, 'published']);
        $post = $stmt->fetch();

        if (!$post) {
            Response::abort(404, 'Publicación no encontrada.');
        }

        Database::connection()->prepare('UPDATE posts SET views_count = views_count + 1 WHERE id = ?')
            ->execute([(int)$post['id']]);

        $related = Database::connection()->prepare(
            'SELECT p.id, p.title, p.title_en, p.slug, p.excerpt, p.excerpt_en, p.category, f.disk_path AS image_path
             FROM posts p
             LEFT JOIN files f ON f.id = p.featured_image_id
             WHERE p.status = ? AND p.id != ? AND (p.published_at IS NULL OR p.published_at <= NOW())
             ORDER BY COALESCE(p.published_at, p.created_at) DESC
             LIMIT 3'
        );
        $related->execute(['published', (int)$post['id']]);

        $settings = Database::connection()
            ->query('SELECT setting_key, setting_value FROM settings')
            ->fetchAll(PDO::FETCH_KEY_PAIR);

        render('landing/post-detail', [
            'title'    => localized_value($post, 'meta_title') ?: localized_value($post, 'title'),
            'post'     => $post,
            'related'  => $related->fetchAll(),
            'settings' => $settings,
            'socials'  => $this->socialesActivos(),
        ], 'layouts/landing');
    }

    public function gallery(): void
    {
        $galerias = Database::connection()->query(
            "SELECT * FROM galleries WHERE is_active = 1 ORDER BY position ASC, id DESC"
        )->fetchAll();

        $ids = array_column($galerias, 'id');
        $imagenesPorAlbum = [];
        if ($ids) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = Database::connection()->prepare(
                "SELECT gi.gallery_id, gi.caption, f.disk_path, f.original_name
                 FROM gallery_images gi
                 JOIN files f ON f.id = gi.file_id
                 WHERE gi.gallery_id IN ({$placeholders})
                 ORDER BY gi.position ASC"
            );
            $stmt->execute($ids);
            foreach ($stmt->fetchAll() as $row) {
                $imagenesPorAlbum[(int)$row['gallery_id']][] = $row;
            }
        }

        $settings = Database::connection()
            ->query('SELECT setting_key, setting_value FROM settings')
            ->fetchAll(PDO::FETCH_KEY_PAIR);

        render('landing/gallery', [
            'title'    => 'Galería fotográfica',
            'galerias' => $galerias,
            'imagenesPorAlbum' => $imagenesPorAlbum,
            'settings' => $settings,
            'socials'  => $this->socialesActivos(),
        ], 'layouts/landing');
    }

    public function search(): void
    {
        $q = trim((string)($_GET['q'] ?? ''));
        if (mb_strlen($q) < 2) {
            $this->json(['items' => []]);
        }

        $like = '%' . $q . '%';
        $pdo = Database::connection();
        $lang = landing_lang();
        $productNameExpr = $lang === 'en' ? "COALESCE(NULLIF(p.name_en, ''), p.name)" : 'p.name';
        $productShortExpr = $lang === 'en' ? "COALESCE(NULLIF(p.short_description_en, ''), p.short_description)" : 'p.short_description';
        $serviceNameExpr = $lang === 'en' ? "COALESCE(NULLIF(name_en, ''), name)" : 'name';
        $serviceShortExpr = $lang === 'en' ? "COALESCE(NULLIF(short_description_en, ''), short_description)" : 'short_description';
        $postTitleExpr = $lang === 'en' ? "COALESCE(NULLIF(p.title_en, ''), p.title)" : 'p.title';
        $postExcerptExpr = $lang === 'en' ? "COALESCE(NULLIF(p.excerpt_en, ''), p.excerpt)" : 'p.excerpt';

        // Products
        $products = $pdo->prepare(
            "SELECT p.id, {$productNameExpr} AS name, p.slug, {$productShortExpr} AS short_description, p.price, p.sale_price, f.disk_path AS cover_path,
                    'producto' AS type, 'Producto' AS type_label
             FROM products p
             LEFT JOIN files f ON f.id = p.cover_image_id
             WHERE p.status = 'published' AND ({$productNameExpr} LIKE ? OR {$productShortExpr} LIKE ?)
             ORDER BY p.is_featured DESC, {$productNameExpr} ASC LIMIT 6"
        );
        $products->execute([$like, $like]);
        $productResults = $products->fetchAll();

        // Services
        $services = $pdo->prepare(
            "SELECT id, {$serviceNameExpr} AS name, slug, {$serviceShortExpr} AS short_description, 'servicio' AS type, 'Servicio' AS type_label
             FROM services WHERE is_active = 1 AND ({$serviceNameExpr} LIKE ? OR {$serviceShortExpr} LIKE ?)
             ORDER BY position ASC LIMIT 4"
        );
        $services->execute([$like, $like]);
        $serviceResults = $services->fetchAll();

        // Posts
        $posts = $pdo->prepare(
            "SELECT p.id, {$postTitleExpr} AS name, p.slug, {$postExcerptExpr} AS short_description, f.disk_path AS cover_path,
                    'publicacion' AS type, 'Publicación' AS type_label
             FROM posts p
             LEFT JOIN files f ON f.id = p.featured_image_id
             WHERE p.status = 'published' AND ({$postTitleExpr} LIKE ? OR {$postExcerptExpr} LIKE ?)
             ORDER BY COALESCE(p.published_at, p.created_at) DESC LIMIT 4"
        );
        $posts->execute([$like, $like]);
        $postResults = $posts->fetchAll();

        $items = array_merge($productResults, $serviceResults, $postResults);

        $this->json([
            'items' => array_map(function ($item) {
                $url = match ($item['type']) {
                    'producto' => lurl('/producto?slug=' . $item['slug']),
                    'servicio' => lurl('/#servicios'),
                    'publicacion' => lurl('/publicacion?slug=' . $item['slug']),
                    default => lurl('/'),
                };
                $typeLabel = match ($item['type']) {
                    'producto' => t('search.product'),
                    'servicio' => t('search.service'),
                    'publicacion' => t('search.post'),
                    default => '',
                };
                $cover = !empty($item['cover_path']) ? url('/' . $item['cover_path']) : null;
                $price = isset($item['price']) ? (float)$item['price'] : 0;
                $sale = isset($item['sale_price']) && $item['sale_price'] !== null ? (float)$item['sale_price'] : null;

                return [
                    'name' => $item['name'],
                    'excerpt' => $item['short_description'] ?? '',
                    'url' => $url,
                    'cover' => $cover,
                    'type' => $item['type'],
                    'type_label' => $typeLabel,
                    'price' => $price > 0 ? number_format($sale ?? $price, 2) : null,
                ];
            }, $items),
            'query' => $q,
        ]);
    }

    public function contact(): void
    {
        $name    = trim((string)($_POST['name'] ?? ''));
        $email   = strtolower(trim((string)($_POST['email'] ?? '')));
        $phone   = trim((string)($_POST['phone'] ?? ''));
        $subject = trim((string)($_POST['subject'] ?? ''));
        $message = trim((string)($_POST['message'] ?? ''));

        $errors = [];
        if ($name === '') $errors[] = t('contact.required_name');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = t('contact.invalid_email');
        if ($message === '') $errors[] = t('contact.required_message');

        if ($errors) {
            back_with_errors($errors, $_POST);
        }

        $pdo = Database::connection();
        $pdo->prepare(
            'INSERT INTO contact_messages (name, email, phone, subject, message, ip) VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$name, $email, $phone ?: null, $subject ?: null, $message, $_SERVER['REMOTE_ADDR'] ?? null]);

        // El mensaje ya quedo guardado arriba; si la notificacion por correo
        // falla, no se pierde nada y quedara marcado para reintentarse despues.
        ContactNotifier::intentarEnviar([
            'id' => (int)$pdo->lastInsertId(),
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'subject' => $subject,
            'message' => $message,
        ]);

        flash('status', t('contact.success'));
        Response::redirect(lurl('/#contacto'));
    }

    /** Redes sociales activas, para el pie de pagina de cualquier vista publica. */
    private function socialesActivos(): array
    {
        return Database::connection()
            ->query("SELECT * FROM social_networks WHERE is_active = 1 ORDER BY position ASC")
            ->fetchAll();
    }

    private function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
