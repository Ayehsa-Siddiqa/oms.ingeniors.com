<?php

class ActivityLogger
{
    public static function log(?int $userId, string $action, string $module): void
    {
        self::purgeOldLogs();
        try {
            $stmt = Database::connection()->prepare(
                'INSERT INTO activity_logs (user_id, action, module, ip_address, browser, created_at) VALUES (:user_id, :action, :module, :ip, :browser, NOW())'
            );
            $stmt->execute([
                'user_id' => $userId,
                'action' => $action,
                'module' => $module,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
                'browser' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255),
            ]);
        } catch (\Throwable $e) {
            try {
                $stmt = Database::connection()->prepare(
                    'INSERT INTO activity_logs (user_id, action, module, ip_address, created_at) VALUES (:user_id, :action, :module, :ip, NOW())'
                );
                $stmt->execute([
                    'user_id' => $userId,
                    'action' => $action,
                    'module' => $module,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
                ]);
            } catch (\Throwable $ignored) {}
        }
    }

    /**
     * Permanently delete logs older than 30 days
     */
    public static function purgeOldLogs(): void
    {
        try {
            Database::connection()->exec('DELETE FROM activity_logs WHERE created_at < NOW() - INTERVAL 30 DAY');
        } catch (\Throwable $e) {}
    }

    /**
     * Paginate activities from the last 30 days
     */
    public static function paginate(int $page = 1, int $perPage = 10): array
    {
        self::purgeOldLogs();
        $db = Database::connection();
        $countSql = "SELECT COUNT(*) FROM activity_logs WHERE created_at >= NOW() - INTERVAL 30 DAY";
        $total = (int) $db->query($countSql)->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = max(0, ($page - 1) * $perPage);

        $sql = "SELECT activity_logs.*, users.name 
                FROM activity_logs 
                LEFT JOIN users ON users.id = activity_logs.user_id 
                WHERE activity_logs.created_at >= NOW() - INTERVAL 30 DAY 
                ORDER BY activity_logs.created_at DESC 
                LIMIT {$perPage} OFFSET {$offset}";
        $rows = $db->query($sql)->fetchAll();

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'perPage' => $perPage,
        ];
    }
}
