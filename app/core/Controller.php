<?php

class Controller
{
    public function view(string $view, array $data = [], string $layout = 'main'): void
    {
        extract($data);
        $viewPath = dirname(__DIR__) . "/views/{$view}.php";

        if (!file_exists($viewPath)) {
            http_response_code(500);
            exit("View not found: {$view}");
        }

        ob_start();
        require $viewPath;
        $content = ob_get_clean();
        require dirname(__DIR__) . "/views/layouts/{$layout}.php";
    }

    protected function redirect(string $path): void
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            header('Location: ' . $path);
        } else {
            header('Location: ' . url($path));
        }
        exit;
    }
}
