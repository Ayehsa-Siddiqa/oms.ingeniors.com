<?php

class ModuleController extends Controller
{
    private array $modules;

    public function __construct()
    {
        $this->modules = require dirname(__DIR__) . '/config/modules.php';
    }

    public function index(string $module): void
    {
        $config = $this->moduleConfig($module);
        AuthMiddleware::requirePermission($config['permission']);

        BaseModel::runGlobalAutoMaintenance();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $query = trim($_GET['q'] ?? '');
        if ($module === 'projects') {
            $pageActive = max(1, (int) ($_GET['page_active'] ?? 1));
            $pageCompleted = max(1, (int) ($_GET['page_completed'] ?? 1));
            $activeProjects = $this->projectSectionRows($config, $query, false, $pageActive, 10);
            $completedProjects = $this->projectSectionRows($config, $query, true, $pageCompleted, 10);
            $this->view('modules/projects_index', compact('module', 'config', 'query', 'activeProjects', 'completedProjects') + ['title' => $config['title']]);
            return;
        }
        if ($module === 'announcements') {
            try {
                $db = Database::connection();
                $hasUpdatedBy = (int) $db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'announcements' AND COLUMN_NAME = 'updated_by'")->fetchColumn();
                if ($hasUpdatedBy === 0) {
                    $db->exec("ALTER TABLE `announcements` ADD COLUMN `updated_by` INT UNSIGNED NULL AFTER `created_by`");
                }
                $db->exec("DELETE FROM announcements WHERE end_date IS NOT NULL AND end_date < CURDATE()");
            } catch (\Throwable $e) {}
        }
        $model = new BaseModel($config['table']);
        $result = $model->paginate($config['search'], $query, $page, 10, $config['order']);
        // For users module: enrich rows with role_name from JOIN
        if ($module === 'users') {
            $db = Database::connection();
            foreach ($result['rows'] as &$row) {
                $stmt = $db->prepare('SELECT name FROM roles WHERE id = :id');
                $stmt->execute(['id' => $row['role_id'] ?? 0]);
                $role = $stmt->fetch();
                $row['role_name'] = $role ? $role['name'] : '—';
            }
            unset($row);
        }
        $this->view('modules/index', compact('module', 'config', 'result', 'page', 'query') + ['title' => $config['title']]);
    }

    public function create(string $module): void
    {
        $config = $this->moduleConfig($module);
        AuthMiddleware::requirePermission($config['permission']);

        // Project module: Only Super Admin and Admin can create projects
        if ($module === 'projects' || $config['table'] === 'projects') {
            if (!in_array(Auth::user()['role_name'] ?? '', ['Super Admin', 'Admin'], true)) {
                flash('error', 'Only administrators can create projects.');
                $this->redirect('projects');
            }
        }

        $record = [];
        $employees = in_array($module, ['projects', 'pending-tasks'], true) ? $this->employeesForAssignment() : [];
        $assignedEmployeeIds = [];
        $roles = $module === 'users' ? $this->allRoles() : [];
        $departments = ($module === 'employees' || in_array('department_select', array_column($config['fields'], 'type'), true) || in_array('departments_select', array_column($config['fields'], 'type'), true)) ? $this->allDepartments() : [];
        $this->view('modules/form', compact('module', 'config', 'record', 'employees', 'assignedEmployeeIds', 'roles', 'departments') + ['title' => 'Create ' . $config['title']]);
    }

