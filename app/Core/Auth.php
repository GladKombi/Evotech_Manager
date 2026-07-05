<?php

namespace App\Core;

class Auth
{
    private const PUBLIC_PATHS = ['/', 'login', 'logout'];

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function requireAuth(): void
    {
        if (!self::check()) Helpers::redirect('login');
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function roles(): array
    {
        $roles = self::user()['roles_list'] ?? [];

        if ($roles === [] && !empty(self::user()['utilisateur_id'])) {
            $roles = self::loadRoles((int) self::user()['utilisateur_id']);
            $_SESSION['user']['roles_list'] = $roles;
            $_SESSION['user']['roles'] = implode(', ', $roles);
        }

        if (is_string($roles)) {
            $roles = array_filter(array_map('trim', explode(',', $roles)));
        }

        return is_array($roles) ? $roles : [];
    }

    public static function can(string $path): bool
    {
        $path = trim($path, '/') ?: '/';

        if (in_array($path, self::PUBLIC_PATHS, true)) {
            return true;
        }

        if (!self::check()) {
            return false;
        }

        $permissions = self::permissions();
        $module = self::moduleFromPath($path);

        foreach (self::roles() as $role) {
            $key = self::normalizeRole($role);
            $allowed = $permissions[$key] ?? [];

            if (in_array('*', $allowed, true) || in_array($module, $allowed, true)) {
                return true;
            }
        }

        return false;
    }

    public static function requireAccess(string $path): void
    {
        self::requireAuth();

        if (!self::can($path)) {
            self::deny();
        }
    }

    public static function deny(): void
    {
        http_response_code(403);
        require dirname(__DIR__) . '/Views/errors/403.php';
        exit;
    }

    public static function moduleFromPath(string $path): string
    {
        $path = trim($path, '/') ?: '/';

        if ($path === 'records/update') {
            return trim((string) ($_POST['_module'] ?? ''), '/') ?: 'records';
        }

        return explode('/', $path)[0] ?: '/';
    }

    private static function permissions(): array
    {
        $raw = require dirname(__DIR__, 2) . '/config/permissions.php';
        $permissions = [];

        foreach ($raw as $role => $modules) {
            $permissions[self::normalizeRole((string) $role)] = array_map(
                static fn ($module) => trim((string) $module, '/'),
                (array) $modules
            );
        }

        return $permissions;
    }

    private static function normalizeRole(string $role): string
    {
        return strtolower(str_replace([' ', '-'], '_', trim($role)));
    }

    private static function loadRoles(int $userId): array
    {
        $sql = "SELECT r.fonction
                FROM ATTRIBUTIONS a
                INNER JOIN ROLES r ON r.role_id = a.role_id AND r.statut = 0
                WHERE a.utilisateur_id = ? AND a.statut = 0
                ORDER BY r.fonction";
        $statement = Database::conn()->prepare($sql);
        $statement->execute([$userId]);

        return array_column($statement->fetchAll(), 'fonction');
    }
}
