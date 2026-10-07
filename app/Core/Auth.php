<?php
declare(strict_types=1);

namespace App\Core;

final class Auth
{
    /** @param array{id:int|string, full_name:string, role:string} $user */
    public static function login(array $user): void
    {
        $_SESSION = [];
        Session::regenerate();
        Session::put('user_id', (int) $user['id']);
        Session::put('user_name', $user['full_name']);
        Session::put('user_role', $user['role']);
        Csrf::rotate();
    }

    public static function logout(): void
    {
        $_SESSION = [];
        Session::regenerate();
        Csrf::rotate();
    }

    public static function check(): bool
    {
        return is_int(Session::get('user_id')) && Session::get('user_id') > 0;
    }

    public static function role(): ?string
    {
        $role = Session::get('user_role');
        return is_string($role) ? $role : null;
    }

    public static function name(): string
    {
        $name = Session::get('user_name');
        return is_string($name) ? $name : '';
    }
}
