<?php
namespace App\Core;
class Helpers {
    public static function config(string $file): array { return require dirname(__DIR__,2)."/config/$file.php"; }
    public static function currentPath(): string {
        if (isset($_GET['url'])) {
            return trim((string) $_GET['url'], '/') ?: '/';
        }

        $requestPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/');
        $basePath = trim(parse_url(self::config('app')['base_url'] ?? '', PHP_URL_PATH) ?: '', '/');

        if ($basePath !== '' && ($requestPath === $basePath || str_starts_with($requestPath, $basePath . '/'))) {
            $requestPath = trim(substr($requestPath, strlen($basePath)), '/');
        }

        if ($requestPath === 'index.php') {
            return '/';
        }

        return $requestPath ?: '/';
    }
    public static function url(string $path=''): string {
        $app = self::config('app');
        $baseUrl = rtrim($app['base_url'], '/');

        if (!empty($_SERVER['HTTP_HOST'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
            $basePath = trim($scriptDir === '.' ? '' : $scriptDir, '/');
            $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . ($basePath ? '/' . $basePath : '');
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
    public static function asset(string $path): string {
        $relativePath = ltrim($path, '/');
        $file = dirname(__DIR__, 2) . '/public/' . $relativePath;
        $version = is_file($file) ? '?v=' . filemtime($file) : '';
        return self::url($relativePath) . $version;
    }
    public static function e(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
    public static function redirect(string $path): void { header('Location: '.self::url($path)); exit; }
}
