<?php

$router->get('', [AuthController::class, 'loginForm']);
$router->get('login', [AuthController::class, 'loginForm']);
$router->post('login', [AuthController::class, 'login']);
$router->get('forgot-password', [AuthController::class, 'forgotForm']);
$router->post('forgot-password', [AuthController::class, 'forgot']);
$router->get('reset-password', [AuthController::class, 'resetForm']);
$router->post('reset-password', [AuthController::class, 'reset']);
$router->get('logout', [AuthController::class, 'logout']);
$router->get('public-dashboard', [PublicDashboardController::class, 'index']);

$router->get('dashboard', [DashboardController::class, 'index']);
$router->post('dashboard/project-status', [DashboardController::class, 'updateProjectStatus']);
$router->post('dashboard/task-status', [DashboardController::class, 'updateTaskStatus']);
$router->post('dashboard/purchase-status', [DashboardController::class, 'updatePurchaseStatus']);
$router->post('dashboard/announcements/create', [DashboardController::class, 'createAnnouncement']);
$router->post('dashboard/announcements/{id}/update', [DashboardController::class, 'updateAnnouncement']);
$router->post('dashboard/announcements/{id}/delete', [DashboardController::class, 'deleteAnnouncement']);
$router->get('roles-permissions', [RolesController::class, 'index']);
$router->post('roles-permissions/create', [RolesController::class, 'store']);
$router->post('roles-permissions/{id}/permissions', [RolesController::class, 'updatePermissions']);
$router->post('roles-permissions/{id}/delete', [RolesController::class, 'destroy']);
$router->get('profile', [ProfileController::class, 'index']);
$router->post('profile/password', [ProfileController::class, 'updatePassword']);
$router->get('reports', [ReportsController::class, 'index']);
$router->get('settings', [SettingsController::class, 'index']);
$router->post('settings', [SettingsController::class, 'update']);

// Backup / Recycle Bin routes
$router->get('backup', [ModuleController::class, 'recycleBin']);
$router->get('recycle-bin', [ModuleController::class, 'recycleBin']);

foreach (array_keys(require dirname(__DIR__) . '/app/config/modules.php') as $module) {
    $router->get($module, [ModuleController::class, 'index', $module]);
    $router->get($module . '/create', [ModuleController::class, 'create', $module]);
    $router->post($module, [ModuleController::class, 'store', $module]);
    $router->get($module . '/{id}/edit', [ModuleController::class, 'edit', $module]);
    $router->post($module . '/{id}/update', [ModuleController::class, 'update', $module]);
    $router->post($module . '/{id}/delete', [ModuleController::class, 'destroy', $module]);
    // Recycle bin: restore + permanent delete
    $router->post($module . '/{id}/restore', [ModuleController::class, 'restore', $module]);
    $router->post($module . '/{id}/permanent-delete', [ModuleController::class, 'permanentDelete', $module]);
}

