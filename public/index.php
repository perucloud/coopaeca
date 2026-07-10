<?php

require dirname(__DIR__) . '/app/bootstrap.php';

require dirname(__DIR__) . '/app/Controllers/AuthController.php';
require dirname(__DIR__) . '/app/Controllers/DashboardController.php';
require dirname(__DIR__) . '/app/Controllers/ProfileController.php';
require dirname(__DIR__) . '/app/Controllers/SettingsController.php';
require dirname(__DIR__) . '/app/Controllers/UserController.php';
require dirname(__DIR__) . '/app/Controllers/PostController.php';
require dirname(__DIR__) . '/app/Controllers/ProductController.php';
require dirname(__DIR__) . '/app/Controllers/OrderController.php';
require dirname(__DIR__) . '/app/Controllers/SalesController.php';
require dirname(__DIR__) . '/app/Controllers/InventoryController.php';
require dirname(__DIR__) . '/app/Controllers/PaymentMethodController.php';
require dirname(__DIR__) . '/app/Controllers/ServiceController.php';
require dirname(__DIR__) . '/app/Controllers/GalleryController.php';
require dirname(__DIR__) . '/app/Controllers/SocialNetworkController.php';
require dirname(__DIR__) . '/app/Controllers/AboutController.php';
require dirname(__DIR__) . '/app/Controllers/ContactController.php';
require dirname(__DIR__) . '/app/Controllers/MediaController.php';
require dirname(__DIR__) . '/app/Controllers/LandingController.php';
require dirname(__DIR__) . '/app/Controllers/CheckoutController.php';
require dirname(__DIR__) . '/app/Controllers/IdentityController.php';
require dirname(__DIR__) . '/app/Controllers/UbigeoController.php';
require dirname(__DIR__) . '/app/Controllers/Mail/MailboxController.php';
require dirname(__DIR__) . '/app/Controllers/Mail/MailAccountController.php';
require dirname(__DIR__) . '/app/Controllers/Mail/MailComposeController.php';

$router = new Router();

// Landing publica
$router->get('/', [LandingController::class, 'index']);
$router->get('/nosotros', [LandingController::class, 'about']);
$router->get('/producto', [LandingController::class, 'productDetail']);
$router->get('/publicacion', [LandingController::class, 'postDetail']);
$router->get('/galeria', [LandingController::class, 'gallery']);
$router->get('/buscar', [LandingController::class, 'search']);
$router->get('/checkout', [CheckoutController::class, 'cart']);
$router->get('/checkout/success', [CheckoutController::class, 'success']);
$router->get('/ubigeo/departments', [UbigeoController::class, 'departments']);
$router->get('/ubigeo/provinces', [UbigeoController::class, 'provinces']);
$router->get('/ubigeo/districts', [UbigeoController::class, 'districts']);
$router->post('/identity/lookup', [IdentityController::class, 'lookup'], ['CsrfMiddleware', 'RateLimitMiddleware:identity,20,60']);
$router->post('/contact', [LandingController::class, 'contact'], ['CsrfMiddleware']);
$router->post('/checkout', [CheckoutController::class, 'store'], ['CsrfMiddleware']);

// Rutas publicas de autenticacion (solo para invitados)
$router->group(['middleware' => ['GuestMiddleware', 'CsrfMiddleware']], function (Router $r): void {
    $r->get('/login', [AuthController::class, 'loginForm']);
    $r->post('/login', [AuthController::class, 'login'], ['RateLimitMiddleware:login,5,60']);
    $r->get('/forgot-password', [AuthController::class, 'forgotForm']);
    $r->post('/forgot-password', [AuthController::class, 'sendReset']);
    $r->get('/reset-password', [AuthController::class, 'resetForm']);
    $r->post('/reset-password', [AuthController::class, 'reset']);
});

$router->post('/logout', [AuthController::class, 'logout'], ['CsrfMiddleware']);

