<aside class="sidebar" id="appSidebar">
    <!-- Brand: full logo (expanded) -->
    <div class="brand sidebar-brand-full">
        <img class="brand-logo sidebar-logo-dark"  src="<?= asset('images/ingeniors-dark-logo.png') ?>"  alt="INGENIORS logo">
        <img class="brand-logo sidebar-logo-light" src="<?= asset('images/ingeniors-light-logo.jpeg') ?>" alt="INGENIORS logo">
        <div class="sidebar-brand-text">
            <strong><?= e(config('name')) ?></strong>
            <small>Engineering Operations</small>
        </div>
        <!-- Desktop Collapse button -->
        <button class="sidebar-collapse-btn d-none d-lg-inline-flex" id="sidebarCollapseBtn" type="button" title="Collapse sidebar" aria-label="Collapse sidebar">
            <i class="bi bi-layout-sidebar-reverse"></i>
        </button>
        <!-- Mobile Close button -->
        <button class="sidebar-close-btn d-lg-none" id="sidebarMobileCloseBtn" type="button" title="Close sidebar" aria-label="Close sidebar">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <!-- Mini logo shown only when collapsed — hover to reveal expand button -->
    <div class="sidebar-brand-mini">
        <div class="mini-logo-wrap" id="sidebarExpandBtn" title="Expand sidebar" role="button" tabindex="0" aria-label="Expand sidebar">
            <img class="brand-logo-mini sidebar-logo-dark"  src="<?= asset('images/ingeniors-dark-logo.png') ?>"  alt="INGENIORS">
            <img class="brand-logo-mini sidebar-logo-light" src="<?= asset('images/ingeniors-light-logo.jpeg') ?>" alt="INGENIORS">
            <span class="mini-expand-overlay"><i class="bi bi-layout-sidebar"></i></span>
        </div>
    </div>

    <nav class="nav flex-column gap-1 mt-2">
        <?php if (Auth::can('dashboard')): ?>
            <a class="nav-link <?= active_nav('dashboard') ?>" href="<?= url('dashboard') ?>" title="Dashboard"><i class="bi bi-speedometer2"></i><span class="nav-label"> Dashboard</span></a>
        <?php endif; ?>
        <?php if (Auth::can('projects')): ?>
            <a class="nav-link <?= active_nav('projects') ?>" href="<?= url('projects') ?>" title="Projects"><i class="bi bi-kanban"></i><span class="nav-label"> Projects</span></a>
        <?php endif; ?>
        <?php if (Auth::can('pending_tasks')): ?>
            <a class="nav-link <?= active_nav('pending-tasks') ?>" href="<?= url('pending-tasks') ?>" title="Pending Tasks"><i class="bi bi-list-check"></i><span class="nav-label"> Pending Tasks</span></a>
        <?php endif; ?>
        <?php if (Auth::can('requirements')): ?>
            <a class="nav-link <?= active_nav('requirements') ?>" href="<?= url('requirements') ?>" title="Requirements"><i class="bi bi-cart-check"></i><span class="nav-label"> Requirements</span></a>
        <?php endif; ?>
        <?php if (Auth::can('employees')): ?>
            <a class="nav-link <?= active_nav('employees') ?>" href="<?= url('employees') ?>" title="Employees"><i class="bi bi-people"></i><span class="nav-label"> Employees</span></a>
        <?php endif; ?>
        <?php if (Auth::can('departments')): ?>
            <a class="nav-link <?= active_nav('departments') ?>" href="<?= url('departments') ?>" title="Departments"><i class="bi bi-diagram-3"></i><span class="nav-label"> Departments</span></a>
        <?php endif; ?>
        <?php if (Auth::can('fines')): ?>
            <a class="nav-link <?= active_nav('employee-fines') ?>" href="<?= url('employee-fines') ?>" title="Employee Fines"><i class="bi bi-cash-coin"></i><span class="nav-label"> Employee Fines</span></a>
        <?php endif; ?>
        <?php if (Auth::can('documents')): ?>
            <a class="nav-link <?= active_nav('documents') ?>" href="<?= url('documents') ?>" title="Documents"><i class="bi bi-folder2-open"></i><span class="nav-label"> Documents</span></a>
        <?php endif; ?>
        <?php if (Auth::can('announcements')): ?>
            <a class="nav-link <?= active_nav('announcements') ?>" href="<?= url('announcements') ?>" title="Announcements"><i class="bi bi-megaphone"></i><span class="nav-label"> Announcements</span></a>
        <?php endif; ?>
        <?php if (Auth::can('reports')): ?>
            <a class="nav-link <?= active_nav('reports') ?>" href="<?= url('reports') ?>" title="Reports"><i class="bi bi-file-earmark-bar-graph"></i><span class="nav-label"> Reports</span></a>
        <?php endif; ?>
        <?php if (Auth::can('backup')): ?>
            <a class="nav-link <?= (active_nav('backup') || active_nav('recycle-bin')) ? 'active' : '' ?>" href="<?= url('backup') ?>" title="Backup"><i class="bi bi-shield-check"></i><span class="nav-label"> Backup</span></a>
        <?php endif; ?>
        <?php if (Auth::can('users')): ?>
            <a class="nav-link <?= active_nav('users') ?>" href="<?= url('users') ?>" title="Users"><i class="bi bi-person-lock"></i><span class="nav-label"> Users</span></a>
        <?php endif; ?>
        <?php if (Auth::can('roles')): ?>
            <a class="nav-link <?= active_nav('roles-permissions') ?>" href="<?= url('roles-permissions') ?>" title="Roles &amp; Permissions"><i class="bi bi-shield-lock"></i><span class="nav-label"> Roles &amp; Permissions</span></a>
        <?php endif; ?>
        <?php if (Auth::can('settings')): ?>
            <a class="nav-link <?= active_nav('settings') ?>" href="<?= url('settings') ?>" title="Settings"><i class="bi bi-gear"></i><span class="nav-label"> Settings</span></a>
        <?php endif; ?>
    </nav>
</aside>
