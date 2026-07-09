<?php
$modules = [
    ['perm' => 'posts',      'url' => '/posts',      'icon' => 'edit',          'color' => 'noticias',   'label' => 'Noticias',   'hint' => 'Registradas',     'count' => $stats['noticias'] ?? 0],
    ['perm' => 'products',   'url' => '/products',   'icon' => 'package',       'color' => 'productos',  'label' => 'Productos',  'hint' => 'En catalogo',     'count' => $stats['productos'] ?? 0],
    ['perm' => 'orders',     'url' => '/orders',     'icon' => 'clipboard-list','color' => 'pedidos',    'label' => 'Pedidos',    'hint' => 'Por revisar',     'count' => $stats['pedidos'] ?? 0],
    ['perm' => 'sales',      'url' => '/sales',      'icon' => 'shopping-bag',  'color' => 'ventas',     'label' => 'Ventas',     'hint' => 'Este mes',        'count' => $stats['ventas'] ?? 0],
    ['perm' => 'inventory',  'url' => '/inventory',  'icon' => 'boxes',         'color' => 'inventario', 'label' => 'Inventario', 'hint' => 'Con stock bajo',  'count' => $stats['inventario'] ?? 0],
    ['perm' => 'services',   'url' => '/services',   'icon' => 'layers',        'color' => 'servicios',  'label' => 'Servicios',  'hint' => 'Del landing',     'count' => $stats['servicios'] ?? 0],
    ['perm' => 'contacts',   'url' => '/contacts',   'icon' => 'mail',          'color' => 'contactos',  'label' => 'Mensajes',   'hint' => 'Sin atender',     'count' => $stats['contactos'] ?? 0],
    ['perm' => 'files',      'url' => '/media',      'icon' => 'image',         'color' => 'media',      'label' => 'Media',      'hint' => 'Archivos',        'count' => $stats['media'] ?? 0],
    ['perm' => 'users',      'url' => '/users',      'icon' => 'users',         'color' => 'usuarios',   'label' => 'Usuarios',   'hint' => 'Del sistema',     'count' => $stats['usuarios'] ?? 0],
];
$modules = array_values(array_filter($modules, fn ($module) => can($module['perm'])));

$quickLinks = [
    ['perm' => 'posts',           'url' => '/posts/create',       'icon' => 'edit',     'label' => 'Nueva noticia', 'hint' => 'Publicar contenido', 'color' => 'noticias'],
    ['perm' => 'products',        'url' => '/products',           'icon' => 'package',  'label' => 'Productos', 'hint' => 'Catalogo comercial', 'color' => 'productos'],
    ['perm' => 'orders',          'url' => '/orders',             'icon' => 'clipboard-list', 'label' => 'Pedidos', 'hint' => 'Por revisar', 'color' => 'pedidos'],
    ['perm' => 'sales',           'url' => '/sales',              'icon' => 'shopping-bag', 'label' => 'Ventas', 'hint' => 'Registro comercial', 'color' => 'ventas'],
    ['perm' => 'inventory',       'url' => '/inventory',          'icon' => 'boxes',    'label' => 'Inventario', 'hint' => 'Stock de productos', 'color' => 'inventario'],
    ['perm' => 'services',        'url' => '/services',           'icon' => 'layers',   'label' => 'Servicios', 'hint' => 'Landing page', 'color' => 'servicios'],
    ['perm' => 'galleries',       'url' => '/galleries',          'icon' => 'image',    'label' => 'Galeria', 'hint' => 'Imagenes publicas', 'color' => 'galeria'],
    ['perm' => 'social_networks', 'url' => '/social-networks',    'icon' => 'share',    'label' => 'Redes sociales', 'hint' => 'Canales activos', 'color' => 'redes'],
    ['perm' => 'contacts',        'url' => '/contacts',           'icon' => 'mail',     'label' => 'Contactenos', 'hint' => 'Mensajes recibidos', 'color' => 'contactos'],
    ['perm' => 'files',           'url' => '/media',              'icon' => 'image',    'label' => 'Media', 'hint' => 'Biblioteca', 'color' => 'media'],
    ['perm' => 'pages',           'url' => '/about',              'icon' => 'layout',   'label' => 'Nosotros', 'hint' => 'Datos institucionales', 'color' => 'nosotros'],
    ['perm' => 'users',           'url' => '/users',              'icon' => 'users',    'label' => 'Usuarios', 'hint' => 'Accesos del sistema', 'color' => 'usuarios'],
    ['perm' => 'settings',        'url' => '/settings',           'icon' => 'settings', 'label' => 'Configuracion', 'hint' => 'Parametros generales', 'color' => 'config'],
];
$quickLinks = array_values(array_filter($quickLinks, fn ($link) => can($link['perm'])));
$visibleLatestContents = array_values(array_filter($latestContents ?? [], fn ($item) => can($item['permission'])));

