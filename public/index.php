<?php
declare(strict_types=1);

use App\Core\Router;
use App\Controllers\AccessController;
use App\Controllers\AdminAuthController;
use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\CustomerFlightController;
use App\Controllers\BookingController;
use App\Controllers\SeatSelectionController;
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

$router->get('/admin/login', static function (): void {
    header('Location: /login', true, 303);
});
$router->post('/admin/login', [AuthController::class, 'login']);
$router->post('/admin/logout', static function () use ($authMiddleware): mixed {
    return $authMiddleware->handle('admin', static fn () => (new AdminAuthController())->logout());
});

$router->get('/account', static function () use ($authMiddleware): mixed {
    return $authMiddleware->handle('customer', static fn () => (new AccessController())->customer());
});
$router->get('/flights', static function () use ($authMiddleware): mixed {
    return $authMiddleware->handle('customer', static fn () => (new CustomerFlightController())->search());
});
$router->get('/flights/details', static function () use ($authMiddleware): mixed {
    return $authMiddleware->handle('customer', static fn () => (new CustomerFlightController())->details());
});
$router->post('/flights/select', static function () use ($authMiddleware): mixed {
    return $authMiddleware->handle('customer', static fn () => (new CustomerFlightController())->select());
});
$router->get('/booking/passengers', static function () use ($authMiddleware): mixed {
    return $authMiddleware->handle('customer', static fn () => (new BookingController())->passengers());
});
$router->post('/booking/review', static function () use ($authMiddleware): mixed {
    return $authMiddleware->handle('customer', static fn () => (new BookingController())->review());
});
$router->post('/booking/create', static function () use ($authMiddleware): mixed {
    return $authMiddleware->handle('customer', static fn () => (new BookingController())->create());
});
$router->get('/booking/confirmation', static function () use ($authMiddleware): mixed {
    return $authMiddleware->handle('customer', static fn () => (new BookingController())->confirmation());
});
$router->get('/seat-selection', static function () use ($authMiddleware): mixed {
    return $authMiddleware->handle('customer', static fn () => (new SeatSelectionController())->index());
});
$router->post('/seat-selection/save', static function () use ($authMiddleware): mixed {
    return $authMiddleware->handle('customer', static fn () => (new SeatSelectionController())->save());
});
$router->get('/admin', static function () use ($authMiddleware): mixed {
    return $authMiddleware->handle('admin', static fn () => (new AdminController())->dashboard());
});
$adminPages = [
    '/admin/airlines' => 'airlines', '/admin/airports' => 'airports', '/admin/flights' => 'flights',
    '/admin/seats' => 'seats', '/admin/bookings' => 'bookings', '/admin/payments' => 'payments',
];
foreach ($adminPages as $path => $method) {
    $router->get($path, static function () use ($authMiddleware, $method): mixed {
        return $authMiddleware->handle('admin', static function () use ($method): void {
            $controller = new AdminController();
            $controller->{$method}();
        });
    });
}
$router->post('/admin/action', static function () use ($authMiddleware): mixed {
    return $authMiddleware->handle('admin', static fn () => (new AdminController())->save());
});
$router->get('/admin/payment-proof', static function () use ($authMiddleware): mixed {
    return $authMiddleware->handle('admin', static fn () => (new AdminController())->proof());
});

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
