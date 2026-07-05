<?php
namespace App\Core;
class Session {
    public static function flash(string $key, ?string $value=null): ?string { if($value!==null){$_SESSION['_flash'][$key]=$value; return null;} $v=$_SESSION['_flash'][$key]??null; unset($_SESSION['_flash'][$key]); return $v; }
    public static function user(): ?array { return $_SESSION['user'] ?? null; }
    public static function setUser(array $u): void { $_SESSION['user']=$u; }
    public static function destroy(): void { session_destroy(); }
}
