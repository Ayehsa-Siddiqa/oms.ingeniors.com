<header class="topbar">
    <div class="d-flex align-items-center gap-2 gap-sm-3 min-w-0">
        <button class="btn btn-sidebar-toggle d-lg-none" data-sidebar-toggle type="button" aria-label="Toggle Navigation" title="Open Navigation Menu">
            <i class="bi bi-list"></i>
        </button>
        <div class="topbar-title-wrap">
            <h1 class="topbar-title"><?= e($title ?? 'Dashboard') ?></h1>
            <div class="topbar-subtitle text-muted"><?= e(date('l, M d, Y')) ?></div>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-shrink-0">
        <button class="btn btn-theme-toggle" id="themeToggleBtn" type="button" aria-label="Toggle Theme" title="Toggle Light/Dark Mode">
            <i class="bi bi-sun-fill" id="themeIcon"></i>
        </button>
        <div class="dropdown">
            <button class="btn btn-user-menu dropdown-toggle" data-bs-toggle="dropdown" type="button">
                <i class="bi bi-person-circle"></i>
                <span class="user-menu-name"><?= e($user['name'] ?? 'User') ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li><a class="dropdown-item" href="<?= url('profile') ?>"><i class="bi bi-person me-2"></i>Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= url('logout') ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</header>