// En escritorio, "Accesos rapidos" solo debe listar modulos que NO ya tienen su propia
// tarjeta KPI arriba, para no duplicar el mismo acceso dos veces en la misma pantalla.
// En movil no aplica: ahi el panel rapido es la unica navegacion (no hay tarjetas KPI).
$kpiPerms = array_column($modules, 'perm');
$desktopQuickLinks = array_values(array_filter($quickLinks, fn ($link) => !in_array($link['perm'], $kpiPerms, true)));

$orderStatusLabels = [
    'pendiente' => 'Pendiente',
    'voucher_enviado' => 'Voucher enviado',
    'en_revision' => 'En revision',
    'aprobado' => 'Aprobado',
    'rechazado' => 'Rechazado',
    'cancelado' => 'Cancelado',
];
$maxOrderStatus = max(1, ...array_map(fn ($k) => (int)($portal['orders'][$k] ?? 0), array_keys($orderStatusLabels)));
$maxTopProduct = max(1, ...array_map(fn ($p) => (int)$p['qty'], $topProducts ?: [['qty' => 0]]));
$maxPaymentMethod = max(1, ...array_map(fn ($p) => (int)$p['total'], $paymentMethodsBreakdown ?: [['total' => 0]]));

$maxActivity = max(1, ...array_map(fn ($point) => (int)$point['total'], $activityChart ?? []));

$bytes = function (int $value): string {
    if ($value >= 1073741824) return number_format($value / 1073741824, 1) . ' GB';
    if ($value >= 1048576) return number_format($value / 1048576, 1) . ' MB';
    if ($value >= 1024) return number_format($value / 1024, 1) . ' KB';
    return $value . ' B';
};

$statusLabel = function (?string $status): string {
    return match ($status) {
        'published' => 'Publicado',
        'draft' => 'Borrador',
        'scheduled' => 'Programado',
        'active' => 'Activo',
        'inactive' => 'Inactivo',
        'new' => 'Nuevo',
        'read' => 'Leido',
        'answered' => 'Respondido',
        'archived' => 'Archivado',
        'pendiente' => 'Pendiente',
        'voucher_enviado' => 'Voucher enviado',
        'en_revision' => 'En revision',
        'aprobado' => 'Aprobado',
        'rechazado' => 'Rechazado',
        'cancelado' => 'Cancelado',
        'confirmada' => 'Confirmada',
        'anulada' => 'Anulada',
        'entregada' => 'Entregada',
        default => $status ? (str_starts_with($status, 'image/') ? 'Imagen' : $status) : 'Sin estado',
    };
};

$statusClass = function (?string $status): string {
    if (in_array($status, ['published', 'active', 'answered', 'aprobado', 'confirmada', 'entregada'], true)) return 'ok';
    if (in_array($status, ['rechazado', 'cancelado', 'anulada'], true)) return 'off';
    if (in_array($status, ['en_revision', 'voucher_enviado', 'pendiente'], true)) return 'warn';
    return 'off';
};
?>

