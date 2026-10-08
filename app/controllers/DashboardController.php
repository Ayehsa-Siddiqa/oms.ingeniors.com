<?php

class DashboardController extends Controller
{
    public function index(): void
    {
        AuthMiddleware::requirePermission('dashboard');
        $db = Database::connection();

        // Run auto-archiving
        (new BaseModel('projects'))->autoArchiveDeliveredProjects();
        (new BaseModel('pending_tasks'))->autoArchiveCompletedTasks();
        (new BaseModel('requirements'))->autoArchivePurchasedRequirements();

        // Helper to check soft delete
        $hasSoftDelete = function(string $table) use ($db): bool {
            static $cache = [];
            if (!isset($cache[$table])) {
                try {
                    $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'deleted_at'");
                    $stmt->execute([$table]);
                    $cache[$table] = (int) $stmt->fetchColumn() > 0;
                } catch (\PDOException $e) {
                    $cache[$table] = false;
                }
            }
            return $cache[$table];
        };

        $projDel = $hasSoftDelete('projects') ? ' AND deleted_at IS NULL' : '';
        $empDel = $hasSoftDelete('employees') ? ' AND deleted_at IS NULL' : '';
        $reqDel = $hasSoftDelete('requirements') ? ' AND deleted_at IS NULL' : '';
        $taskDel = $hasSoftDelete('pending_tasks') ? ' AND deleted_at IS NULL' : '';

        $pProjDel = $hasSoftDelete('projects') ? ' AND p.deleted_at IS NULL' : '';
        $perPageProjects = 10;
        $perPageWidget = 5;

        // 1. Recent Projects (10 per page)
        $projPage = max(1, (int) ($_GET['proj_page'] ?? 1));
        $projTotal = (int) $db->query("SELECT COUNT(*) FROM projects WHERE status NOT IN ('Delivered', 'Finished'){$projDel}")->fetchColumn();
        $projPages = max(1, (int) ceil($projTotal / $perPageProjects));
        $projPage = max(1, min($projPage, $projPages));
        $projOffset = max(0, ($projPage - 1) * $perPageProjects);
        $recentProjectsQuery = "SELECT p.*, GROUP_CONCAT(e.name ORDER BY e.name ASC SEPARATOR ', ') AS assigned_names FROM projects p LEFT JOIN project_assignments pa ON pa.project_id = p.id LEFT JOIN employees e ON e.id = pa.employee_id WHERE p.status NOT IN ('Delivered', 'Finished'){$pProjDel} GROUP BY p.id ORDER BY p.updated_at DESC LIMIT {$perPageProjects} OFFSET {$projOffset}";
        $recentProjects = [
            'rows' => $db->query($recentProjectsQuery)->fetchAll(),
            'total' => $projTotal,
            'page' => $projPage,
            'pages' => $projPages,
        ];

        // 2. Pending Tasks (5 per page)
        $taskPage = max(1, (int) ($_GET['task_page'] ?? 1));
        $taskTotal = (int) $db->query("SELECT COUNT(*) FROM pending_tasks WHERE 1=1{$taskDel}")->fetchColumn();
        $taskPages = max(1, (int) ceil($taskTotal / $perPageWidget));
        $taskPage = max(1, min($taskPage, $taskPages));
        $taskOffset = max(0, ($taskPage - 1) * $perPageWidget);
        $pendingTasks = [
            'rows' => $db->query("SELECT * FROM pending_tasks WHERE 1=1{$taskDel} ORDER BY CASE WHEN status = 'Completed' THEN 1 ELSE 0 END ASC, required_date ASC, created_at DESC LIMIT {$perPageWidget} OFFSET {$taskOffset}")->fetchAll(),
            'total' => $taskTotal,
            'page' => $taskPage,
            'pages' => $taskPages,
        ];

        // 3. Purchase Requests (5 per page)
        $purPage = max(1, (int) ($_GET['pur_page'] ?? 1));
        $purTotal = (int) $db->query("SELECT COUNT(*) FROM requirements WHERE 1=1{$reqDel}")->fetchColumn();
        $purPages = max(1, (int) ceil($purTotal / $perPageWidget));
        $purPage = max(1, min($purPage, $purPages));
        $purOffset = max(0, ($purPage - 1) * $perPageWidget);
        $pendingPurchases = [
            'rows' => $db->query("SELECT * FROM requirements WHERE 1=1{$reqDel} ORDER BY required_date ASC, created_at DESC LIMIT {$perPageWidget} OFFSET {$purOffset}")->fetchAll(),
            'total' => $purTotal,
            'page' => $purPage,
            'pages' => $purPages,
        ];

        // 4. In-House Projects (for progress widget)
        $inHouseProjects = $db->query("SELECT * FROM projects WHERE (category = 'In House' OR LOWER(category) = 'in house') AND status NOT IN ('Delivered', 'Finished'){$projDel} ORDER BY updated_at DESC, deadline ASC")->fetchAll();

        // 5. Announcements (with auto-purge for expired announcements)
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS `announcements` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `title` VARCHAR(255) NOT NULL,
                `content` TEXT NOT NULL,
                `type` VARCHAR(50) NOT NULL DEFAULT 'General',
                `start_date` DATE NOT NULL DEFAULT (CURRENT_DATE),
                `end_date` DATE NOT NULL DEFAULT (DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY)),
                `created_by` INT UNSIGNED NULL,
                `deleted_at` DATETIME NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_announcements_dates` (`start_date`, `end_date`),
                INDEX `idx_announcements_created_at` (`created_at`),
                INDEX `idx_announcements_deleted_at` (`deleted_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // Ensure columns if table existed previously without start_date / end_date
            $hasStart = (int) $db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'announcements' AND COLUMN_NAME = 'start_date'")->fetchColumn();
            if ($hasStart === 0) {
                $db->exec("ALTER TABLE `announcements` ADD COLUMN `start_date` DATE NOT NULL DEFAULT (CURRENT_DATE) AFTER `type`");
            }
            $hasEnd = (int) $db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'announcements' AND COLUMN_NAME = 'end_date'")->fetchColumn();
            if ($hasEnd === 0) {
                $db->exec("ALTER TABLE `announcements` ADD COLUMN `end_date` DATE NOT NULL DEFAULT (DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY)) AFTER `start_date`");
            }
            $hasUpdatedBy = (int) $db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'announcements' AND COLUMN_NAME = 'updated_by'")->fetchColumn();
            if ($hasUpdatedBy === 0) {
                $db->exec("ALTER TABLE `announcements` ADD COLUMN `updated_by` INT UNSIGNED NULL AFTER `created_by`");
            }