    public function store(string $module): void
    {
        verify_csrf();
        $config = $this->moduleConfig($module);
        AuthMiddleware::requirePermission($config['permission']);

        // Project module: Only Super Admin and Admin can create projects
        if ($module === 'projects' || $config['table'] === 'projects') {
            if (!in_array(Auth::user()['role_name'] ?? '', ['Super Admin', 'Admin'], true)) {
                flash('error', 'Only administrators can create projects.');
                $this->redirect('projects');
            }
        }

        $data = $this->validatedData($config);
        if ($module === 'pending-tasks') {
            $selectedIds = array_filter(array_map('intval', $_POST['employee_ids'] ?? []));
            $assignedNames = [];
            if (!empty($selectedIds)) {
                $inQuery = implode(',', array_fill(0, count($selectedIds), '?'));
                $stmt = Database::connection()->prepare("SELECT name FROM employees WHERE id IN ($inQuery) ORDER BY name ASC");
                $stmt->execute($selectedIds);
                $assignedNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }
            $data['assigned_to'] = !empty($assignedNames) ? implode(', ', $assignedNames) : null;
        }
        if ($config['table'] === 'users' && empty($data['password'])) {
            flash('error', 'Password is required for new users.');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? 'users');
        }
        if ($config['table'] === 'users') {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        $data['created_by'] = Auth::user()['id'] ?? null;
        $data['updated_by'] = Auth::user()['id'] ?? null;
        if ($module === 'projects') {
            if (in_array($data['status'] ?? '', ['Delivered', 'Finished', 'Completed'], true)) {
                $data['delivery_date'] = date('Y-m-d');
            }
        }
        if ($module === 'employee-fines' || $config['table'] === 'employee_fines') {
            if (($data['paid'] ?? '') === 'Yes' || strtolower((string)($data['paid'] ?? '')) === 'yes') {
                $data['deleted_at'] = date('Y-m-d H:i:s');
            }
        }
        if ($module === 'requirements' || $config['table'] === 'requirements') {
            if (in_array($data['status'] ?? '', ['Purchased', 'Received'], true)) {
                $data['deleted_at'] = date('Y-m-d H:i:s');
            }
        }
        $id = (new BaseModel($config['table']))->create($data);

        // When a user is added with Employee role, auto-add to employees table
        if ($config['table'] === 'users' || $module === 'users') {
            $this->syncUserToEmployeeOnCreate($data);
        }

        if ($module === 'projects') {
            $this->syncProjectAssignments($id, $_POST['employee_ids'] ?? []);
        }
        ActivityLogger::log(Auth::user()['id'] ?? null, 'Create #' . $id, $config['title']);
        if (($data['deleted_at'] ?? null) !== null) {
            flash('success', $config['title'] . ' record created and moved to backup.');
        } else {
            flash('success', $config['title'] . ' record created.');
        }
        $this->redirect($module);
    }

    public function edit(string $module, string $id): void
    {
        $config = $this->moduleConfig($module);
        AuthMiddleware::requirePermission($config['permission']);
        $record = (new BaseModel($config['table']))->find((int) $id);
        if (!$record) {
            $this->redirect($module);
        }

        // Project module: Employees can only edit projects assigned to them
        if ($module === 'projects' || $config['table'] === 'projects') {
            if (!in_array(Auth::user()['role_name'] ?? '', ['Super Admin', 'Admin'], true)) {
                $empId = $this->currentEmployeeId();
                $assignedIds = $this->assignedEmployeeIds((int) $id);
                if (!$empId || !in_array($empId, $assignedIds, true)) {
                    flash('error', 'You can only edit projects assigned to you.');
                    $this->redirect('projects');
                }
            }
        }

        $employees = in_array($module, ['projects', 'pending-tasks'], true) ? $this->employeesForAssignment() : [];
        $assignedEmployeeIds = [];
        if ($module === 'projects') {
            $assignedEmployeeIds = $this->assignedEmployeeIds((int) $id);
        } elseif ($module === 'pending-tasks') {
            $assignedNames = array_filter(array_map('trim', explode(',', (string)($record['assigned_to'] ?? ''))));
            foreach ($employees as $emp) {
                if (in_array($emp['name'], $assignedNames, true)) {
                    $assignedEmployeeIds[] = (int)$emp['id'];
                }
            }
        }
        $roles = $module === 'users' ? $this->allRoles() : [];
        $departments = ($module === 'employees' || in_array('department_select', array_column($config['fields'], 'type'), true) || in_array('departments_select', array_column($config['fields'], 'type'), true)) ? $this->allDepartments() : [];
        $this->view('modules/form', compact('module', 'config', 'record', 'employees', 'assignedEmployeeIds', 'roles', 'departments') + ['title' => 'Edit ' . $config['title']]);
    }

