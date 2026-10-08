<?php
/**
 * Database Migration Script
 * Run via: php database/migrate.php
 * OR visit: /database/migrate.php in browser (XAMPP)
 * 
 * Safe to run multiple times - checks if columns/tables exist first.
 */

// Load database config dynamically
$config = require __DIR__ . '/../app/config/database.php';

try {
    // Connect without dbname first to ensure we can recreate or verify database cleanly
    $pdoOptions = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 30,
    ];
    if (defined('PDO::MYSQL_ATTR_MULTI_STATEMENTS')) {
        $pdoOptions[PDO::MYSQL_ATTR_MULTI_STATEMENTS] = true;
    }
    $db = new PDO(
        "mysql:host={$config['host']};charset={$config['charset']}",
        $config['username'],
        $config['password'],
        $pdoOptions
    );
    $dbName = $config['database'];
    $db->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $db->exec("USE `{$dbName}`");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

$output = [];

function columnExists(PDO $db, string $table, string $column): bool
{
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->execute([$table, $column]);
        return (int) $stmt->fetchColumn() > 0;
    } catch (\PDOException $e) {
        return false;
    }
}

function runMigration(PDO $db, string $description, string $sql, array &$output): void
{
    try {
        $db->exec($sql);
        $output[] = "✅ {$description}";
    } catch (PDOException $e) {
        $output[] = "❌ {$description}: " . $e->getMessage();
    }
}

// ── 0. Check if base tables exist; if not, import schema.sql ────────────
$needsSchema = isset($_GET['force_schema']) || isset($_GET['reset']);
try {
    $count = (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count === 0) {
        $needsSchema = true;
    }
} catch (\PDOException $e) {
    $needsSchema = true;
}

