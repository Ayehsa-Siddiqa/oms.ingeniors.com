<?php

class BaseModel extends Model
{
    public function __construct(string $table)
    {
        parent::__construct();
        $this->table = $table;
        if ($this->table === 'projects') {
            $this->ensureProjectsColumns();
        }
    }

    private function ensureProjectsColumns(): void
    {
        static $checked = false;
        if ($checked) return;
        $checked = true;
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'projects' AND COLUMN_NAME = 'category'");
            $stmt->execute();
            if ((int)$stmt->fetchColumn() === 0) {
                $this->db->exec("ALTER TABLE `projects` ADD COLUMN `category` VARCHAR(50) NOT NULL DEFAULT 'In House' AFTER `project_name`");
            }

            $stmt2 = $this->db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'projects' AND COLUMN_NAME = 'source'");
            $stmt2->execute();
            if ((int)$stmt2->fetchColumn() === 0) {
                $this->db->exec("ALTER TABLE `projects` ADD COLUMN `source` VARCHAR(50) NOT NULL DEFAULT 'Direct' AFTER `description`");
            }
        } catch (\Throwable $e) {}
    }

    /**
     * Paginate active records (exclude soft-deleted)
     */
    public function paginate(array $searchFields, string $query, int $page, int $perPage, string $order): array
    {
        $where = $this->softDeleteFilter();
        $params = [];

        if ($query !== '' && $searchFields !== []) {
            $parts = [];
            foreach ($searchFields as $index => $field) {
                $key = "q{$index}";
                $parts[] = "{$field} LIKE :{$key}";
                $params[$key] = "%{$query}%";
            }
            $where .= ' AND (' . implode(' OR ', $parts) . ')';
        }

        $total = $this->count($where, $params);
        $offset = max(0, ($page - 1) * $perPage);
        $sql = "SELECT * FROM {$this->table} WHERE {$where} ORDER BY {$order} LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [
            'rows' => $stmt->fetchAll(),
            'total' => $total,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    /**
     * Paginate soft-deleted records (recycle bin)
     */
    public function paginateTrashed(array $searchFields, string $query, int $page, int $perPage, string $order): array
    {
        if (!$this->hasSoftDelete()) {
            return [
                'rows' => [],
                'total' => 0,
                'pages' => 1,
            ];
        }

        $where = 'deleted_at IS NOT NULL';
        $params = [];

        if ($query !== '' && $searchFields !== []) {
            $parts = [];
            foreach ($searchFields as $index => $field) {
                $key = "q{$index}";
                $parts[] = "{$field} LIKE :{$key}";
                $params[$key] = "%{$query}%";
            }
            $where .= ' AND (' . implode(' OR ', $parts) . ')';
        }

        $total = $this->count($where, $params);
        $offset = max(0, ($page - 1) * $perPage);
        $sql = "SELECT * FROM {$this->table} WHERE {$where} ORDER BY deleted_at DESC LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [
            'rows' => $stmt->fetchAll(),
            'total' => $total,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    /**
     * Ensure soft-delete columns exist on all supported tables
     */
    public static function ensureSoftDeleteColumns(): void
    {
        static $checked = false;
        if ($checked) return;
        $checked = true;

        $db = Database::connection();
        $tables = ['projects', 'pending_tasks', 'requirements', 'employee_fines', 'documents', 'employees', 'users', 'announcements'];
        foreach ($tables as $table) {
            try {
                $hasCol = (int) $db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$table}' AND COLUMN_NAME = 'deleted_at'")->fetchColumn();
                if ($hasCol === 0) {
                    $db->exec("ALTER TABLE `{$table}` ADD COLUMN `deleted_at` DATETIME NULL");
                }
            } catch (\Throwable $e) {}
        }
    }

    /**
     * Run all automated archiving and permanent purge rules across modules
     */
    public static function runGlobalAutoMaintenance(): void
    {
        static $executed = false;
        if ($executed) return;
        $executed = true;

        self::ensureSoftDeleteColumns();
        $db = Database::connection();

        // 1. Projects:
        // - Delivered/Finished projects auto-archive to backup after 7 days
        // - Permanently deleted from DB after 1 month (30 days) from backup
        try {
            $db->exec("UPDATE projects 
                SET deleted_at = NOW() 
                WHERE status IN ('Delivered', 'Finished', 'Completed') 
                  AND deleted_at IS NULL 
                  AND (
                      (delivery_date IS NOT NULL AND delivery_date <= DATE_SUB(CURDATE(), INTERVAL 7 DAY))
                      OR 
                      (delivery_date IS NULL AND updated_at <= DATE_SUB(NOW(), INTERVAL 7 DAY))
                  )");

            $db->exec("DELETE FROM projects 
                WHERE deleted_at IS NOT NULL 
                  AND deleted_at <= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        } catch (\Throwable $e) {}

        // 2. Requirements (Purchase Requests):
        // - Purchased/Received items auto-move to backup
        // - Permanently deleted from DB after 7 days from backup
        try {
            $db->exec("UPDATE requirements 
                SET deleted_at = NOW() 
                WHERE status IN ('Purchased', 'Received') 
                  AND deleted_at IS NULL");

            $db->exec("DELETE FROM requirements 
                WHERE deleted_at IS NOT NULL 
                  AND deleted_at <= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        } catch (\Throwable $e) {}

        // 3. Pending Tasks:
        // - Completed tasks auto-archive to backup after 7 days
        // - Permanently deleted from DB after 30 days from backup
        try {
            $db->exec("UPDATE pending_tasks 
                SET deleted_at = NOW() 
                WHERE status = 'Completed' 
                  AND deleted_at IS NULL 
                  AND updated_at <= DATE_SUB(NOW(), INTERVAL 7 DAY)");

            $db->exec("DELETE FROM pending_tasks 
                WHERE deleted_at IS NOT NULL 
                  AND deleted_at <= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        } catch (\Throwable $e) {}

        // 4. Employee Fines:
        // - Paid fines auto-move to backup
        // - Permanently deleted from DB after 1 month (30 days) from backup
        try {
            $db->exec("UPDATE employee_fines 
                SET deleted_at = NOW() 
                WHERE (paid = 'Yes' OR LOWER(paid) = 'yes') 
                  AND deleted_at IS NULL");

            $db->exec("DELETE FROM employee_fines 
                WHERE deleted_at IS NOT NULL 
                  AND deleted_at <= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        } catch (\Throwable $e) {}

        // 5. Documents:
        // - Soft-deleted documents permanently deleted from DB after 7 days
        try {
            $db->exec("DELETE FROM documents 
                WHERE deleted_at IS NOT NULL 
                  AND deleted_at <= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        } catch (\Throwable $e) {}

        // 6. Announcements:
        // - Permanently purge expired announcements
        try {
            $db->exec("DELETE FROM announcements 
                WHERE end_date IS NOT NULL 
                  AND end_date < CURDATE()");
        } catch (\Throwable $e) {}
    }

    /**
     * Auto-archive projects marked Delivered or Finished
     */
    public function autoArchiveDeliveredProjects(): void
    {
        self::runGlobalAutoMaintenance();
    }

    /**
     * Auto-archive tasks marked Completed
     */
    public function autoArchiveCompletedTasks(): void
    {
        self::runGlobalAutoMaintenance();
    }

    /**
     * Auto-archive requirements marked Purchased or Received
     */
    public function autoArchivePurchasedRequirements(): void
    {
        self::runGlobalAutoMaintenance();
    }

    public function getTableColumns(): array
    {
        static $columnCache = [];
        if (!isset($columnCache[$this->table])) {
            try {
                $stmt = $this->db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
                $stmt->execute([$this->table]);
                $columnCache[$this->table] = $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
            } catch (\PDOException $e) {
                $columnCache[$this->table] = [];
            }
        }
        return $columnCache[$this->table];
    }

    private function filterDataToColumns(array $data): array
    {
        $cols = $this->getTableColumns();
        if (empty($cols)) {
            return $data;
        }
        return array_intersect_key($data, array_flip($cols));
    }

    public function create(array $data): int
    {
        $filtered = $this->filterDataToColumns($data);
        if (empty($filtered)) {
            return 0;
        }
        $columns = array_keys($filtered);
        $names = implode(', ', $columns);
        $binds = ':' . implode(', :', $columns);
        $stmt = $this->db->prepare("INSERT INTO {$this->table} ({$names}) VALUES ({$binds})");
        $stmt->execute($filtered);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $filtered = $this->filterDataToColumns($data);
        if (empty($filtered)) {
            return false;
        }
        $sets = [];
        foreach (array_keys($filtered) as $column) {
            $sets[] = "{$column} = :{$column}";
        }
        $filtered['id'] = $id;
        $stmt = $this->db->prepare("UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = :id");
        return $stmt->execute($filtered);
    }

    public function all(string $order = 'id DESC'): array
    {
        return $this->db->query("SELECT * FROM {$this->table} WHERE {$this->softDeleteFilter()} ORDER BY {$order}")->fetchAll();
    }

    /**
     * Check if the table has a deleted_at column
     */
    private function hasSoftDelete(): bool
    {
        static $cache = [];
        if (!isset($cache[$this->table])) {
            try {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'deleted_at'");
                $stmt->execute([$this->table]);
                $cache[$this->table] = (int) $stmt->fetchColumn() > 0;
            } catch (\PDOException $e) {
                $cache[$this->table] = false;
            }
        }
        return $cache[$this->table];
    }

    /**
     * Return SQL condition to filter out soft-deleted records
     */
    private function softDeleteFilter(): string
    {
        return $this->hasSoftDelete() ? 'deleted_at IS NULL' : '1=1';
    }
}