<section class="dashboard-desktop">
    <div class="dashboard-hero">
        <div>
            <span class="eyebrow">Panel administrativo</span>
            <h2>Estado general del portal</h2>
            <p>Resumen operativo construido con los contenidos reales registrados en el sistema.</p>
        </div>
        <a class="button" href="<?= e(url('/')) ?>" target="_blank" rel="noopener"><?= icon('share') ?> Ver sitio web</a>
    </div>

    <div class="dashboard-kpi-grid">
        <?php foreach ($modules as $module): ?>
            <a class="dashboard-kpi stat-color-<?= e($module['color']) ?>" href="<?= e(url($module['url'])) ?>">
                <span class="dashboard-kpi-icon"><?= icon($module['icon']) ?></span>
                <span>
                    <strong><?= e($module['count']) ?></strong>
                    <em><?= e($module['label']) ?></em>
                    <small><?= e($module['hint']) ?></small>
                </span>
                <?= icon('chevron-right', 'dashboard-kpi-arrow') ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="dashboard-main-grid">
        <section class="dashboard-panel dashboard-chart-panel">
            <div class="dashboard-panel-head">
                <div>
                    <h3>Actividad de contenidos</h3>
                    <span>Registros creados durante los ultimos 14 dias.</span>
                </div>
                <?= icon('bar-chart') ?>
            </div>
            <div class="dashboard-chart" aria-label="Actividad de contenidos">
                <?php foreach (($activityChart ?? []) as $point):
                    $height = max(8, round(((int)$point['total'] / $maxActivity) * 100));
                ?>
                    <div class="dashboard-chart-column">
                        <strong><?= e($point['total']) ?></strong>
                        <span style="height: <?= e($height) ?>%"></span>
                        <em><?= e($point['label']) ?></em>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="dashboard-panel">
            <div class="dashboard-panel-head">
                <div>
                    <h3>Resumen del portal</h3>
                    <span>Publicacion, atencion y visibilidad.</span>
                </div>
                <?= icon('activity') ?>
            </div>
            <div class="dashboard-status-list">
                <div>
                    <span>Noticias publicadas</span>
                    <strong><?= e($portal['posts']['published'] ?? 0) ?></strong>
                    <small><?= e($portal['posts']['draft'] ?? 0) ?> borradores, <?= e($portal['posts']['scheduled'] ?? 0) ?> programadas</small>
                </div>
                <div>
                    <span>Productos publicados</span>
                    <strong><?= e($portal['products']['published'] ?? 0) ?></strong>
                    <small><?= e($portal['products']['draft'] ?? 0) ?> borradores</small>
                </div>
                <div>
                    <span>Pedidos por revisar</span>
                    <strong><?= e(($portal['orders']['voucher_enviado'] ?? 0) + ($portal['orders']['en_revision'] ?? 0) + ($portal['orders']['pendiente'] ?? 0)) ?></strong>
                    <small><?= e($portal['orders']['aprobado'] ?? 0) ?> aprobados, <?= e($portal['orders']['rechazado'] ?? 0) ?> rechazados</small>
                </div>
                <div>
                    <span>Ventas confirmadas</span>
                    <strong>S/ <?= e(number_format((float)($portal['sales']['revenue_month'] ?? 0), 2)) ?></strong>
                    <small><?= e($portal['sales']['confirmed_month'] ?? 0) ?> ventas este mes</small>
                </div>
                <div>
                    <span>Stock bajo o agotado</span>
                    <strong><?= e($portal['inventory']['low_stock'] ?? 0) ?></strong>
                    <small><?= e($portal['inventory']['out_of_stock'] ?? 0) ?> sin stock</small>
                </div>
                <div>
                    <span>Servicios activos</span>
                    <strong><?= e($portal['services']['active'] ?? 0) ?></strong>
                    <small><?= e($portal['services']['inactive'] ?? 0) ?> inactivos</small>
                </div>
                <div>
                    <span>Mensajes nuevos</span>
                    <strong><?= e($portal['contacts']['new'] ?? 0) ?></strong>
                    <small><?= e($portal['contacts']['answered'] ?? 0) ?> respondidos</small>
                </div>
                <div>
                    <span>Media almacenada</span>
                    <strong><?= e($bytes((int)($portal['media_size'] ?? 0))) ?></strong>
                    <small><?= e($stats['media'] ?? 0) ?> archivos registrados</small>
                </div>
                <div>
                    <span>Vistas ultimos 30 dias</span>
                    <strong><?= e($portal['page_views_30'] ?? 0) ?></strong>
                    <small>Segun registros del portal</small>
                </div>
            </div>
        </section>
    </div>

    <?php if (can('orders') || can('sales')): ?>
    <div class="dashboard-charts-grid">
        <?php if (can('orders')): ?>
        <section class="dashboard-panel">
            <div class="dashboard-panel-head">
                <div>
                    <h3>Pedidos por estado</h3>
                    <span>Distribucion actual de todos los pedidos.</span>
                </div>
                <?= icon('clipboard-list') ?>
            </div>
            <div class="dashboard-bar-list">
                <?php foreach ($orderStatusLabels as $key => $label):
                    $count = (int)($portal['orders'][$key] ?? 0);
                    $width = max(3, round(($count / $maxOrderStatus) * 100));
                ?>
                <div class="dashboard-bar-row">
                    <span class="bar-label"><?= e($label) ?></span>
                    <span class="dashboard-bar-track"><span class="dashboard-bar-fill <?= e($statusClass($key)) ?>" style="width: <?= e($width) ?>%"></span></span>
                    <strong class="bar-value"><?= e($count) ?></strong>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if (can('sales')): ?>
        <section class="dashboard-panel">
            <div class="dashboard-panel-head">
                <div>
                    <h3>Productos mas vendidos</h3>
                    <span>Unidades en ventas confirmadas.</span>
                </div>
                <?= icon('package') ?>
            </div>
            <div class="dashboard-bar-list">
                <?php if ($topProducts): ?>
                    <?php foreach ($topProducts as $product):
                        $qty = (int)$product['qty'];
                        $width = max(3, round(($qty / $maxTopProduct) * 100));
                    ?>
                    <div class="dashboard-bar-row">
                        <span class="bar-label" title="<?= e($product['product_name']) ?>"><?= e($product['product_name']) ?></span>
                        <span class="dashboard-bar-track"><span class="dashboard-bar-fill accent" style="width: <?= e($width) ?>%"></span></span>
                        <strong class="bar-value"><?= e($qty) ?></strong>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">Aun no hay ventas confirmadas con productos.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="dashboard-panel">
            <div class="dashboard-panel-head">
                <div>
                    <h3>Metodos de pago</h3>
                    <span>Ventas confirmadas por metodo.</span>
                </div>
                <?= icon('credit-card') ?>
            </div>
            <div class="dashboard-bar-list">
                <?php if ($paymentMethodsBreakdown): ?>
                    <?php foreach ($paymentMethodsBreakdown as $method):
                        $total = (int)$method['total'];
                        $width = max(3, round(($total / $maxPaymentMethod) * 100));
                    ?>
                    <div class="dashboard-bar-row">
                        <span class="bar-label"><?= e($method['payment_method']) ?></span>
                        <span class="dashboard-bar-track"><span class="dashboard-bar-fill accent" style="width: <?= e($width) ?>%"></span></span>
                        <strong class="bar-value"><?= e($total) ?></strong>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">Aun no hay ventas confirmadas.</p>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="dashboard-bottom-grid">
        <section class="dashboard-panel">
            <div class="dashboard-panel-head">
                <div>
                    <h3>Ultimos contenidos</h3>
                    <span>Registros creados o actualizados recientemente.</span>
                </div>
                <?= icon('list') ?>
            </div>
            <div class="table-wrap dashboard-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Modulo</th>
                            <th>Contenido</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($visibleLatestContents as $item): ?>
                            <tr>
                                <td data-label="Modulo">
                                    <span class="dashboard-module-label"><?= icon($item['icon']) ?> <?= e($item['module']) ?></span>
                                </td>
                                <td data-label="Contenido">
                                    <strong class="table-title"><?= e($item['name']) ?></strong>
                                </td>
                                <td data-label="Estado">
                                    <span class="badge <?= e($statusClass($item['status'])) ?>"><?= e($statusLabel($item['status'])) ?></span>
                                </td>
                                <td data-label="Fecha"><?= e(date('d/m/Y H:i', strtotime((string)$item['changed_at']))) ?></td>
                                <td class="actions">
                                    <a class="button small" href="<?= e(url($item['url'])) ?>">Abrir</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($visibleLatestContents)): ?>
                            <tr><td colspan="5" class="empty-state">Sin contenidos registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="dashboard-panel">
            <div class="dashboard-panel-head">
                <div>
                    <h3>Accesos rapidos</h3>
                    <span>Otros modulos, sin tarjeta propia arriba.</span>
                </div>
                <?= icon('chevron-right') ?>
            </div>
            <div class="dashboard-quick-grid">
                <?php foreach ($desktopQuickLinks as $link): ?>
                    <a class="dashboard-quick-link" href="<?= e(url($link['url'])) ?>">
                        <?= icon($link['icon']) ?>
                        <span>
                            <strong><?= e($link['label']) ?></strong>
                            <small><?= e($link['hint']) ?></small>
                        </span>
                    </a>
                <?php endforeach; ?>
                <?php if (!$desktopQuickLinks): ?>
                    <p class="text-muted">Todos los modulos disponibles ya tienen tarjeta arriba.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
</section>

<section class="dashboard-mobile">
    <div class="dashboard-mobile-head">
        <span class="eyebrow">Panel rapido</span>
        <h2>Inicio</h2>
        <p>Accesos principales para administrar el portal desde el movil.</p>
    </div>

    <div class="dashboard-mobile-grid">
        <?php foreach ($quickLinks as $link): ?>
            <a class="dashboard-mobile-card qc-<?= e($link['color']) ?>" href="<?= e(url($link['url'])) ?>">
                <span><?= icon($link['icon']) ?></span>
                <strong><?= e($link['label']) ?></strong>
                <small><?= e($link['hint']) ?></small>
            </a>
        <?php endforeach; ?>
    </div>
</section>
