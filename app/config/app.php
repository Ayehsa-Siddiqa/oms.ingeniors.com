<?php

// Auto-detect base URL (e.g. /oms.ingeniors.com, /oms, or empty for subdomain root)
$basePath = '';
if (!empty($_SERVER['SCRIPT_NAME'])) {
    $dir = dirname($_SERVER['SCRIPT_NAME']);
    $dir = str_replace('\\', '/', $dir);
    $dir = preg_replace('#/public$#', '', $dir);
    $basePath = ($dir === '/' || $dir === '.' || $dir === '') ? '' : rtrim($dir, '/');
}

return [
    'name' => 'INGENIORS',
    'base_url' => $basePath,
    'timezone' => 'Asia/Karachi',
    'session_lifetime' => 3600,
    'remember_lifetime' => 2592000,
    'upload_path' => dirname(__DIR__, 2) . '/assets/uploads',
    'allowed_uploads' => [
        // Documents (Word, PDF, Text)
        'pdf', 'doc', 'docx', 'dot', 'dotx', 'docm', 'rtf', 'txt', 'md', 'log', 'json', 'xml',
        // Spreadsheets (Excel, CSV)
        'xls', 'xlsx', 'xlsm', 'xlsb', 'csv',
        // Presentations (PowerPoint)
        'ppt', 'pptx', 'pps', 'ppsx', 'pot', 'potx',
        // Images
        'png', 'jpg', 'jpeg', 'webp', 'gif', 'bmp', 'svg', 'ico', 'tif', 'tiff',
        // Archives
        'zip', 'rar', '7z', 'tar', 'gz',
        // CAD & Engineering
        'dwg', 'dxf', 'rvt', 'ifc', 'skp'
    ],
    'max_upload_size' => 50 * 1024 * 1024, // 50MB
];


