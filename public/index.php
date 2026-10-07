<?php
declare(strict_types=1);

use App\Core\Router;
use App\Controllers\AccessController;
use App\Controllers\AdminAuthController;
use App\Controllers\AuthController;
use App\Middleware\AuthMiddleware;

define('BASE_PATH', dirname(__DIR__));

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $path = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

require BASE_PATH . '/config/bootstrap.php';

$router = new Router();
$authMiddleware = new AuthMiddleware();

$router->get('/', static function (): void {
    header('Location: /login', true, 303);
});

$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/admin/login', [AdminAuthController::class, 'showLogin']);
$router->post('/admin/login', [AdminAuthController::class, 'login']);
$router->post('/admin/logout', [AdminAuthController::class, 'logout']);

$router->get('/account', static fn () => $authMiddleware->handle(
    'customer',
    static fn () => (new AccessController())->customer()
));
$router->get('/admin', static fn () => $authMiddleware->handle(
    'admin',
    static fn () => (new AccessController())->admin()
));

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
