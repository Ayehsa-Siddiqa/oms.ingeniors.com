<?php

$isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', 'localhost:8000', '127.0.0.1:8000']) || (php_sapi_name() === 'cli' && empty($_SERVER['HTTP_HOST']));

if ($isLocal) {
    return [
        'host' => '127.0.0.1',
        'database' => 'oms',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ];
}

return [
    'host' => 'localhost',
    'database' => 'hujyopjz_oms',     // full database name from your hosting panel
    'username' => 'hujyopjz_omsuser',   // full database username from your hosting panel
    'password' => '^SWS~Q3Sho7iB0e)',            // the password you set for that user
    'charset' => 'utf8mb4',
];

