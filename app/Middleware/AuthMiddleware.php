<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\HttpError;
use App\Core\Session;
use App\Models\User;

final class AuthMiddleware
{
    public function handle(string $requiredRole, callable $next): mixed
    {
        if (!Auth::check()) {
            header('Location: /login', true, 303);
            return null;
        }

        $user = (new User())->findIdentityById((int) Session::get('user_id'));
        if ($user === null || $user['status'] !== 'active' || $user['role'] !== Auth::role()) {
            Auth::logout();
            Session::flash('errors', ['Your session is no longer active. Sign in again to continue.']);
            header('Location: /login', true, 303);
            return null;
        }

        if ($user['role'] !== $requiredRole) {
            HttpError::render(403);
            return null;
        }

        Session::put('user_name', $user['full_name']);

        return $next();
    }
}
