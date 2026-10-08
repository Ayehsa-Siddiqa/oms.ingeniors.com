<?php 
$user = Auth::user(); 
$companyName = setting('company_name', config('name', 'INGENIORS'));
$themeColor = setting('theme_color', '#4f8cff');
$defaultTheme = setting('default_theme', 'dark');
$defaultSidebar = setting('sidebar_default', 'expanded');
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
            /* Apply sidebar state before first paint to prevent text flash */
            var sidebarPref = localStorage.getItem('oms_sidebar_collapsed');
            if (sidebarPref === '1' || (sidebarPref === null && '<?= e($defaultSidebar) ?>' === 'collapsed')) {
                document.documentElement.classList.add('sidebar-pre-collapsed');
            }
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
            --bs-link-color: <?= e($themeColor) ?> !important;
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
        .btn-outline-primary {
            color: var(--oms-primary) !important;
            border-color: var(--oms-primary) !important;
        }
        .btn-outline-primary:hover, .btn-outline-primary:focus, .btn-outline-primary:active, .btn-outline-primary.active {
            background-color: var(--oms-primary) !important;
            border-color: var(--oms-primary) !important;
            color: #ffffff !important;
        }
        .text-primary {
            color: var(--oms-primary) !important;
        }
        .bg-primary {
            background-color: var(--oms-primary) !important;
        }
        .border-primary {
            border-color: var(--oms-primary) !important;
        }
        .nav-link.active, .nav-tabs .nav-link.active {
            color: var(--oms-primary) !important;
            border-bottom-color: var(--oms-primary) !important;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--oms-primary) !important;
            box-shadow: 0 0 0 0.2rem rgba(var(--oms-primary-rgb), 0.2) !important;
        }
        .badge-soft-primary {
            background: rgba(var(--oms-primary-rgb), 0.14) !important;
            color: var(--oms-primary) !important;
            border-color: rgba(var(--oms-primary-rgb), 0.28) !important;
        }
    </style>
</head>
<body>
<div class="app">
    <?php require dirname(__DIR__) . '/partials/sidebar.php'; ?>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
    <div class="content">
        <?php require dirname(__DIR__) . '/partials/topbar.php'; ?>
        <div class="content-scrollable" id="contentScrollable">
            <main class="container-fluid py-4 content-page-wrapper" id="contentPageWrapper">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= url('dashboard') ?>">Home</a></li>
                        <li class="breadcrumb-item active"><?= e($title ?? 'Page') ?></li>
                    </ol>
                </nav>
                <?php require dirname(__DIR__) . '/partials/alerts.php'; ?>
                <?= $content ?>
            </main>
            <footer class="footer px-4 py-3"><?= e($companyName) ?> &copy; <?= date('Y') ?></footer>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/app.js') ?>?v=<?= time() ?>"></script>
</body>
</html>


