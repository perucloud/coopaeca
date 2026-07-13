<?php

final class SitemapController extends Controller
{
    public function xml(): void
    {
        $db = Database::connection();

        $products = $db->query(
            "SELECT slug, updated_at FROM products WHERE status = 'published' ORDER BY id DESC"
        )->fetchAll();

        $posts = $db->query(
            "SELECT slug, updated_at FROM posts
             WHERE status = 'published' AND (published_at IS NULL OR published_at <= NOW())
             ORDER BY id DESC"
        )->fetchAll();

        $urls = [
            ['loc' => absolute_url('/'), 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['loc' => absolute_url('/nosotros'), 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => absolute_url('/galeria'), 'changefreq' => 'monthly', 'priority' => '0.5'],
        ];

        foreach ($products as $product) {
            $urls[] = [
                'loc' => absolute_url('/producto?slug=' . rawurlencode((string)$product['slug'])),
                'lastmod' => self::formatDate($product['updated_at']),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        foreach ($posts as $post) {
            $urls[] = [
                'loc' => absolute_url('/publicacion?slug=' . rawurlencode((string)$post['slug'])),
                'lastmod' => self::formatDate($post['updated_at']),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
        }

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $url) {
            echo '  <url>' . "\n";
            echo '    <loc>' . e($url['loc']) . '</loc>' . "\n";
            if (!empty($url['lastmod'])) {
                echo '    <lastmod>' . e($url['lastmod']) . '</lastmod>' . "\n";
            }
            echo '    <changefreq>' . e($url['changefreq']) . '</changefreq>' . "\n";
            echo '    <priority>' . e($url['priority']) . '</priority>' . "\n";
            echo '  </url>' . "\n";
        }
        echo '</urlset>';
    }

    private static function formatDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }
        $timestamp = strtotime($value);
        return $timestamp ? date('Y-m-d', $timestamp) : null;
    }
}
