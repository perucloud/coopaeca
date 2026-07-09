<?php

final class DashboardController extends Controller
{
    public function index(): void
    {
        $pdo = Database::connection();

        $stats = [
            'noticias' => (int)$pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn(),
            'productos' => (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
            'servicios' => (int)$pdo->query('SELECT COUNT(*) FROM services')->fetchColumn(),
            'contactos' => (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn(),
            'media' => (int)$pdo->query('SELECT COUNT(*) FROM files')->fetchColumn(),
            'usuarios' => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        ];

        $portal = [
            'posts' => $this->groupCount($pdo, 'SELECT status, COUNT(*) total FROM posts GROUP BY status', 'status'),
            'products' => $this->groupCount($pdo, 'SELECT status, COUNT(*) total FROM products GROUP BY status', 'status'),
            'services' => [
                'active' => (int)$pdo->query('SELECT COUNT(*) FROM services WHERE is_active = 1')->fetchColumn(),
                'inactive' => (int)$pdo->query('SELECT COUNT(*) FROM services WHERE is_active = 0')->fetchColumn(),
            ],
            'contacts' => $this->groupCount($pdo, 'SELECT status, COUNT(*) total FROM contact_messages GROUP BY status', 'status'),
            'media_size' => (int)$pdo->query('SELECT COALESCE(SUM(size_bytes), 0) FROM files')->fetchColumn(),
            'page_views_30' => (int)$pdo->query('SELECT COUNT(*) FROM page_views WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)')->fetchColumn(),
        ];

        render('dashboard/index', [
            'title' => 'Dashboard',
            'stats' => $stats,
            'portal' => $portal,
            'activityChart' => $this->activityChart($pdo),
            'latestContents' => $this->latestContents($pdo),
        ]);
    }

    private function groupCount(PDO $pdo, string $sql, string $keyColumn): array
    {
        $rows = $pdo->query($sql)->fetchAll();
        $result = [];
        foreach ($rows as $row) {
            $result[(string)$row[$keyColumn]] = (int)$row['total'];
        }
        return $result;
    }

    private function activityChart(PDO $pdo): array
    {
        $days = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime('-' . $i . ' days'));
            $days[$date] = [
                'date' => $date,
                'label' => date('d/m', strtotime($date)),
                'total' => 0,
            ];
        }

        $sources = [
            'posts',
            'products',
            'services',
            'files',
            'galleries',
        ];

        foreach ($sources as $table) {
            $rows = $pdo->query(
                "SELECT DATE(created_at) day, COUNT(*) total
                 FROM {$table}
                 WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
                 GROUP BY DATE(created_at)"
            )->fetchAll();

            foreach ($rows as $row) {
                $day = (string)$row['day'];
                if (isset($days[$day])) {
                    $days[$day]['total'] += (int)$row['total'];
                }
            }
        }

        return array_values($days);
    }

    private function latestContents(PDO $pdo): array
    {
        $items = [];

        $queries = [
            'posts' => [
                'module' => 'Noticias',
                'permission' => 'posts',
                'icon' => 'edit',
                'url' => '/posts/edit?id=',
                'sql' => "SELECT id, title AS name, status, COALESCE(updated_at, created_at) AS changed_at FROM posts ORDER BY changed_at DESC LIMIT 8",
            ],
            'products' => [
                'module' => 'Productos',
                'permission' => 'products',
                'icon' => 'package',
                'url' => '/products/edit?id=',
                'sql' => "SELECT id, name, status, COALESCE(updated_at, created_at) AS changed_at FROM products ORDER BY changed_at DESC LIMIT 8",
            ],
            'services' => [
                'module' => 'Servicios',
                'permission' => 'services',
                'icon' => 'layers',
                'url' => '/services/edit?id=',
                'sql' => "SELECT id, name, IF(is_active = 1, 'active', 'inactive') AS status, COALESCE(updated_at, created_at) AS changed_at FROM services ORDER BY changed_at DESC LIMIT 8",
            ],
            'files' => [
                'module' => 'Media',
                'permission' => 'files',
                'icon' => 'image',
                'url' => '/media',
                'sql' => "SELECT id, original_name AS name, mime_type AS status, created_at AS changed_at FROM files ORDER BY created_at DESC LIMIT 8",
            ],
            'galleries' => [
                'module' => 'Galeria',
                'permission' => 'galleries',
                'icon' => 'image',
                'url' => '/galleries/edit?id=',
                'sql' => "SELECT id, title AS name, IF(is_active = 1, 'active', 'inactive') AS status, created_at AS changed_at FROM galleries ORDER BY created_at DESC LIMIT 8",
            ],
        ];

        foreach ($queries as $query) {
            foreach ($pdo->query($query['sql'])->fetchAll() as $row) {
                $items[] = [
                    'module' => $query['module'],
                    'permission' => $query['permission'],
                    'icon' => $query['icon'],
                    'url' => $query['url'] === '/media' ? $query['url'] : $query['url'] . $row['id'],
                    'name' => $row['name'],
                    'status' => $row['status'],
                    'changed_at' => $row['changed_at'],
                ];
            }
        }

        usort($items, fn ($a, $b) => strtotime((string)$b['changed_at']) <=> strtotime((string)$a['changed_at']));

        return array_slice($items, 0, 10);
    }
}