            // PERMANENT AUTO-DELETE: Purge all announcements whose end_date is in the past
            $db->exec("DELETE FROM announcements WHERE end_date IS NOT NULL AND end_date < CURDATE()");
        } catch (\Throwable $e) {}

        $announcements = [];
        try {
            $announcements = $db->query("SELECT a.*, u.name as author_name FROM announcements a LEFT JOIN users u ON u.id = a.created_by WHERE a.deleted_at IS NULL AND a.start_date <= CURDATE() AND (a.end_date >= CURDATE() OR a.end_date IS NULL) ORDER BY a.created_at DESC LIMIT 15")->fetchAll();
        } catch (\Throwable $e) {}

        $showAnnouncementToast = (flash('show_announcement_toast') !== null);

        // 6. Recent Activity (Last 30 Days auto-purged, 10 per page)
        $actPage = max(1, (int) ($_GET['act_page'] ?? 1));
        $activities = ActivityLogger::paginate($actPage, 10);

        $data = [
            'title' => 'Dashboard',
            'projects' => (int) $db->query("SELECT COUNT(*) FROM projects WHERE 1=1{$projDel}")->fetchColumn(),
            'employees' => (int) $db->query("SELECT COUNT(*) FROM employees WHERE status = 'Active'{$empDel}")->fetchColumn(),
            'requirements' => (int) $db->query("SELECT COUNT(*) FROM requirements WHERE status IN ('Pending', 'Ordered'){$reqDel}")->fetchColumn(),
            'completed' => (int) $db->query("SELECT COUNT(*) FROM projects WHERE status IN ('Delivered', 'Finished'){$projDel}")->fetchColumn(),
            'overdue' => (int) $db->query("SELECT COUNT(*) FROM projects WHERE deadline < CURDATE() AND status NOT IN ('Delivered', 'Finished'){$projDel}")->fetchColumn(),
            'recentProjects' => $recentProjects,
            'pendingPurchases' => $pendingPurchases,
            'pendingTasks' => $pendingTasks,
            'inHouseProjects' => $inHouseProjects,
            'announcements' => $announcements,
            'showAnnouncementToast' => $showAnnouncementToast,
            'activities' => $activities,
            'statusRows' => $db->query("SELECT status, COUNT(*) total FROM projects WHERE 1=1{$projDel} GROUP BY status")->fetchAll(),
        ];
        $this->view('dashboard/index', $data);
    }

    public function updateProjectStatus(): void
    {
        verify_csrf();
        AuthMiddleware::requireAuth();
        if (!Auth::can('projects') && !Auth::can('dashboard')) {
            AuthMiddleware::requirePermission('projects');
        }
        $projectId = (int) ($_POST['project_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $allowed = ['Not Started', 'Pending', 'In Progress', 'In Review', 'On Hold', 'Waiting on Client', 'Revision', 'Completed', 'Delivered', 'Finished'];

        $user = Auth::user();
        $isAdmin = in_array($user['role_name'] ?? '', ['Super Admin', 'Admin'], true);
        if (!$isAdmin) {
            $empStmt = $db->prepare('SELECT id FROM employees WHERE email = :email LIMIT 1');
            $empStmt->execute(['email' => $user['email'] ?? '']);
            $empId = (int)$empStmt->fetchColumn();
            $isAssigned = $empId > 0 && (int)$db->query("SELECT COUNT(*) FROM project_assignments WHERE project_id = {$projectId} AND employee_id = {$empId}")->fetchColumn() > 0;
            if (!$isAssigned) {
                flash('error', 'You can only update status for projects assigned to you.');
                $this->redirect($_SERVER['HTTP_REFERER'] ?? 'projects');
            }
        }

        if ($projectId <= 0 || !in_array($status, $allowed, true)) {
            flash('error', 'Invalid project status update.');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? 'projects');
        }

        $isDelivered = in_array($status, ['Delivered', 'Finished', 'Completed'], true);
        try {
            $stmt = Database::connection()->prepare('UPDATE projects SET status = :status, delivery_date = CASE WHEN :is_del = 1 THEN COALESCE(delivery_date, CURDATE()) ELSE NULL END, updated_by = :updated_by WHERE id = :id');
            $stmt->execute([
                'status' => $status,
                'is_del' => $isDelivered ? 1 : 0,
                'updated_by' => Auth::user()['id'] ?? null,
                'id' => $projectId,
            ]);
        } catch (\Throwable $e) {
            $stmt = Database::connection()->prepare('UPDATE projects SET status = :status, updated_by = :updated_by WHERE id = :id');
            $stmt->execute([
                'status' => $status,
                'updated_by' => Auth::user()['id'] ?? null,
                'id' => $projectId,
            ]);
        }
        ActivityLogger::log(Auth::user()['id'] ?? null, 'Status Update #' . $projectId . ' to ' . $status, 'Projects');
        flash('success', 'Project status updated.');
        $this->redirect($_SERVER['HTTP_REFERER'] ?? 'projects');
    }

    public function updateTaskStatus(): void
    {
        verify_csrf();
        AuthMiddleware::requireAuth();
        if (!Auth::can('pending_tasks') && !Auth::can('dashboard')) {
            AuthMiddleware::requirePermission('pending_tasks');
        }
        $taskId = (int) ($_POST['task_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $allowed = ['Pending', 'In Progress', 'Completed'];

        $db = Database::connection();
        $record = $db->query("SELECT created_by FROM pending_tasks WHERE id = " . $taskId)->fetch();
        if ($record && (Auth::user()['role_name'] ?? '') === 'User') {
            if ($record['created_by'] !== (Auth::user()['id'] ?? 0)) {
                flash('error', 'You do not have permission to modify this task.');
                $this->redirect($_SERVER['HTTP_REFERER'] ?? 'pending-tasks');
            }
        }

        if ($taskId <= 0 || !in_array($status, $allowed, true)) {
            flash('error', 'Invalid task status update.');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? 'pending-tasks');
        }

        $stmt = Database::connection()->prepare('UPDATE pending_tasks SET status = :status, updated_at = NOW(), updated_by = :updated_by WHERE id = :id');
        $stmt->execute([
            'status' => $status,
            'updated_by' => Auth::user()['id'] ?? null,
            'id' => $taskId,
        ]);
        ActivityLogger::log(Auth::user()['id'] ?? null, 'Task #' . $taskId . ' status updated to ' . $status, 'Pending Tasks');
        flash('success', 'Task status updated.');
        $this->redirect($_SERVER['HTTP_REFERER'] ?? 'pending-tasks');
    }

    public function updatePurchaseStatus(): void
    {
        verify_csrf();
        AuthMiddleware::requireAuth();
        if (!Auth::can('requirements') && !Auth::can('dashboard')) {
            AuthMiddleware::requirePermission('requirements');
        }
        $purchaseId = (int) ($_POST['purchase_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $allowed = ['Pending', 'Ordered', 'Purchased', 'Received'];

        $db = Database::connection();
        $record = $db->query("SELECT created_by FROM requirements WHERE id = " . $purchaseId)->fetch();
        if ($record && (Auth::user()['role_name'] ?? '') === 'User') {
            if ($record['created_by'] !== (Auth::user()['id'] ?? 0)) {
                flash('error', 'You do not have permission to modify this requirement.');
                $this->redirect($_SERVER['HTTP_REFERER'] ?? 'requirements');
            }
        }

        if ($purchaseId <= 0 || !in_array($status, $allowed, true)) {
            flash('error', 'Invalid purchase status update.');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? 'requirements');
        }

        if (in_array($status, ['Purchased', 'Received'], true)) {
            $stmt = Database::connection()->prepare('UPDATE requirements SET status = :status, deleted_at = NOW(), updated_at = NOW(), updated_by = :updated_by WHERE id = :id');
            $stmt->execute([
                'status' => $status,
                'updated_by' => Auth::user()['id'] ?? null,
                'id' => $purchaseId,
            ]);
            ActivityLogger::log(Auth::user()['id'] ?? null, 'Purchase Request #' . $purchaseId . ' marked ' . $status . ' (Moved to Backup)', 'Requirements');
            flash('success', 'Purchase request marked ' . $status . ' and moved to Backup.');
        } else {
            $stmt = Database::connection()->prepare('UPDATE requirements SET status = :status, deleted_at = NULL, updated_at = NOW(), updated_by = :updated_by WHERE id = :id');
            $stmt->execute([
                'status' => $status,
                'updated_by' => Auth::user()['id'] ?? null,
                'id' => $purchaseId,
            ]);
            ActivityLogger::log(Auth::user()['id'] ?? null, 'Purchase Request #' . $purchaseId . ' status updated to ' . $status, 'Requirements');
            flash('success', 'Purchase request status updated.');
        }
        $this->redirect($_SERVER['HTTP_REFERER'] ?? 'requirements');
    }

    public function createAnnouncement(): void
    {
        verify_csrf();
        AuthMiddleware::requirePermission('dashboard');
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $type = trim($_POST['type'] ?? 'General');
        $startDate = !empty($_POST['start_date']) ? trim($_POST['start_date']) : date('Y-m-d');
        $endDate = !empty($_POST['end_date']) ? trim($_POST['end_date']) : date('Y-m-d', strtotime('+7 days'));

        if ($title === '' || $content === '') {
            flash('error', 'Announcement title and message are required.');
            $this->redirect('dashboard#announcementsBox');
        }

        if ($endDate < $startDate) {
            flash('error', 'End date cannot be earlier than start date.');
            $this->redirect('dashboard#announcementsBox');
        }

        $stmt = Database::connection()->prepare('INSERT INTO announcements (title, content, type, start_date, end_date, created_by) VALUES (:title, :content, :type, :start_date, :end_date, :created_by)');
        $stmt->execute([
            'title' => $title,
            'content' => $content,
            'type' => $type,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'created_by' => Auth::user()['id'] ?? null,
        ]);
        ActivityLogger::log(Auth::user()['id'] ?? null, 'Created Announcement: ' . $title, 'Announcements');
        flash('success', 'Announcement published successfully.');
        $this->redirect('dashboard#announcementsBox');
    }

    public function updateAnnouncement(string $id): void
    {
        verify_csrf();
        AuthMiddleware::requirePermission('dashboard');
        $intId = (int) $id;
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $type = trim($_POST['type'] ?? 'General');
        $startDate = !empty($_POST['start_date']) ? trim($_POST['start_date']) : date('Y-m-d');
        $endDate = !empty($_POST['end_date']) ? trim($_POST['end_date']) : date('Y-m-d', strtotime('+7 days'));

        if ($intId <= 0 || $title === '' || $content === '') {
            flash('error', 'Invalid announcement update.');
            $this->redirect('dashboard#announcementsBox');
        }

        $db = Database::connection();
        $record = $db->query("SELECT created_by FROM announcements WHERE id = " . $intId)->fetch();
        if ($record && (Auth::user()['role_name'] ?? '') === 'User') {
            if ($record['created_by'] !== (Auth::user()['id'] ?? 0)) {
                flash('error', 'You do not have permission to modify this announcement.');
                $this->redirect('dashboard#announcementsBox');
            }
        }

        if ($endDate < $startDate) {
            flash('error', 'End date cannot be earlier than start date.');
            $this->redirect('dashboard#announcementsBox');
        }

        $stmt = Database::connection()->prepare('UPDATE announcements SET title = :title, content = :content, type = :type, start_date = :start_date, end_date = :end_date, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'title' => $title,
            'content' => $content,
            'type' => $type,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'id' => $intId,
        ]);
        ActivityLogger::log(Auth::user()['id'] ?? null, 'Updated Announcement #' . $intId, 'Announcements');
        flash('success', 'Announcement updated.');
        $this->redirect('dashboard#announcementsBox');
    }

    public function deleteAnnouncement(string $id): void
    {
        verify_csrf();
        AuthMiddleware::requirePermission('dashboard');
        $intId = (int) $id;
        if ($intId <= 0) {
            flash('error', 'Invalid announcement deletion.');
            $this->redirect('dashboard#announcementsBox');
        }

        $db = Database::connection();
        $record = $db->query("SELECT created_by FROM announcements WHERE id = " . $intId)->fetch();
        if ($record && (Auth::user()['role_name'] ?? '') === 'User') {
            if ($record['created_by'] !== (Auth::user()['id'] ?? 0)) {
                flash('error', 'You do not have permission to delete this announcement.');
                $this->redirect('dashboard#announcementsBox');
            }
        }

        $stmt = Database::connection()->prepare('DELETE FROM announcements WHERE id = :id');
        $stmt->execute(['id' => $intId]);
        ActivityLogger::log(Auth::user()['id'] ?? null, 'Permanently Deleted Announcement #' . $intId, 'Announcements');
        flash('success', 'Announcement permanently deleted.');
        $this->redirect('dashboard#announcementsBox');
    }
}
