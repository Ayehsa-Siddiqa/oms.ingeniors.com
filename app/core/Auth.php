<?php

class Auth
{
    public static function user(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }

        $stmt = Database::connection()->prepare(
            'SELECT users.*, roles.name AS role_name FROM users JOIN roles ON roles.id = users.role_id WHERE users.id = :id AND users.status = "Active"'
        );
        $stmt->execute(['id' => $_SESSION['user_id']]);
        return $stmt->fetch() ?: null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function login(string $email, string $password, bool $remember): bool
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE email = :email AND status = "Active" LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['last_activity'] = time();

        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $hash = hash('sha256', $token);
            $expires = date('Y-m-d H:i:s', time() + config('remember_lifetime'));
            $stmt = Database::connection()->prepare('UPDATE users SET remember_token = :token, remember_expires_at = :expires WHERE id = :id');
            $stmt->execute(['token' => $hash, 'expires' => $expires, 'id' => $user['id']]);
            setcookie('remember_oms', $user['id'] . ':' . $token, time() + config('remember_lifetime'), '/', '', false, true);
        }

        ActivityLogger::log((int) $user['id'], 'Login', 'Authentication');
        return true;
    }

    public static function attemptRememberLogin(): void
    {
        if (!empty($_SESSION['user_id']) || empty($_COOKIE['remember_oms'])) {
            return;
        }

        [$id, $token] = array_pad(explode(':', $_COOKIE['remember_oms'], 2), 2, '');
        if (!ctype_digit($id) || $token === '') {
            return;
        }

        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE id = :id AND remember_expires_at > NOW() LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        if ($user && hash_equals($user['remember_token'] ?? '', hash('sha256', $token))) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['last_activity'] = time();
        }
    }

    public static function logout(): void
    {
        if (!empty($_SESSION['user_id'])) {
            ActivityLogger::log((int) $_SESSION['user_id'], 'Logout', 'Authentication');
        }

        setcookie('remember_oms', '', time() - 3600, '/', '', false, true);
        $_SESSION = [];
        session_destroy();
    }

    public static function can(string $permission): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }

        // Super Admin always has full access
        if ($user['role_name'] === 'Super Admin') {
            return true;
        }

        // Normalize permission alias name to module key in permissions table
        $perm = match ($permission) {
            'pending-tasks' => 'pending_tasks',
            'employee-fines' => 'fines',
            'roles-permissions' => 'roles',
            'recycle-bin', 'backups' => 'backup',
            default => $permission,
        };

        // Check role_permissions dynamically from database
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM role_permissions rp
             JOIN permissions p ON p.id = rp.permission_id
             WHERE rp.role_id = :role_id AND p.module = :module'
        );
        $stmt->execute(['role_id' => $user['role_id'], 'module' => $perm]);
        $hasPerm = (int) $stmt->fetchColumn() > 0;

        // If Admin has specific permissions assigned, respect them; otherwise default allow
        if ($user['role_name'] === 'Admin') {
            $totalAdminPerms = (int) $db->query("SELECT COUNT(*) FROM role_permissions WHERE role_id = " . (int)$user['role_id'])->fetchColumn();
            if ($totalAdminPerms > 0) {
                return $hasPerm;
            }
            return true;
        }

        return $hasPerm;
    }
}
