<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;

final class AdminAuthController extends Controller
{
    public function showLogin(): void
    {
        $this->redirect('/login');
    }

    public function login(): void
    {
        (new AuthController())->login();
    }

    public function logout(): void
    {
        $this->requireValidCsrf();
        Auth::logout();
        Session::flash('success', 'You have been signed out.');
        $this->redirect('/login');
    }
}
