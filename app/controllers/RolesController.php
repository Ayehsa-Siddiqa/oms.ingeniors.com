<?php

class RolesController extends Controller
{
    // All modules/permissions that can be toggled
    private array $allModules = [
        'dashboard'    => 'Dashboard',
        'projects'     => 'Projects',
        'employees'    => 'Employees',
        'departments'  => 'Departments',
        'requirements' => 'Requirements',
        'pending_tasks'=> 'Pending Tasks',
        'fines'        => 'Employee Fines',
        'documents'    => 'Documents',
        'announcements'=> 'Announcements',
        'backup'       => 'Backup',
        'reports'      => 'Reports',
        'settings'     => 'Settings',
        'users'        => 'Users',
        'roles'        => 'Roles & Permissions',
        'profile'      => 'Profile',
    ];

    public function index(): void
    {
        AuthMiddleware::requirePermission('roles');

        $db = Database::connection();

        // All roles
        $roles = $db->query('SELECT * FROM roles ORDER BY id ASC')->fetchAll();

        // Ensure all permissions exist in DB
        $this->ensurePermissionsExist($db);

        // Ensure Admin has default operational permissions if none exist yet
        try {
            $adminRole = $db->query("SELECT id FROM roles WHERE name = 'Admin'")->fetch();
            if ($adminRole) {
                $adminId = (int)$adminRole['id'];
                $hasAdminPerms = (int)$db->query("SELECT COUNT(*) FROM role_permissions WHERE role_id = $adminId")->fetchColumn();
                if ($hasAdminPerms === 0) {
                    $db->exec("
                        INSERT IGNORE INTO role_permissions (role_id, permission_id)
                        SELECT $adminId, id FROM permissions WHERE module NOT IN ('settings', 'roles')
                    ");
                }
            }
        } catch (\Throwable $e) {}

        // Build: role_id => [module => bool]
        $rolePerms = [];
        foreach ($roles as $role) {
            $rolePerms[$role['id']] = [];
            foreach ($this->allModules as $module => $label) {
                $rolePerms[$role['id']][$module] = false;
            }
        }

        $rows = $db->query(
            'SELECT rp.role_id, p.module FROM role_permissions rp JOIN permissions p ON p.id = rp.permission_id'
        )->fetchAll();

        foreach ($rows as $row) {
            if (isset($rolePerms[$row['role_id']][$row['module']])) {
                $rolePerms[$row['role_id']][$row['module']] = true;
            }
        }

        // User counts per role
        $userCounts = [];
        $uc = $db->query('SELECT role_id, COUNT(*) as cnt FROM users WHERE deleted_at IS NULL GROUP BY role_id')->fetchAll();
        foreach ($uc as $row) {
            $userCounts[$row['role_id']] = (int)$row['cnt'];
        }

        $this->view('roles/index', [
            'title'      => 'Roles & Permissions',
            'roles'      => $roles,
            'allModules' => $this->allModules,
            'rolePerms'  => $rolePerms,
            'userCounts' => $userCounts,
        ]);
    }

    public function store(): void
    {
        verify_csrf();
        AuthMiddleware::requirePermission('roles');

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($name === '') {
            flash('error', 'Role name is required.');
            $this->redirect('roles-permissions');
        }

        $db = Database::connection();
        $stmt = $db->prepare('SELECT id FROM roles WHERE name = :name');
        $stmt->execute(['name' => $name]);
        if ($stmt->fetch()) {
            flash('error', 'A role with this name already exists.');
            $this->redirect('roles-permissions');
        }

        $stmt = $db->prepare(
            'INSERT INTO roles (name, description, created_by, updated_by) VALUES (:name, :desc, :by, :by2)'
        );
        $stmt->execute([
            'name' => $name,
            'desc' => $description,
            'by'   => Auth::user()['id'] ?? null,
            'by2'  => Auth::user()['id'] ?? null,
        ]);

        ActivityLogger::log(Auth::user()['id'] ?? null, 'Created Role: ' . $name, 'Roles');
        flash('success', 'Role "' . $name . '" created successfully.');
        $this->redirect('roles-permissions');
    }

    public function updatePermissions(string $roleId): void
    {
        verify_csrf();
        AuthMiddleware::requirePermission('roles');

        $db = Database::connection();
        $intId = (int) $roleId;

        $role = $db->query("SELECT name FROM roles WHERE id = $intId")->fetch();
        if (!$role) {
            flash('error', 'Role not found.');
            $this->redirect('roles-permissions');
        }
        if ($role['name'] === 'Super Admin') {
            flash('error', 'Super Admin permissions cannot be modified.');
            $this->redirect('roles-permissions');
        }

        $this->ensurePermissionsExist($db);

        $selected = is_array($_POST['modules'] ?? null) ? $_POST['modules'] : [];

        // Clear old permissions
        $db->prepare('DELETE FROM role_permissions WHERE role_id = :role_id')->execute(['role_id' => $intId]);

        // Insert selected
        if (!empty($selected)) {
            $placeholders = implode(',', array_fill(0, count($selected), '?'));
            $stmt = $db->prepare("SELECT id FROM permissions WHERE module IN ($placeholders)");
            $stmt->execute($selected);
            $permIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

            $ins = $db->prepare('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?,?)');
            foreach ($permIds as $pid) {
                $ins->execute([$intId, $pid]);
            }
        }

        ActivityLogger::log(Auth::user()['id'] ?? null, 'Updated Permissions for Role: ' . $role['name'], 'Roles');
        flash('success', 'Permissions updated for "' . $role['name'] . '".');
        $this->redirect('roles-permissions');
    }

    public function destroy(string $roleId): void
    {
        verify_csrf();
        AuthMiddleware::requirePermission('roles');

        $db = Database::connection();
        $intId = (int) $roleId;

        $role = $db->query("SELECT name FROM roles WHERE id = $intId")->fetch();
        if (!$role) {
            flash('error', 'Role not found.');
            $this->redirect('roles-permissions');
        }

        if ($role['name'] === 'Super Admin') {
            flash('error', 'Super Admin role cannot be deleted.');
            $this->redirect('roles-permissions');
        }

        $used = (int)$db->query("SELECT COUNT(*) FROM users WHERE role_id = $intId AND deleted_at IS NULL")->fetchColumn();
        if ($used > 0) {
            flash('error', 'Cannot delete "' . $role['name'] . '" — it is currently assigned to ' . $used . ' user(s). Please reassign them in the Users tab first.');
            $this->redirect('roles-permissions');
        }

        // Permanently remove role permissions and role from DB (no backup)
        $db->prepare('DELETE FROM role_permissions WHERE role_id = :id')->execute(['id' => $intId]);
        $db->prepare('DELETE FROM roles WHERE id = :id')->execute(['id' => $intId]);
        ActivityLogger::log(Auth::user()['id'] ?? null, 'Permanently Deleted Role: ' . $role['name'], 'Roles');
        flash('success', 'Role "' . $role['name'] . '" permanently deleted from database.');
        $this->redirect('roles-permissions');
    }

    private function ensurePermissionsExist(\PDO $db): void
    {
        foreach (array_keys($this->allModules) as $module) {
            $stmt = $db->prepare('INSERT IGNORE INTO permissions (module, action) VALUES (:module, :action)');
            $stmt->execute(['module' => $module, 'action' => 'manage']);
        }
    }
}
