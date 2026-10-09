<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        require BASE_PATH.'/app/'.str_replace('\\', '/', substr($class, 4)).'.php';
    }
});
$GLOBALS['config']['app']['name'] = 'AeroBook';
set_error_handler(static function (int $severity, string $message): never {
    throw new ErrorException($message, 0, $severity);
});
function check(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}

foreach (['guest', 'customer', 'admin'] as $role) {
    $_SESSION = $role === 'guest' ? [] : ['user_id'=>1, 'user_role'=>$role, 'user_name'=>'Test <User>'];
    // Public Home must ignore account-module query parameters and render without a database.
    $_GET = ['section'=>'bookings'];
    ob_start();
    (new App\Controllers\AccessController())->home();
    $html = ob_get_clean();
    check(str_contains($html, 'id="hero-heading"'), "$role cannot view Home");
    check(!str_contains($html, 'class="customer-bookings"'), 'Home exposed an account module');
    check(str_contains($html, 'href="/flights"'), 'Flight search link is missing');
    check(str_contains($html, 'href="/"'), 'Home navigation link is missing');
    if ($role === 'guest') {
        check(str_contains($html, 'href="/login">Login</a>'), 'Guest Login option is missing');
        check(str_contains($html, 'href="/register">Sign Up</a>'), 'Guest Sign Up option is missing');
        check(!str_contains($html, 'action="/logout"'), 'Guest sees a sign-out form');
    } else {
        check(str_contains($html, 'Hi, Test &lt;User&gt;'), 'Authenticated greeting is missing or unescaped');
        check(str_contains($html, 'action="/logout"'), 'Authenticated sign-out form is missing');
        check(!str_contains($html, 'href="/register">Sign Up</a>'), 'Authenticated user sees guest access links');
        if ($role === 'admin') check(str_contains($html, 'href="/admin"'), 'Admin panel link is missing');
    }
    echo "PASS: $role public Home rendering\n";
}