if ($needsSchema) {
    $schemaFile = __DIR__ . '/schema.sql';
    if (file_exists($schemaFile)) {
        try {
            // Drop database and recreate to wipe corrupted/orphaned tablespace files
            $db->exec("DROP DATABASE IF EXISTS `{$dbName}`");
            $db->exec("CREATE DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $db->exec("USE `{$dbName}`");

            $rawSql = file_get_contents($schemaFile);
            
            // Execute statements sequentially for reliability
            $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
            
            // Clean comments before splitting
            $cleanedSql = preg_replace('!/\*.*?\*/!s', '', $rawSql);
            $cleanedSql = preg_replace('/^\s*--.*$/m', '', $cleanedSql);
            $cleanedSql = preg_replace('/^\s*#.*$/m', '', $cleanedSql);

            // Split SQL into individual statements
            $statements = array_filter(
                array_map('trim', preg_split('/;(?:\s*[\r\n]+|$)/', $cleanedSql)),
                fn($stmt) => !empty($stmt)
            );
            
            $importedCount = 0;
            $errors = [];
            foreach ($statements as $statement) {
                $trimmed = trim($statement);
                if ($trimmed !== '') {
                    try {
                        $db->exec($trimmed);
                        $importedCount++;
                    } catch (\PDOException $stmtErr) {
                        $errors[] = "Statement error: " . $stmtErr->getMessage() . "\nQuery: " . substr($trimmed, 0, 120);
                    }
                }
            }
            
            $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
            
            if ($errors !== []) {
                $output[] = "⚠️ Import had " . count($errors) . " errors:\n" . implode("\n", array_slice($errors, 0, 5));
            } else {
                $userCount = (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
                $output[] = "✅ Rebuilt database and imported {$importedCount} statements ({$userCount} users seeded).";
            }
        } catch (\PDOException $e) {
            $output[] = "❌ Failed to import schema.sql: " . $e->getMessage();
        }
    } else {
        $output[] = "⚠️ schema.sql not found at {$schemaFile}";
    }
}

// ── 1. Add deleted_at to all module tables ──────────────────────────────
$tables = ['projects', 'employees', 'departments', 'requirements', 'pending_tasks', 'employee_fines', 'documents', 'users'];

foreach ($tables as $table) {
    if (!columnExists($db, $table, 'deleted_at')) {
        runMigration($db, "Add deleted_at to {$table}", "ALTER TABLE `{$table}` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL", $output);
    } else {
        $output[] = "⏭️ deleted_at already exists on {$table}";
    }
}

// ── 2. Add source column to projects ────────────────────────────────────
if (!columnExists($db, 'projects', 'source')) {
    runMigration($db, "Add source to projects", "ALTER TABLE `projects` ADD COLUMN `source` VARCHAR(50) NULL DEFAULT NULL AFTER `status`", $output);
} else {
    $output[] = "⏭️ source already exists on projects";
}

// ── 3. Add assigned_to column to projects ───────────────────────────────
if (!columnExists($db, 'projects', 'assigned_to')) {
    runMigration($db, "Add assigned_to to projects", "ALTER TABLE `projects` ADD COLUMN `assigned_to` VARCHAR(100) NULL DEFAULT NULL AFTER `source`", $output);
} else {
    $output[] = "⏭️ assigned_to already exists on projects";
}

// ── 4. Add days_left (virtual) or delivery_date to projects ─────────────
if (!columnExists($db, 'projects', 'delivery_date')) {
    runMigration($db, "Add delivery_date to projects", "ALTER TABLE `projects` ADD COLUMN `delivery_date` DATE NULL DEFAULT NULL AFTER `deadline`", $output);
} else {
    $output[] = "⏭️ delivery_date already exists on projects";
}

// ── 5. Add browser column to activity_logs ─────────────────────────────
if (!columnExists($db, 'activity_logs', 'browser')) {
    runMigration($db, "Add browser to activity_logs", "ALTER TABLE `activity_logs` ADD COLUMN `browser` VARCHAR(255) NULL AFTER `ip_address`", $output);
} else {
    $output[] = "⏭️ browser already exists on activity_logs";
}

// ── 6. Add category column to projects ─────────────────────────────────
if (!columnExists($db, 'projects', 'category')) {
    runMigration($db, "Add category to projects", "ALTER TABLE `projects` ADD COLUMN `category` VARCHAR(50) NOT NULL DEFAULT 'In House' AFTER `project_name`", $output);
} else {
    $output[] = "⏭️ category already exists on projects";
}

// ── 8. Add announcements table ──────────────────────────────────────────
try {
    $db->exec("CREATE TABLE IF NOT EXISTS `announcements` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `content` TEXT NOT NULL,
        `type` VARCHAR(50) NOT NULL DEFAULT 'General',
        `start_date` DATE NOT NULL,
        `end_date` DATE NOT NULL,
        `created_by` INT UNSIGNED NULL,
        `deleted_at` DATETIME NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_announcements_dates` (`start_date`, `end_date`),
        INDEX `idx_announcements_created_at` (`created_at`),
        INDEX `idx_announcements_deleted_at` (`deleted_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $output[] = "✅ announcements table verified/created";

    // Add start_date and end_date if missing
    if (!columnExists($db, 'announcements', 'start_date')) {
        runMigration($db, "Add start_date to announcements", "ALTER TABLE `announcements` ADD COLUMN `start_date` DATE NOT NULL DEFAULT (CURRENT_DATE) AFTER `type`", $output);
    }
    if (!columnExists($db, 'announcements', 'end_date')) {
        runMigration($db, "Add end_date to announcements", "ALTER TABLE `announcements` ADD COLUMN `end_date` DATE NOT NULL DEFAULT (DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY)) AFTER `start_date`", $output);
    }

    // Seed default if table is empty
    $count = (int)$db->query("SELECT COUNT(*) FROM announcements")->fetchColumn();
    if ($count === 0) {
        $db->exec("INSERT INTO announcements (id, title, content, type, start_date, end_date, created_by) VALUES
            (1, 'All-Hands Engineering Review', 'Monthly review meeting scheduled this Friday at 3:00 PM. Please have your project progress sheets updated.', 'Important', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 1),
            (2, 'AutoCAD & Tekla Network Updates', 'Server maintenance will occur tonight at 11:00 PM. Save and close all local drawing files before leaving.', 'Urgent', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 1)");
        $output[] = "✅ Seeded default announcements";
    }
} catch (\Throwable $e) {
    $output[] = "⚠️ announcements table check: " . $e->getMessage();
}

// ── Output results ──────────────────────────────────────────────────────
if (php_sapi_name() === 'cli') {
    echo "\n=== OMS Database Migration ===\n\n";
    foreach ($output as $line) {
        echo $line . "\n";
    }
    echo "\nDone.\n";
} else {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>OMS Database Migration</title>";
    echo "<style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 2.5rem; background: #0b0f15; color: #f1f5f9; line-height: 1.6; }
        .card { max-width: 700px; margin: 0 auto; background: #121821; border: 1px solid #222c3c; border-radius: 12px; padding: 2rem; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        h1 { color: #4f8cff; font-size: 1.5rem; margin-top: 0; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 8px; }
        pre { background: #080b10; border: 1px solid #1e2638; border-radius: 8px; padding: 1.25rem; font-family: 'JetBrains Mono', monospace; font-size: 13px; line-height: 1.7; overflow-x: auto; }
        .ok { color: #10b981; }
        .err { color: #ef4444; }
        .skip { color: #f59e0b; }
        .actions { margin-top: 1.5rem; display: flex; gap: 1rem; }
        .btn { display: inline-block; padding: 0.6rem 1.2rem; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; }
        .btn-primary { background: #4f8cff; color: #fff; }
        .btn-primary:hover { background: #3d7ae8; }
        .btn-secondary { background: #1e2638; color: #cbd5e1; border: 1px solid #263347; }
        .btn-secondary:hover { background: #263347; color: #fff; }
    </style></head><body>";
    echo "<div class='card'>";
    echo "<h1>🛠️ OMS Database Setup & Migration</h1>";
    echo "<pre>";
    foreach ($output as $line) {
        $class = str_starts_with($line, '✅') ? 'ok' : (str_starts_with($line, '❌') ? 'err' : 'skip');
        echo "<span class='{$class}'>" . htmlspecialchars($line) . "</span>\n";
    }
    echo "</pre>";
    echo "<div class='actions'>";
    echo "<a href='../' class='btn btn-primary'>🚀 Open Application</a>";
    echo "<a href='?force_schema=1' class='btn btn-secondary' onclick=\"return confirm('Re-importing schema will reset all tables to default seed data. Proceed?')\">🔄 Re-import Full Schema</a>";
    echo "</div>";
    echo "</div></body></html>";
}
