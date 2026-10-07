<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<string, array<string, callable|array{class-string, string}>> */
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function add(string $method, string $path, callable|array $handler): void
    {
        $method = strtoupper($method);
        $path = '/' . trim($path, '/');
        $this->routes[$method][$path === '/' ? '/' : rtrim($path, '/')] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = '/' . trim(rawurldecode($path), '/');
        $path = $path === '/' ? '/' : rtrim($path, '/');
        $handler = $this->routes[strtoupper($method)][$path] ?? null;

        if ($handler === null) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Not Found';
            return;
        }

        if (is_array($handler) && is_string($handler[0])) {
            $controllerClass = $handler[0];
            $controller = new $controllerClass();
            $handler = [$controller, $handler[1]];
        }

        $response = $handler();
        if (is_string($response)) {
            echo $response;
        }
    }
}
