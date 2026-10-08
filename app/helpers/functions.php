<?php

function config(string $key, mixed $default = null): mixed
{
    static $config = null;
    if ($config === null) {
        $config = require dirname(__DIR__) . '/config/app.php';
    }
    return $config[$key] ?? $default;
}

function url(string $path = ''): string
{
    return rtrim(config('base_url'), '/') . '/' . ltrim($path, '/');
}

function absolute_url(string $path = ''): string
{
    $host = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'oms.ingeniors.com';
    $basePath = rtrim(config('base_url', ''), '/');
    return 'https://' . $host . $basePath . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return rtrim(config('base_url'), '/') . '/assets/' . ltrim($path, '/');
}

function absolute_asset(string $path): string
{
    return absolute_url('assets/' . ltrim($path, '/'));
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals($_SESSION['_csrf'] ?? '', $_POST['_csrf'] ?? '')) {
        http_response_code(419);
        exit('Invalid security token.');
    }
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
}

function setting(string $key, mixed $default = null): mixed
{
    static $settings = null;
    if ($settings === null) {
        try {
            $settings = Database::connection()->query('SELECT setting_key, setting_value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (\Throwable $e) {
            $settings = [];
        }
    }
    return $settings[$key] ?? $default;
}

function active_nav(string $path): string
{
    $current = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    return str_contains($current, trim($path, '/')) ? 'active' : '';
}

function money(mixed $value): string
{
    $sym = setting('currency_symbol', 'PKR');
    $prefix = match($sym) {
        'PKR' => 'PKR ',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'AED' => 'AED ',
        'SAR' => 'SAR ',
        default => $sym . ' ',
    };
    return $prefix . number_format((float) $value, 2);
}

function hexToRgb(string $hex): string
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $r = hexdec(str_repeat(substr($hex, 0, 1), 2));
        $g = hexdec(str_repeat(substr($hex, 1, 1), 2));
        $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
    } elseif (strlen($hex) >= 6) {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    } else {
        return '79, 140, 255';
    }
    return "$r, $g, $b";
}

function component(string $name, array $data = []): void
{
    extract($data);
    $file = dirname(__DIR__) . "/views/components/{$name}.php";
    if (!file_exists($file)) {
        $file = dirname(__DIR__) . "/views/partials/{$name}.php";
    }
    if (file_exists($file)) {
        require $file;
    }
}

function render_pagination(int $currentPage, int $totalPages, callable $urlGenerator): string
{
    if ($totalPages <= 1) {
        return '';
    }

    $currentPage = max(1, min($currentPage, $totalPages));
    $html = '<div class="btn-group btn-group-sm" role="navigation" aria-label="Pagination">';

    // Previous Button
    if ($currentPage > 1) {
        $prevUrl = $urlGenerator($currentPage - 1);
        $html .= '<a class="btn btn-outline-primary" href="' . e($prevUrl) . '" title="Previous Page"><i class="bi bi-chevron-left me-1"></i>Prev</a>';
    } else {
        $html .= '<button type="button" class="btn btn-outline-secondary disabled" disabled style="opacity: 0.5;"><i class="bi bi-chevron-left me-1"></i>Prev</button>';
    }

    // Numbered Pages with Ellipsis
    if ($totalPages <= 7) {
        $elements = range(1, $totalPages);
    } elseif ($currentPage <= 3) {
        $elements = [1, 2, 3, 4, '...', $totalPages];
    } elseif ($currentPage >= $totalPages - 2) {
        $elements = [1, '...', $totalPages - 3, $totalPages - 2, $totalPages - 1, $totalPages];
    } else {
        $elements = [1, '...', $currentPage - 1, $currentPage, $currentPage + 1, '...', $totalPages];
    }

    foreach ($elements as $elem) {
        if ($elem === '...') {
            $html .= '<span class="btn btn-outline-secondary disabled px-2" style="opacity: 0.6; cursor: default; pointer-events: none;">&hellip;</span>';
        } else {
            $pageNum = (int)$elem;
            $isActive = ($pageNum === $currentPage);
            $url = $urlGenerator($pageNum);
            if ($isActive) {
                $html .= '<a class="btn btn-primary active fw-bold" href="' . e($url) . '" aria-current="page">' . $pageNum . '</a>';
            } else {
                $html .= '<a class="btn btn-outline-primary" href="' . e($url) . '">' . $pageNum . '</a>';
            }
        }
    }

    // Next Button
    if ($currentPage < $totalPages) {
        $nextUrl = $urlGenerator($currentPage + 1);
        $html .= '<a class="btn btn-outline-primary" href="' . e($nextUrl) . '" title="Next Page">Next<i class="bi bi-chevron-right ms-1"></i></a>';
    } else {
        $html .= '<button type="button" class="btn btn-outline-secondary disabled" disabled style="opacity: 0.5;">Next<i class="bi bi-chevron-right ms-1"></i></button>';
    }

    $html .= '</div>';
    return $html;
}