    public function update(string $module, string $id): void
    {
        verify_csrf();
        $config = $this->moduleConfig($module);
        AuthMiddleware::requirePermission($config['permission']);
        $record = (new BaseModel($config['table']))->find((int) $id);
        if (!$record) {
            $this->redirect($module);
        }

        // Project module: Employees can only update projects assigned to them
        if ($module === 'projects' || $config['table'] === 'projects') {
            if (!in_array(Auth::user()['role_name'] ?? '', ['Super Admin', 'Admin'], true)) {
                $empId = $this->currentEmployeeId();
                $assignedIds = $this->assignedEmployeeIds((int) $id);
                if (!$empId || !in_array($empId, $assignedIds, true)) {
                    flash('error', 'You can only edit projects assigned to you.');
                    $this->redirect('projects');
                }
            }
        }

        $data = $this->validatedData($config);
        if ($module === 'pending-tasks') {
            $selectedIds = array_filter(array_map('intval', $_POST['employee_ids'] ?? []));
            $assignedNames = [];
            if (!empty($selectedIds)) {
                $inQuery = implode(',', array_fill(0, count($selectedIds), '?'));
                $stmt = Database::connection()->prepare("SELECT name FROM employees WHERE id IN ($inQuery) ORDER BY name ASC");
                $stmt->execute($selectedIds);
                $assignedNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }
            $data['assigned_to'] = !empty($assignedNames) ? implode(', ', $assignedNames) : null;
        }
        if ($config['table'] === 'employees' || $module === 'employees') {
            // Email is unchangeable on employee table — preserve existing email
            if (!empty($record['email'])) {
                $data['email'] = $record['email'];
            }
        }
        if ($config['table'] === 'users') {
            if (empty($data['password'])) {
                unset($data['password']);
            } else {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
        }
        $data['updated_by'] = Auth::user()['id'] ?? null;
        if ($module === 'projects') {
            if (in_array($data['status'] ?? '', ['Delivered', 'Finished', 'Completed'], true)) {
                $existing = (new BaseModel('projects'))->find((int)$id);
                $data['delivery_date'] = $existing['delivery_date'] ?? date('Y-m-d');
            } else {
                $data['delivery_date'] = null;
            }
        }
        if ($module === 'employee-fines' || $config['table'] === 'employee_fines') {
            if (($data['paid'] ?? '') === 'Yes' || strtolower((string)($data['paid'] ?? '')) === 'yes') {
                $data['deleted_at'] = date('Y-m-d H:i:s');
            }
        }
        if ($module === 'requirements' || $config['table'] === 'requirements') {
            if (in_array($data['status'] ?? '', ['Purchased', 'Received'], true)) {
                $data['deleted_at'] = date('Y-m-d H:i:s');
            }
        }
        (new BaseModel($config['table']))->update((int) $id, $data);

        // When user is updated in user tab, auto-sync email and name to employee table
        if ($config['table'] === 'users' || $module === 'users') {
            $this->syncUserToEmployeeOnUpdate($record, $data);
        }

        // When employee is updated in employee tab, auto-sync name to users table
        if ($config['table'] === 'employees' || $module === 'employees') {
            $this->syncEmployeeToUserOnUpdate($record, $data);
        }

        if ($module === 'projects') {
            $this->syncProjectAssignments((int) $id, $_POST['employee_ids'] ?? []);
        }
        ActivityLogger::log(Auth::user()['id'] ?? null, 'Update #' . $id, $config['title']);
        if (($data['deleted_at'] ?? null) !== null) {
            flash('success', $config['title'] . ' record updated and moved to backup.');
        } else {
            flash('success', $config['title'] . ' record updated.');
        }
        $this->redirect($module);
    }

    /**
     * Soft delete — moves record to recycle bin (or permanently deletes for departments)
     */
    public function destroy(string $module, string $id): void
    {
        verify_csrf();
        $config = $this->moduleConfig($module);
        AuthMiddleware::requirePermission($config['permission']);
        $record = (new BaseModel($config['table']))->find((int) $id);
        if (!$record) {
            $this->redirect($module);
        }

        // Project module: Only Super Admin and Admin can delete projects
        if ($module === 'projects' || $config['table'] === 'projects') {
            if (!in_array(Auth::user()['role_name'] ?? '', ['Super Admin', 'Admin'], true)) {
                flash('error', 'Only administrators can delete projects.');
                $this->redirect('projects');
            }
        }

        // Departments, Announcements, Roles & Permissions: permanently deleted immediately from DB, never moved to backup
        $excludedFromBackup = ['departments', 'announcements', 'roles', 'permissions'];
        if (in_array($module, $excludedFromBackup, true) || in_array($config['table'], $excludedFromBackup, true)) {
            (new BaseModel($config['table']))->delete((int) $id);
            ActivityLogger::log(Auth::user()['id'] ?? null, 'Permanent Delete ' . $config['title'] . ' #' . $id, $config['title']);
            flash('success', $config['title'] . ' record permanently deleted.');
            $this->redirect($module);
            return;
        }

        (new BaseModel($config['table']))->softDelete((int) $id);

        if ($module === 'users' || $config['table'] === 'users') {
            if (!empty($record['email'])) {
                try {
                    $db = Database::connection();
                    $stmt = $db->prepare('UPDATE employees SET deleted_at = NOW() WHERE email = :email AND deleted_at IS NULL');
                    $stmt->execute(['email' => $record['email']]);
                } catch (\Throwable $e) {}
            }
        }

        ActivityLogger::log(Auth::user()['id'] ?? null, 'Soft Delete #' . $id, $config['title']);
        flash('success', $config['title'] . ' record moved to backup.');
        $this->redirect($module);
    }

    /**
     * Recycle Bin — show soft-deleted records for modules (excluding departments, announcements, roles, and permissions)
     */
    public function recycleBin(): void
    {
        AuthMiddleware::requirePermission('backup');
        BaseModel::runGlobalAutoMaintenance();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $query = trim($_GET['q'] ?? '');
        $activeTab = $_GET['tab'] ?? 'projects';
        $excludedFromBackup = ['departments', 'announcements', 'roles', 'permissions'];
        if (in_array($activeTab, $excludedFromBackup, true)) {
            $activeTab = 'projects';
        }

        $tabs = [];
        foreach ($this->modules as $slug => $config) {
            if (in_array($slug, $excludedFromBackup, true) || in_array($config['table'], $excludedFromBackup, true)) {
                continue;
            }
            $model = new BaseModel($config['table']);
            $result = $model->paginateTrashed($config['search'], $query, $page, 10, $config['order']);
            $tabs[$slug] = [
                'config' => $config,
                'result' => $result,
            ];
        }

        $this->view('modules/recycle_bin', compact('tabs', 'activeTab', 'page', 'query') + ['title' => 'Recycle Bin']);
    }

    /**
     * Restore a soft-deleted record from recycle bin
     */
    public function restore(string $module, string $id): void
    {
        verify_csrf();
        $config = $this->moduleConfig($module);
        AuthMiddleware::requirePermission($config['permission']);

        $db = Database::connection();
        $intId = (int) $id;

        $stmt = $db->prepare("SELECT * FROM {$config['table']} WHERE id = ?");
        $stmt->execute([$intId]);
        $record = $stmt->fetch();
        if ($record && (Auth::user()['role_name'] ?? '') === 'Employee') {
            if (($record['created_by'] ?? null) !== (Auth::user()['id'] ?? 0)) {
                flash('error', 'You do not have permission to modify this record.');
                $this->redirect('recycle-bin?tab=' . $module);
            }
        }

        // Restore (clears deleted_at)
        (new BaseModel($config['table']))->restore($intId);

        // Auto-set status on restore
        if ($config['table'] === 'requirements') {
            // Purchase requests: reset to Pending on restore
            try {
                $stmt = $db->prepare("UPDATE requirements SET status = 'Pending', updated_at = NOW() WHERE id = :id");
                $stmt->execute(['id' => $intId]);
            } catch (\Throwable $e) {}
            flash('success', 'Purchase request restored and status reset to Pending.');
        } elseif ($config['table'] === 'projects') {
            // Projects: reset to In Progress and clear delivery_date on restore
            try {
                $stmt = $db->prepare("UPDATE projects SET status = 'In Progress', delivery_date = NULL, updated_at = NOW() WHERE id = :id");
                $stmt->execute(['id' => $intId]);
            } catch (\Throwable $e) {
                // Fallback without delivery_date column
                try {
                    $stmt = $db->prepare("UPDATE projects SET status = 'In Progress', updated_at = NOW() WHERE id = :id");
                    $stmt->execute(['id' => $intId]);
                } catch (\Throwable $e2) {}
            }
            flash('success', 'Project restored and status reset to In Progress.');
        } elseif ($config['table'] === 'employee_fines') {
            // Employee fines: reset paid to No on restore
            try {
                $stmt = $db->prepare("UPDATE employee_fines SET paid = 'No', updated_at = NOW() WHERE id = :id");
                $stmt->execute(['id' => $intId]);
            } catch (\Throwable $e) {}
            flash('success', 'Employee fine restored and status reset to Unpaid.');
        } elseif ($config['table'] === 'pending_tasks') {
            // Pending tasks: reset status to In Progress on restore
            try {
                $stmt = $db->prepare("UPDATE pending_tasks SET status = 'In Progress', updated_at = NOW() WHERE id = :id");
                $stmt->execute(['id' => $intId]);
            } catch (\Throwable $e) {}
            flash('success', 'Task restored and status reset to In Progress.');
        } elseif ($config['table'] === 'users') {
            if (!empty($record['email'])) {
                try {
                    $stmt = $db->prepare("UPDATE employees SET deleted_at = NULL WHERE email = :email");
                    $stmt->execute(['email' => $record['email']]);
                } catch (\Throwable $e) {}
            }
            flash('success', 'User account restored from backup.');
        } else {
            flash('success', $config['title'] . ' record restored from backup.');
        }

        ActivityLogger::log(Auth::user()['id'] ?? null, 'Restore #' . $id, $config['title']);
        $this->redirect('recycle-bin?tab=' . $module);
    }

    /**
     * Permanently delete from recycle bin
     */
    public function permanentDelete(string $module, string $id): void
    {
        verify_csrf();
        $config = $this->moduleConfig($module);
        AuthMiddleware::requirePermission($config['permission']);

        $db = Database::connection();
        $stmt = $db->prepare("SELECT * FROM {$config['table']} WHERE id = ?");
        $stmt->execute([(int) $id]);
        $record = $stmt->fetch();
        if ($record && (Auth::user()['role_name'] ?? '') === 'Employee') {
            if (($record['created_by'] ?? null) !== (Auth::user()['id'] ?? 0)) {
                flash('error', 'You do not have permission to modify this record.');
                $this->redirect('recycle-bin?tab=' . $module);
            }
        }
        if ((Auth::user()['role_name'] ?? '') === 'Employee') {
            flash('error', 'Employees cannot permanently delete records.');
            $this->redirect('recycle-bin?tab=' . $module);
        }
        (new BaseModel($config['table']))->delete((int) $id);
        ActivityLogger::log(Auth::user()['id'] ?? null, 'Permanent Delete #' . $id, $config['title']);
        flash('success', $config['title'] . ' record permanently deleted.');
        $this->redirect('recycle-bin?tab=' . $module);
    }

    private function moduleConfig(string $module): array
    {
        if (!isset($this->modules[$module])) {
            http_response_code(404);
            exit('Module not found.');
        }
        return $this->modules[$module];
    }

    private function validatedData(array $config): array
    {
        $data = [];
        foreach ($config['fields'] as $name => $field) {
            // Virtual fields are display-only — never saved to DB
            if (($field['type'] ?? '') === 'virtual') {
                continue;
            }
            if (($field['type'] ?? '') === 'file') {
                $data[$name] = $this->handleUpload($name, $_POST['existing_' . $name] ?? null);
                continue;
            }

            $value = trim((string) ($_POST[$name] ?? ''));
            if (!empty($field['required']) && $value === '') {
                flash('error', $field['label'] . ' is required.');
                $this->redirect($_SERVER['HTTP_REFERER'] ?? 'dashboard');
            }
            if (($field['type'] ?? '') === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                flash('error', 'Enter a valid email address.');
                $this->redirect($_SERVER['HTTP_REFERER'] ?? 'dashboard');
            }
            $data[$name] = $value;
        }
        return $data;
    }

    private function handleUpload(string $field, ?string $existing): ?string
    {
        if (empty($_FILES[$field]['name'])) {
            return $existing;
        }
        if ($_FILES[$field]['size'] > config('max_upload_size')) {
            flash('error', 'File is too large.');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? 'dashboard');
        }
        $extension = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, config('allowed_uploads'), true)) {
            flash('error', 'File type is not allowed.');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? 'dashboard');
        }
        $uploadDir = rtrim(config('upload_path'), '/\\');
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }
        $name = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
        $destination = $uploadDir . DIRECTORY_SEPARATOR . $name;
        move_uploaded_file($_FILES[$field]['tmp_name'], $destination);
        return $name;
    }

    private function employeesForAssignment(): array
    {
        $db = Database::connection();
        try {
            // Auto-sync active users into employees table so all users/employees can be assigned
            $users = $db->query("SELECT u.id, u.name, u.email, u.status, r.name as role_name 
                                 FROM users u 
                                 LEFT JOIN roles r ON r.id = u.role_id 
                                 WHERE u.status = 'Active' AND (u.deleted_at IS NULL)")->fetchAll();

            foreach ($users as $u) {
                $email = trim((string)($u['email'] ?? ''));
                if ($email === '') {
                    continue;
                }
                $chk = $db->prepare("SELECT id, deleted_at, status FROM employees WHERE email = :email LIMIT 1");
                $chk->execute(['email' => $email]);
                $existing = $chk->fetch();

                if (!$existing) {
                    $ins = $db->prepare("INSERT INTO employees (employee_code, name, department, designation, email, joining_date, status, created_by, updated_by) 
                                         VALUES (:code, :name, :dept, :desig, :email, :joining_date, 'Active', :created_by, :updated_by)");
                    $roleLabel = !empty($u['role_name']) ? $u['role_name'] : 'User';
                    $deptLabel = (strcasecmp($roleLabel, 'Super Admin') === 0 || strcasecmp($roleLabel, 'Admin') === 0) ? 'Management' : $this->defaultDepartmentForEmployee();
                    $ins->execute([
                        'code' => $this->generateNextEmployeeCode(),
                        'name' => trim((string)$u['name']),
                        'dept' => $deptLabel,
                        'desig' => $roleLabel,
                        'email' => $email,
                        'joining_date' => date('Y-m-d'),
                        'created_by' => Auth::user()['id'] ?? null,
                        'updated_by' => Auth::user()['id'] ?? null,
                    ]);
                } elseif (!empty($existing['deleted_at']) || $existing['status'] !== 'Active') {
                    $upd = $db->prepare("UPDATE employees SET deleted_at = NULL, status = 'Active', name = :name WHERE id = :id");
                    $upd->execute([
                        'name' => trim((string)$u['name']),
                        'id' => (int)$existing['id']
                    ]);
                }
            }
        } catch (\Throwable $e) {
            error_log('Error syncing users to employees in employeesForAssignment: ' . $e->getMessage());
        }

        return $db->query('SELECT id, name, designation, department, email FROM employees WHERE status = "Active" AND deleted_at IS NULL ORDER BY name ASC')
            ->fetchAll();
    }

    private function allRoles(): array
    {
        return Database::connection()
            ->query('SELECT id, name FROM roles ORDER BY name ASC')
            ->fetchAll();
    }

    private function allDepartments(): array
    {
        try {
            $stmt = Database::connection()->query('SELECT id, department_name, status FROM departments WHERE deleted_at IS NULL ORDER BY department_name ASC');
            $rows = $stmt->fetchAll();
            $active = array_filter($rows, fn($r) => empty($r['status']) || strtolower($r['status']) === 'active');
            return !empty($active) ? array_values($active) : $rows;
        } catch (\Throwable $e) {
            try {
                return Database::connection()
                    ->query('SELECT id, department_name FROM departments ORDER BY department_name ASC')
                    ->fetchAll();
            } catch (\Throwable $e2) {
                return [];
            }
        }
    }

    private function assignedEmployeeIds(int $projectId): array
    {
        $stmt = Database::connection()->prepare('SELECT pa.employee_id FROM project_assignments pa JOIN employees e ON e.id = pa.employee_id WHERE pa.project_id = :project_id AND e.deleted_at IS NULL AND e.status = "Active"');
        $stmt->execute(['project_id' => $projectId]);
        return array_map('intval', array_column($stmt->fetchAll(), 'employee_id'));
    }

    private function syncProjectAssignments(int $projectId, array $employeeIds): void
    {
        $db = Database::connection();
        $ids = array_values(array_unique(array_filter(array_map('intval', $employeeIds))));
        $stmt = $db->prepare('DELETE FROM project_assignments WHERE project_id = :project_id');
        $stmt->execute(['project_id' => $projectId]);

        if ($ids === []) {
            return;
        }

        $stmt = $db->prepare('INSERT INTO project_assignments (project_id, employee_id) VALUES (:project_id, :employee_id)');
        foreach ($ids as $employeeId) {
            $stmt->execute(['project_id' => $projectId, 'employee_id' => $employeeId]);
        }
    }

    private function projectSectionRows(array $config, string $query, bool $completed, int $page = 1, int $perPage = 10): array
    {
        $where = $completed
            ? 'projects.status IN ("Delivered", "Finished")'
            : 'projects.status NOT IN ("Delivered", "Finished")';

        // Exclude soft-deleted
        $where .= ' AND projects.deleted_at IS NULL';

        $params = [];

        if ($query !== '') {
            $parts = [];
            foreach ($config['search'] as $index => $field) {
                $key = "q{$index}";
                $parts[] = "projects.{$field} LIKE :{$key}";
                $params[$key] = "%{$query}%";
            }
            $where .= ' AND (' . implode(' OR ', $parts) . ')';
        }

        $countStmt = Database::connection()->prepare("SELECT COUNT(*) FROM projects WHERE {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = max(0, ($page - 1) * $perPage);

        $order = $completed ? 'projects.updated_at DESC' : 'projects.deadline ASC';
        $stmt = Database::connection()->prepare(
            "SELECT projects.*,
                    GROUP_CONCAT(e.name ORDER BY e.name ASC SEPARATOR ', ') AS assigned_names,
                    GROUP_CONCAT(e.id ORDER BY e.id ASC SEPARATOR ',') AS assigned_employee_ids
             FROM projects
             LEFT JOIN project_assignments pa ON pa.project_id = projects.id
             LEFT JOIN employees e ON e.id = pa.employee_id
             WHERE {$where}
             GROUP BY projects.id
             ORDER BY {$order}
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);

        return [
            'rows' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'perPage' => $perPage,
        ];
    }

    private function currentEmployeeId(): ?int
    {
        $user = Auth::user();
        if (!$user || empty($user['email'])) {
            return null;
        }
        $stmt = Database::connection()->prepare('SELECT id FROM employees WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $user['email']]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (int)$val : null;
    }

    private function generateNextEmployeeCode(): string
    {
        $db = Database::connection();
        $codes = $db->query("SELECT employee_code FROM employees WHERE employee_code LIKE 'EMP-%'")->fetchAll(PDO::FETCH_COLUMN);
        $maxNum = 0;
        foreach ($codes as $code) {
            if (preg_match('/EMP-(\d+)/i', (string)$code, $matches)) {
                $num = (int)$matches[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }
        $nextNum = max($maxNum + 1, (int)$db->query("SELECT COUNT(*) FROM employees")->fetchColumn() + 1);
        do {
            $candidate = sprintf('EMP-%03d', $nextNum);
            $stmt = $db->prepare("SELECT COUNT(*) FROM employees WHERE employee_code = :code");
            $stmt->execute(['code' => $candidate]);
            if ((int)$stmt->fetchColumn() === 0) {
                return $candidate;
            }
            $nextNum++;
        } while (true);
    }

    private function defaultDepartmentForEmployee(): string
    {
        try {
            $depts = $this->allDepartments();
            if (!empty($depts[0]['department_name'])) {
                return (string)$depts[0]['department_name'];
            }
        } catch (\Throwable $e) {}
        return 'General';
    }

    private function syncUserToEmployeeOnCreate(array $userData): void
    {
        $email = trim((string)($userData['email'] ?? ''));
        $name = trim((string)($userData['name'] ?? ''));
        $roleId = (int)($userData['role_id'] ?? 0);
        $status = $userData['status'] ?? 'Active';
        if ($email === '' || $roleId === 0) {
            return;
        }

        try {
            $db = Database::connection();
            $roleStmt = $db->prepare('SELECT name FROM roles WHERE id = :id');
            $roleStmt->execute(['id' => $roleId]);
            $roleName = trim((string)$roleStmt->fetchColumn());

            if (strcasecmp($roleName, 'Employee') !== 0) {
                return;
            }

            // Check if employee with this email already exists
            $stmt = $db->prepare('SELECT id, deleted_at FROM employees WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => $email]);
            $existing = $stmt->fetch();

            if ($existing) {
                $upd = $db->prepare('UPDATE employees SET name = :name, status = :status, deleted_at = NULL, updated_by = :uid WHERE id = :id');
                $upd->execute([
                    'name' => $name,
                    'status' => $status,
                    'uid' => Auth::user()['id'] ?? null,
                    'id' => (int)$existing['id'],
                ]);
            } else {
                $ins = $db->prepare('INSERT INTO employees (employee_code, name, department, designation, email, joining_date, status, created_by, updated_by) VALUES (:code, :name, :dept, :desig, :email, :joining_date, :status, :created_by, :updated_by)');
                $ins->execute([
                    'code' => $this->generateNextEmployeeCode(),
                    'name' => $name,
                    'dept' => $this->defaultDepartmentForEmployee(),
                    'desig' => 'Employee',
                    'email' => $email,
                    'joining_date' => date('Y-m-d'),
                    'status' => $status,
                    'created_by' => Auth::user()['id'] ?? null,
                    'updated_by' => Auth::user()['id'] ?? null,
                ]);
            }
        } catch (\Throwable $e) {
            error_log('Failed to sync user to employee on create: ' . $e->getMessage());
        }
    }

    private function syncUserToEmployeeOnUpdate(array $oldRecord, array $newData): void
    {
        $oldEmail = trim((string)($oldRecord['email'] ?? ''));
        $newEmail = trim((string)($newData['email'] ?? $oldEmail));
        $newName = trim((string)($newData['name'] ?? ($oldRecord['name'] ?? '')));
        $newStatus = $newData['status'] ?? ($oldRecord['status'] ?? 'Active');
        $roleId = (int)($newData['role_id'] ?? ($oldRecord['role_id'] ?? 0));

        if ($newEmail === '') {
            return;
        }

        try {
            $db = Database::connection();
            $roleStmt = $db->prepare('SELECT name FROM roles WHERE id = :id');
            $roleStmt->execute(['id' => $roleId]);
            $roleName = trim((string)$roleStmt->fetchColumn());
            $isEmployeeRole = (strcasecmp($roleName, 'Employee') === 0);

            // Find employee by old or new email
            $stmt = $db->prepare('SELECT id FROM employees WHERE email = :old_email OR email = :new_email LIMIT 1');
            $stmt->execute(['old_email' => $oldEmail, 'new_email' => $newEmail]);
            $existing = $stmt->fetch();

            if ($existing) {
                // Auto update email, name, status in employee table
                $upd = $db->prepare('UPDATE employees SET email = :new_email, name = :new_name, status = :status, updated_by = :uid WHERE id = :id');
                $upd->execute([
                    'new_email' => $newEmail,
                    'new_name' => $newName,
                    'status' => $newStatus,
                    'uid' => Auth::user()['id'] ?? null,
                    'id' => (int)$existing['id'],
                ]);
            } elseif ($isEmployeeRole) {
                // If role was set to Employee and no employee record exists yet, auto-create
                $ins = $db->prepare('INSERT INTO employees (employee_code, name, department, designation, email, joining_date, status, created_by, updated_by) VALUES (:code, :name, :dept, :desig, :email, :joining_date, :status, :created_by, :updated_by)');
                $ins->execute([
                    'code' => $this->generateNextEmployeeCode(),
                    'name' => $newName,
                    'dept' => $this->defaultDepartmentForEmployee(),
                    'desig' => 'Employee',
                    'email' => $newEmail,
                    'joining_date' => date('Y-m-d'),
                    'status' => $newStatus,
                    'created_by' => Auth::user()['id'] ?? null,
                    'updated_by' => Auth::user()['id'] ?? null,
                ]);
            }
        } catch (\Throwable $e) {
            error_log('Failed to sync user to employee on update: ' . $e->getMessage());
        }
    }

    private function syncEmployeeToUserOnUpdate(array $oldRecord, array $newData): void
    {
        $email = trim((string)($newData['email'] ?? ($oldRecord['email'] ?? '')));
        $newName = trim((string)($newData['name'] ?? ''));

        if ($email === '' || $newName === '') {
            return;
        }

        try {
            $db = Database::connection();
            $stmt = $db->prepare('UPDATE users SET name = :name, updated_by = :uid, updated_at = NOW() WHERE email = :email AND deleted_at IS NULL');
            $stmt->execute([
                'name'  => $newName,
                'uid'   => Auth::user()['id'] ?? null,
                'email' => $email,
            ]);
        } catch (\Throwable $e) {
            error_log('Failed to sync employee to user on update: ' . $e->getMessage());
        }
    }
}
