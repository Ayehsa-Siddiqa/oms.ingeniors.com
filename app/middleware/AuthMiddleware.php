<?php

class AuthMiddleware
{
    public static function requireAuth(): void
    {
        Auth::attemptRememberLogin();

        if (!Auth::check()) {
            header('Location: ' . url('login'));
            exit;
        }

        if ((time() - ($_SESSION['last_activity'] ?? 0)) > config('session_lifetime')) {
            Auth::logout();
            header('Location: ' . url('login'));
            exit;
        }

        $_SESSION['last_activity'] = time();
    }

    public static function requirePermission(string $permission): void
    {
        self::requireAuth();
        if (!Auth::can($permission)) {
            http_response_code(403);
            (new Controller())->view('errors/403', ['title' => 'Access Denied']);
            exit;
        }
    }
}
