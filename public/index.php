<?php

declare(strict_types=1);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require dirname(__DIR__) . '/app/helpers/functions.php';
date_default_timezone_set(config('timezone'));

spl_autoload_register(function (string $class): void {
    foreach (['core', 'controllers', 'models', 'middleware'] as $folder) {
        $file = dirname(__DIR__) . "/app/{$folder}/{$class}.php";
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

$router = new Router();
require dirname(__DIR__) . '/routes/web.php';
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
