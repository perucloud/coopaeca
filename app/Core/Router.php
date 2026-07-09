<?php

final class Router
{
    private array $routes = [];
    private array $groupStack = [];

    public function get(string $path, array $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    private function addRoute(string $method, string $path, array $handler, array $middleware): void
    {
        $prefix = '';
        $groupMiddleware = [];
        foreach ($this->groupStack as $group) {
            $prefix .= $group['prefix'] ?? '';
            $groupMiddleware = array_merge($groupMiddleware, $group['middleware'] ?? []);
        }

        $fullPath = '/' . trim($prefix . $path, '/');
        if ($fullPath === '') {
            $fullPath = '/';
        }

        $this->routes[$method][$fullPath] = [
            'handler' => $handler,
            'middleware' => array_merge($groupMiddleware, $middleware),
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
        if (str_ends_with(str_replace('\\', '/', $base), '/public')) {
            $base = substr($base, 0, -7) ?: '/';
        }
        if ($base !== '' && $base !== '/' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }
        $path = '/' . trim($path, '/');
        if ($path === '//') {
            $path = '/';
        }

        $route = $this->routes[$method][$path] ?? null;
        if (!$route) {
            Response::abort(404);
        }

        $request = Request::capture();
        $destination = function (Request $request) use ($route): void {
            [$class, $action] = $route['handler'];
            (new $class())->$action();
        };

        $this->runPipeline($route['middleware'], $request, $destination);
    }

    private function runPipeline(array $middleware, Request $request, Closure $destination): void
    {
        $chain = array_reduce(
            array_reverse($middleware),
            function (Closure $next, string $entry): Closure {
                return function (Request $request) use ($entry, $next) {
                    $instance = $this->resolveMiddleware($entry);
                    return $instance->handle($request, $next);
                };
            },
            $destination
        );

        $chain($request);
    }

    private function resolveMiddleware(string $entry): MiddlewareInterface
    {
        if (str_contains($entry, ':')) {
            [$class, $param] = explode(':', $entry, 2);
            return new $class($param);
        }
        return new $entry();
    }
}
