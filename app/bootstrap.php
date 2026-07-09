<?php

declare(strict_types=1);

require __DIR__ . '/Helpers/app.php';
require __DIR__ . '/Helpers/icons.php';
require __DIR__ . '/Helpers/security.php';
require __DIR__ . '/Helpers/logger.php';
require __DIR__ . '/Helpers/crypto.php';
require __DIR__ . '/Helpers/lang.php';

// Autoload de Composer DESPUES de los helpers propios: illuminate/support
// (dependencia de webklex/php-imap) define e() y otros helpers globales
// protegidos con function_exists; los del proyecto deben ganar.
$vendorAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($vendorAutoload)) {
    require $vendorAutoload;
}
unset($vendorAutoload);
require __DIR__ . '/Core/Database.php';
require __DIR__ . '/Core/Response.php';
require __DIR__ . '/Core/Request.php';
require __DIR__ . '/Core/MiddlewareInterface.php';
require __DIR__ . '/Core/Router.php';
require __DIR__ . '/Core/Controller.php';
require __DIR__ . '/Core/Model.php';
require __DIR__ . '/Services/ImapService.php';
require __DIR__ . '/Services/SmtpService.php';
require __DIR__ . '/Services/ContactNotifier.php';
require __DIR__ . '/Services/ApiPeruIdentityService.php';
require __DIR__ . '/Services/VoucherStorageService.php';
require __DIR__ . '/Services/InventoryService.php';
require __DIR__ . '/Services/OrderService.php';
require __DIR__ . '/Services/SaleService.php';
require __DIR__ . '/Helpers/auth.php';
require __DIR__ . '/Middleware/AuthMiddleware.php';
require __DIR__ . '/Middleware/GuestMiddleware.php';
require __DIR__ . '/Middleware/CsrfMiddleware.php';
require __DIR__ . '/Middleware/PermissionMiddleware.php';
require __DIR__ . '/Middleware/RateLimitMiddleware.php';

load_env_file(dirname(__DIR__) . '/.env');

$app = require __DIR__ . '/Config/app.php';
date_default_timezone_set($app['timezone']);
start_secure_session();

ini_set('display_errors', $app['debug'] ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', dirname(__DIR__) . '/storage/logs/php-errors.log');
error_reporting(E_ALL);

set_exception_handler(function (Throwable $e) use ($app): void {
    app_log('exception', $e->getMessage(), ['file' => $e->getFile() . ':' . $e->getLine()]);
    if ($app['debug']) {
        http_response_code(500);
        echo '<pre>' . e((string)$e) . '</pre>';
        exit;
    }
    Response::abort(500, 'Error interno del servidor.');
});

remember_login();
