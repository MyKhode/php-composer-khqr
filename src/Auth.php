<?php
namespace App;


class Auth
{
public static function check(): bool { return !empty($_SESSION['admin_id']); }
public static function require(): void { if (!self::check()) { header('Location: /admin/login'); exit; } }
public static function login(int $id): void { $_SESSION['admin_id'] = $id; }
public static function logout(): void { session_destroy(); }
}