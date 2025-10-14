<?php
namespace App;

class Router
{
    private array $routes = [];

    public function get(string $pattern, callable|array $action): void
    {
        $this->map('GET', $pattern, $action);
    }

    public function post(string $pattern, callable|array $action): void
    {
        $this->map('POST', $pattern, $action);
    }

    private function map(string $method, string $pattern, callable|array $action): void
    {
        $this->routes[] = [
            'method' => $method,
            'pattern' => '#^' . $pattern . '$#',
            'action' => $action
        ];
    }

    public function dispatch(string $method, string $uri)
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        foreach ($this->routes as $route) {
            if ($method !== $route['method']) {
                continue;
            }
            if (preg_match($route['pattern'], $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $action = $route['action'];
                if (is_array($action)) {
                    [$class, $func] = $action;
                    $controller = new $class();
                    return $controller->$func($params);
                }
                return $action($params);
            }
        }
        http_response_code(404);
        echo 'Not Found';
    }
}