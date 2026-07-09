<!doctype html>
<html lang="es" data-theme="">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title ?? dashboard_name()) ?> - <?= e(dashboard_name()) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>

<div class="app-shell" id="appShell">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="brand">
                <div class="brand-mark"><?= icon('layers') ?></div>
                <div class="brand-text">
                    <strong><?= e(dashboard_name()) ?></strong>
                    <span><?= e(user()['role_name'] ?? '') ?></span>
                </div>
            </div>
            <button class="sidebar-collapse-btn" id="collapseBtn" title="Colapsar menú">
                <?= icon('chevron-right') ?>
            </button>
        </div>

        <nav class="nav" id="sidebarNav">
            <span class="nav-section">Principal</span>

            <a href="<?= e(url('/dashboard')) ?>" class="nav-link <?= is_active('/dashboard', '/') ?>">
                <?= icon('home') ?><span>Dashboard</span>
            </a>

            <?php if (can('posts')): ?>
            <div class="nav-group <?= is_active('/posts') ? 'open' : '' ?>">
                <button type="button" class="nav-link nav-group-toggle" data-submenu-toggle>
                    <?= icon('edit') ?><span>Nueva noticia</span><?= icon('chevron-down', 'nav-chevron') ?>
                </button>
                <div class="nav-submenu">
                    <a href="<?= e(url('/posts/create')) ?>" class="<?= is_active('/posts/create') ?>"><?= icon('edit') ?><span>Crear noticia</span></a>
                    <a href="<?= e(url('/posts')) ?>" class="<?= is_active('/posts') ?>"><?= icon('list') ?><span>Publicaciones</span></a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (can('products')): ?>
            <a href="<?= e(url('/products')) ?>" class="nav-link <?= is_active('/products') ?>">
                <?= icon('package') ?><span>Productos</span>
            </a>
            <?php endif; ?>

            <?php if (can('orders')): ?>
            <a href="<?= e(url('/orders')) ?>" class="nav-link <?= is_active('/orders') ?>">
                <?= icon('clipboard-list') ?><span>Pedidos</span>
            </a>
            <?php endif; ?>

            <?php if (can('sales')): ?>
            <a href="<?= e(url('/sales')) ?>" class="nav-link <?= is_active('/sales') ?>">
                <?= icon('shopping-bag') ?><span>Ventas</span>
            </a>
            <?php endif; ?>

            <?php if (can('inventory')): ?>
            <a href="<?= e(url('/inventory')) ?>" class="nav-link <?= is_active('/inventory') ?>">
                <?= icon('boxes') ?><span>Inventario</span>
            </a>
            <?php endif; ?>

            <?php if (can('services')): ?>
            <a href="<?= e(url('/services')) ?>" class="nav-link <?= is_active('/services') ?>">
                <?= icon('layers') ?><span>Servicios</span>
            </a>
            <?php endif; ?>

            <?php if (can('galleries')): ?>
            <a href="<?= e(url('/galleries')) ?>" class="nav-link <?= is_active('/galleries') ?>">
                <?= icon('image') ?><span>Galería</span>
            </a>
            <?php endif; ?>

            <?php if (can('social_networks')): ?>
            <a href="<?= e(url('/social-networks')) ?>" class="nav-link <?= is_active('/social-networks') ?>">
                <?= icon('share') ?><span>Redes sociales</span>
            </a>
            <?php endif; ?>

            <?php if (can('pages')): ?>
            <a href="<?= e(url('/about')) ?>" class="nav-link <?= is_active('/about') ?>">
                <?= icon('users') ?><span>Nosotros</span>
            </a>
            <?php endif; ?>

            <?php if (can('contacts')): ?>
            <?php $unread = (int)Database::connection()->query("SELECT COUNT(*) FROM contact_messages WHERE status='new'")->fetchColumn(); ?>
            <a href="<?= e(url('/contacts')) ?>" class="nav-link <?= is_active('/contacts') ?>">
                <?= icon('mail') ?><span>Contáctenos</span>
                <?php if ($unread > 0): ?><em class="nav-badge"><?= $unread ?></em><?php endif; ?>
            </a>
            <?php endif; ?>

            <?php if (can('files')): ?>
            <a href="<?= e(url('/media')) ?>" class="nav-link <?= is_active('/media') ?>">
                <?= icon('image') ?><span>Media</span>
            </a>
            <?php endif; ?>

            <?php /* Modulo Correo oculto: se movera a una app de correo independiente, reutilizable para otros proyectos.
                     El codigo, tablas y rutas de app/Controllers/Mail siguen intactos por si se reutilizan. */ ?>

            <a href="<?= e(url('/')) ?>" class="nav-link nav-web-link" target="_blank" rel="noopener">
                <?= icon('share') ?><span>Ver pagina web</span>
            </a>

            <span class="nav-section">Administración</span>

            <?php if (can('users') || can('settings') || can('payment_methods') || is_superadmin()): ?>
            <div class="nav-group <?= is_active('/users', '/settings', '/payment-methods', '/profile') ? 'open' : '' ?>">
                <button type="button" class="nav-link nav-group-toggle" data-submenu-toggle>
                    <?= icon('shield') ?><span>Sistema</span><?= icon('chevron-down', 'nav-chevron') ?>
                </button>
                <div class="nav-submenu">
                    <?php if (can('users')): ?><a href="<?= e(url('/users')) ?>" class="<?= is_active('/users') ?>"><?= icon('users') ?><span>Usuarios</span></a><?php endif; ?>
                    <?php if (can('settings')): ?><a href="<?= e(url('/settings')) ?>" class="<?= is_active('/settings') ?>"><?= icon('settings') ?><span>Configuración</span></a><?php endif; ?>
                    <?php if (can('payment_methods')): ?><a href="<?= e(url('/payment-methods')) ?>" class="<?= is_active('/payment-methods') ?>"><?= icon('credit-card') ?><span>Metodos de pago</span></a><?php endif; ?>
                    <a href="<?= e(url('/profile')) ?>" class="<?= is_active('/profile') ?>"><?= icon('user') ?><span>Mi perfil</span></a>
                </div>
            </div>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <a href="<?= e(url('/profile')) ?>" class="sidebar-user">
                <div class="sidebar-avatar"><?= strtoupper(substr(user()['name'] ?? 'U', 0, 1)) ?></div>
                <div class="sidebar-user-info">
                    <strong><?= e(user()['name'] ?? '') ?></strong>
                    <span><?= e(user()['role_name'] ?? '') ?></span>
                </div>
            </a>
        </div>
    </aside>

    <div class="sidebar-backdrop" data-toggle-sidebar></div>

    <div class="main" id="mainContent">
        <header class="topbar">
            <button class="icon-button" id="mobileMenuBtn" data-toggle-sidebar aria-label="Menú"><?= icon('menu') ?></button>

            <nav class="breadcrumb-nav" aria-label="breadcrumb">
                <?php
                $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
                $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
                if (str_ends_with(str_replace('\\', '/', $base), '/public')) {
                    $base = substr($base, 0, -7) ?: '';
                }
                $rel = $base !== '' ? ltrim(substr($uri, strlen($base)), '/') : ltrim($uri, '/');
                $segs = array_values(array_filter(explode('/', $rel)));
                $map = [
                    'dashboard' => 'Dashboard',
                    'posts' => 'Noticias',
                    'products' => 'Productos',
                    'orders' => 'Pedidos',
                    'sales' => 'Ventas',
                    'inventory' => 'Inventario',
                    'movements' => 'Movimientos',
                    'payment-methods' => 'Metodos de pago',
                    'services' => 'Servicios',
                    'galleries' => 'Galería',
                    'social-networks' => 'Redes sociales',
                    'about' => 'Nosotros',
                    'contacts' => 'Contáctenos',
                    'media' => 'Media',
                    'mail' => 'Correo',
                    'read' => 'Leer',
                    'accounts' => 'Cuentas',
                    'users' => 'Usuarios',
                    'settings' => 'Configuración',
                    'profile' => 'Mi perfil',
                    'create' => 'Nuevo',
                    'edit' => 'Editar',
                ];
                echo '<a href="' . e(url('/dashboard')) . '" class="bc-item">Inicio</a>';
                foreach ($segs as $seg) {
                    $label = $map[$seg] ?? ucfirst($seg);
                    echo '<span class="bc-sep">' . icon('chevron-right') . '</span>';
                    echo '<span class="bc-item bc-active">' . e($label) . '</span>';
                }
                ?>
            </nav>

            <div class="topbar-actions">
                <a class="topbar-link" href="<?= e(url('/')) ?>" target="_blank" rel="noopener"><?= icon('share') ?><span>Ver sitio</span></a>

                <button class="topbar-btn" id="searchTrigger" title="Buscar (Ctrl+K)">
                    <?= icon('search') ?>
                </button>

                <div class="dropdown" id="notifDropdown">
                    <button class="topbar-btn notif-btn" data-dropdown="notif" title="Notificaciones">
                        <?= icon('bell') ?>
                        <?php
                        $nCount = (int)Database::connection()->query("SELECT COUNT(*) FROM contact_messages WHERE status='new'")->fetchColumn();
                        if ($nCount > 0): ?>
                        <span class="notif-dot"><?= $nCount ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-panel dropdown-right" id="notifPanel">
                        <div class="dropdown-header">
                            <strong>Notificaciones</strong>
                            <?php if ($nCount > 0): ?><span class="badge-count"><?= $nCount ?> nuevas</span><?php endif; ?>
                        </div>
                        <?php if ($nCount > 0): ?>
                        <a class="dropdown-item" href="<?= e(url('/contacts')) ?>">
                            <?= icon('mail') ?>
                            <div><strong><?= $nCount ?> mensaje<?= $nCount > 1 ? 's' : '' ?> nuevo<?= $nCount > 1 ? 's' : '' ?></strong><span>Sin atender</span></div>
                        </a>
                        <?php else: ?>
                        <div class="dropdown-empty"><?= icon('check-circle') ?> Sin notificaciones</div>
                        <?php endif; ?>
                    </div>
                </div>

                <button class="topbar-btn" id="themeToggle" title="Modo oscuro">
                    <span id="iconDark"><?= icon('moon') ?></span>
                    <span id="iconLight" style="display:none"><?= icon('sun') ?></span>
                </button>

                <div class="dropdown" id="userDropdown">
                    <button class="user-btn" data-dropdown="userMenu">
                        <div class="topbar-avatar"><?= strtoupper(substr(user()['name'] ?? 'U', 0, 1)) ?></div>
                        <span class="user-name"><?= e(user()['name'] ?? '') ?></span>
                        <?= icon('chevron-down', 'user-chevron') ?>
                    </button>
                    <div class="dropdown-panel dropdown-right" id="userMenu">
                        <div class="dropdown-header">
                            <strong><?= e(user()['name'] ?? '') ?></strong>
                            <span><?= e(user()['email'] ?? '') ?></span>
                        </div>
                        <a href="<?= e(url('/profile')) ?>" class="dropdown-item"><?= icon('user') ?><span>Mi perfil</span></a>
                        <?php if (can('settings')): ?>
                        <a href="<?= e(url('/settings')) ?>" class="dropdown-item"><?= icon('settings') ?><span>Configuración</span></a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <form method="post" action="<?= e(url('/logout')) ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="dropdown-item dropdown-item-danger"><?= icon('logout') ?><span>Cerrar sesión</span></button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="content">
            <?php if ($message = flash('status')): ?>
                <div class="alert success"><?= icon('check-circle') ?> <?= e($message) ?></div>
            <?php endif; ?>
            <?php $errors = errors(); if ($errors): ?>
                <div class="alert error">
                    <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?= $content ?>
        </main>
    </div>
