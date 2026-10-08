<?php 
$companyName = setting('company_name', config('name', 'INGENIORS'));
$themeColor = setting('theme_color', '#4f8cff');
$defaultTheme = setting('default_theme', 'dark');
?>
<!doctype html>
<html lang="en" data-theme="<?= e($defaultTheme) ?>" data-bs-theme="<?= e($defaultTheme) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ? $title . ' - ' . $companyName : $companyName) ?></title>
    <script>
        (function() {
            var theme = localStorage.getItem('oms_theme') || '<?= e($defaultTheme) ?>';
            document.documentElement.setAttribute('data-theme', theme);
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>
    <link rel="icon" type="image/jpeg" href="<?= asset('images/ingeniors-light-logo.jpeg') ?>">
    <link rel="apple-touch-icon" href="<?= asset('images/ingeniors-light-logo.jpeg') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= asset('css/app.css') ?>?v=<?= time() ?>" rel="stylesheet">
    <?php $themeRgb = hexToRgb($themeColor); ?>
    <style>
        :root, [data-theme="dark"], [data-theme="light"], [data-bs-theme="dark"], [data-bs-theme="light"] {
            --oms-primary: <?= e($themeColor) ?> !important;
            --oms-primary-hover: <?= e($themeColor) ?>ee !important;
            --oms-primary-rgb: <?= $themeRgb ?> !important;
            --primary: <?= e($themeColor) ?> !important;
            --bs-primary: <?= e($themeColor) ?> !important;
            --bs-primary-rgb: <?= $themeRgb ?> !important;
        }
        .btn-primary {
            background-color: var(--oms-primary) !important;
            border-color: var(--oms-primary) !important;
            color: #ffffff !important;
        }
        .btn-primary:hover, .btn-primary:focus, .btn-primary:active {
            background-color: var(--oms-primary-hover) !important;
            border-color: var(--oms-primary-hover) !important;
            color: #ffffff !important;
        }
    </style>
</head>
<body class="auth-body">
    <main class="auth-card shadow-sm">
        <?= $content ?>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= asset('js/app.js') ?>?v=<?= time() ?>"></script>
</body>
</html>


