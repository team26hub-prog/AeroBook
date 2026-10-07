<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;

final class AuthService
{
    public function __construct(private readonly User $users = new User())
    {
    }

    public function authenticate(string $email, string $password, string $requiredRole): bool
    {
        $user = $this->users->findByEmail(strtolower(trim($email)));
        if ($user === null
            || $user['role'] !== $requiredRole
            || $user['status'] !== 'active'
            || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $this->users->updatePasswordHash((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        }

        Auth::login($user);
        return true;
    }
}
