<?php

class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, array $handler): void
    {
        $pattern = preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*\}#', '([^/]+)', trim($path, '/'));
        $this->routes[] = compact('method', 'path', 'pattern', 'handler');
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = trim(parse_url($uri, PHP_URL_PATH), '/');
        $base = trim(parse_url(config('base_url'), PHP_URL_PATH) ?? '', '/');
        
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = trim(substr($uri, strlen($base)), '/');
        }
        
        if (str_starts_with($uri, 'public/')) {
            $uri = trim(substr($uri, 7), '/');
        } elseif ($uri === 'public') {
            $uri = '';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match('#^' . $route['pattern'] . '$#', $uri, $matches)) {
                array_shift($matches);
                [$class, $action] = $route['handler'];
                $fixedParameters = array_slice($route['handler'], 2);
                $controller = new $class();
                $controller->$action(...array_merge($fixedParameters, $matches));
                return;
            }
        }

        http_response_code(404);
        (new Controller())->view('errors/404', ['title' => 'Page Not Found']);
    }
}