</div>

<div class="search-overlay" id="searchOverlay">
    <div class="search-modal">
        <div class="search-input-wrap">
            <?= icon('search', 'search-modal-icon') ?>
            <input type="text" id="searchInput" placeholder="Buscar usuarios, noticias, productos..." autocomplete="off">
            <kbd>Esc</kbd>
        </div>
        <div class="search-results" id="searchResults">
            <div class="search-tip">Escribe para buscar en todos los módulos.</div>
        </div>
    </div>
</div>

<?php
// Barra de navegacion inferior (solo movil): Dashboard + hasta 2 modulos
// segun los permisos del usuario + boton de Menu que abre el cajon completo.
$mobileNavCandidatos = [
    ['posts', '/posts', 'edit', 'Noticias'],
    ['products', '/products', 'package', 'Productos'],
    ['orders', '/orders', 'clipboard-list', 'Pedidos'],
    ['sales', '/sales', 'shopping-bag', 'Ventas'],
    ['inventory', '/inventory', 'boxes', 'Stock'],
    ['services', '/services', 'layers', 'Servicios'],
    ['galleries', '/galleries', 'image', 'Galería'],
    ['social_networks', '/social-networks', 'share', 'Redes'],
    ['users', '/users', 'users', 'Usuarios'],
];
$mobileNavItems = [];
foreach ($mobileNavCandidatos as [$mod, $path, $ic, $label]) {
    if (can($mod)) {
        $mobileNavItems[] = [$path, $ic, $label];
        if (count($mobileNavItems) === 2) break;
    }
}
?>
<nav class="mobile-app-nav" id="mobileAppNav">
    <a href="<?= e(url('/dashboard')) ?>" class="mobile-app-nav-link <?= is_active('/dashboard', '/') ?>">
        <?= icon('home') ?><span>Inicio</span>
    </a>
    <?php foreach ($mobileNavItems as [$path, $ic, $label]): ?>
    <a href="<?= e(url($path)) ?>" class="mobile-app-nav-link <?= is_active($path) ?>">
        <?= icon($ic) ?><span><?= e($label) ?></span>
    </a>
    <?php endforeach; ?>
    <button type="button" class="mobile-app-nav-link" data-toggle-sidebar>
        <?= icon('menu') ?><span>Menú</span>
    </button>
</nav>

<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