// Rutas privadas del dashboard
$router->group(['middleware' => ['AuthMiddleware', 'CsrfMiddleware']], function (Router $r): void {
    $r->get('/dashboard', [DashboardController::class, 'index']);

    $r->get('/posts',        [PostController::class, 'index'],  ['PermissionMiddleware:posts.view']);
    $r->get('/posts/create', [PostController::class, 'create'], ['PermissionMiddleware:posts.create']);
    $r->post('/posts/store', [PostController::class, 'store'],  ['PermissionMiddleware:posts.create']);
    $r->get('/posts/edit',   [PostController::class, 'edit'],   ['PermissionMiddleware:posts.edit']);
    $r->post('/posts/update',[PostController::class, 'update'], ['PermissionMiddleware:posts.edit']);
    $r->post('/posts/delete',[PostController::class, 'delete'], ['PermissionMiddleware:posts.delete']);

    $r->get('/products',        [ProductController::class, 'index'],  ['PermissionMiddleware:products.view']);
    $r->get('/products/create', [ProductController::class, 'create'], ['PermissionMiddleware:products.create']);
    $r->post('/products/store', [ProductController::class, 'store'],  ['PermissionMiddleware:products.create']);
    $r->get('/products/edit',   [ProductController::class, 'edit'],   ['PermissionMiddleware:products.edit']);
    $r->post('/products/update',[ProductController::class, 'update'], ['PermissionMiddleware:products.edit']);
    $r->post('/products/delete',[ProductController::class, 'delete'], ['PermissionMiddleware:products.delete']);
    $r->get('/products/pdf',    [ProductController::class, 'pdf'],    ['PermissionMiddleware:products.view']);

    $r->get('/orders',       [OrderController::class, 'index'],      ['PermissionMiddleware:orders.view']);
    $r->get('/orders/show',  [OrderController::class, 'show'],       ['PermissionMiddleware:orders.view']);
    $r->post('/orders/review',[OrderController::class, 'markReview'],['PermissionMiddleware:orders.edit']);
    $r->post('/orders/approve',[OrderController::class, 'approve'],  ['PermissionMiddleware:orders.approve']);
    $r->post('/orders/reject',[OrderController::class, 'reject'],    ['PermissionMiddleware:orders.reject']);

    $r->get('/sales',        [SalesController::class, 'index'],      ['PermissionMiddleware:sales.view']);
    $r->get('/sales/create', [SalesController::class, 'create'],     ['PermissionMiddleware:sales.create']);
    $r->post('/sales/store', [SalesController::class, 'store'],      ['PermissionMiddleware:sales.create']);
    $r->get('/sales/show',   [SalesController::class, 'show'],       ['PermissionMiddleware:sales.view']);
    $r->post('/sales/cancel',[SalesController::class, 'cancel'],     ['PermissionMiddleware:sales.cancel']);
    $r->post('/sales/receipt/issue', [SalesController::class, 'issueReceipt'], ['PermissionMiddleware:sales.create']);
    $r->get('/sales/receipt/view',   [SalesController::class, 'viewReceipt'],  ['PermissionMiddleware:sales.view']);
    $r->post('/sales/receipt/email', [SalesController::class, 'emailReceipt'], ['PermissionMiddleware:sales.create']);

    $r->get('/inventory',            [InventoryController::class, 'index'],     ['PermissionMiddleware:inventory.view']);
    $r->get('/inventory/movements',  [InventoryController::class, 'movements'], ['PermissionMiddleware:inventory.view']);
    $r->post('/inventory/adjust',    [InventoryController::class, 'adjust'],    ['PermissionMiddleware:inventory.adjust']);
    $r->post('/inventory/bulk/store',[InventoryController::class, 'bulkStore'], ['PermissionMiddleware:inventory.adjust']);

    $r->get('/payment-methods',         [PaymentMethodController::class, 'index'],  ['PermissionMiddleware:payment_methods.view']);
    $r->post('/payment-methods/store',  [PaymentMethodController::class, 'store'],  ['PermissionMiddleware:payment_methods.edit']);
    $r->post('/payment-methods/update', [PaymentMethodController::class, 'update'], ['PermissionMiddleware:payment_methods.edit']);

    $r->get('/services',        [ServiceController::class, 'index'],  ['PermissionMiddleware:services.view']);
    $r->get('/services/create', [ServiceController::class, 'create'], ['PermissionMiddleware:services.create']);
    $r->post('/services/store', [ServiceController::class, 'store'],  ['PermissionMiddleware:services.create']);
    $r->get('/services/edit',   [ServiceController::class, 'edit'],   ['PermissionMiddleware:services.edit']);
    $r->post('/services/update',[ServiceController::class, 'update'], ['PermissionMiddleware:services.edit']);
    $r->post('/services/toggle',[ServiceController::class, 'toggle'], ['PermissionMiddleware:services.edit']);
    $r->post('/services/delete',[ServiceController::class, 'delete'], ['PermissionMiddleware:services.delete']);

    $r->get('/galleries',        [GalleryController::class, 'index'],  ['PermissionMiddleware:galleries.view']);
    $r->get('/galleries/create', [GalleryController::class, 'create'], ['PermissionMiddleware:galleries.create']);
    $r->post('/galleries/store', [GalleryController::class, 'store'],  ['PermissionMiddleware:galleries.create']);
    $r->get('/galleries/edit',   [GalleryController::class, 'edit'],   ['PermissionMiddleware:galleries.edit']);
    $r->post('/galleries/update',[GalleryController::class, 'update'], ['PermissionMiddleware:galleries.edit']);
    $r->post('/galleries/toggle',[GalleryController::class, 'toggle'], ['PermissionMiddleware:galleries.edit']);
    $r->post('/galleries/delete',[GalleryController::class, 'delete'], ['PermissionMiddleware:galleries.delete']);

    $r->get('/social-networks',        [SocialNetworkController::class, 'index'],  ['PermissionMiddleware:social_networks.view']);
    $r->post('/social-networks/store', [SocialNetworkController::class, 'store'],  ['PermissionMiddleware:social_networks.create']);
    $r->post('/social-networks/update',[SocialNetworkController::class, 'update'], ['PermissionMiddleware:social_networks.edit']);
    $r->post('/social-networks/toggle',[SocialNetworkController::class, 'toggle'], ['PermissionMiddleware:social_networks.edit']);
    $r->post('/social-networks/delete',[SocialNetworkController::class, 'delete'], ['PermissionMiddleware:social_networks.delete']);

    $r->get('/about',  [AboutController::class, 'edit'],   ['PermissionMiddleware:pages.view']);
    $r->post('/about', [AboutController::class, 'update'], ['PermissionMiddleware:pages.edit']);

    $r->get('/contacts',          [ContactController::class, 'index'],  ['PermissionMiddleware:contacts.view']);
    $r->post('/contacts/update',  [ContactController::class, 'update'], ['PermissionMiddleware:contacts.edit']);
    $r->post('/contacts/retry',   [ContactController::class, 'retry'],  ['PermissionMiddleware:contacts.edit']);
    $r->post('/contacts/delete',  [ContactController::class, 'delete'], ['PermissionMiddleware:contacts.delete']);

    $r->get('/media',          [MediaController::class, 'index'],  ['PermissionMiddleware:files.view']);
    $r->get('/media/picker',   [MediaController::class, 'picker'], ['PermissionMiddleware:files.view']);
    $r->post('/media/store',   [MediaController::class, 'store'],  ['PermissionMiddleware:files.create']);
    $r->post('/media/upload-json', [MediaController::class, 'uploadJson'], ['PermissionMiddleware:files.create']);
    $r->post('/media/delete',  [MediaController::class, 'delete'], ['PermissionMiddleware:files.delete']);

    $r->get('/profile',          [ProfileController::class, 'edit']);
    $r->post('/profile',         [ProfileController::class, 'update']);
    $r->post('/profile/password',[ProfileController::class, 'password']);

    $r->get('/settings',  [SettingsController::class, 'edit'],   ['PermissionMiddleware:settings.view']);
    $r->post('/settings', [SettingsController::class, 'update'], ['PermissionMiddleware:settings.edit']);

    // Webmail (Fase 1: lectura)
    $r->group(['prefix' => '/dashboard/mail'], function (Router $r): void {
        $r->get('',            [MailboxController::class, 'inbox']);
        $r->get('/sync',       [MailboxController::class, 'sync']);
        $r->get('/read',       [MailboxController::class, 'read']);
        $r->get('/attachment', [MailboxController::class, 'attachment']);
        $r->post('/seen',      [MailboxController::class, 'toggleSeen']);
        $r->post('/delete',    [MailboxController::class, 'delete']);
        $r->post('/move',      [MailboxController::class, 'move']);

        $r->get('/accounts',          [MailAccountController::class, 'index']);
        $r->post('/accounts/store',   [MailAccountController::class, 'store']);
        $r->post('/accounts/delete',  [MailAccountController::class, 'delete']);
        $r->post('/accounts/default', [MailAccountController::class, 'setDefault']);

        $r->get('/compose',                    [MailComposeController::class, 'form']);
        $r->post('/compose/draft',             [MailComposeController::class, 'guardarBorrador']);
        $r->post('/compose/attachment',        [MailComposeController::class, 'subirAdjunto']);
        $r->post('/compose/attachment/delete', [MailComposeController::class, 'eliminarAdjunto']);
        $r->post('/compose/send',              [MailComposeController::class, 'enviar']);
        $r->post('/compose/discard',           [MailComposeController::class, 'descartar']);
    });

    $r->get('/users',           [UserController::class, 'index'],  ['PermissionMiddleware:users.view']);
    $r->post('/users/store',    [UserController::class, 'store'],  ['PermissionMiddleware:users.create']);
    $r->post('/users/update',   [UserController::class, 'update'], ['PermissionMiddleware:users.edit']);
    $r->post('/users/toggle',   [UserController::class, 'toggle'], ['PermissionMiddleware:users.edit']);
});

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
