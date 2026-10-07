<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuthService;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;

final class AdminAuthController extends Controller
{
    public function showLogin(): void
    {
        $this->redirectAuthenticatedUser();
        $this->view('auth/admin-login', [
            'errors' => Session::pullFlash('errors', []),
            'old' => Session::pullFlash('old', []),
            'success' => Session::pullFlash('success'),
            'csrf' => Csrf::token(),
        ]);
    }

    public function login(): void
    {
        $this->requireValidCsrf();

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        if ((new AuthService())->authenticate($email, $password, 'admin')) {
            $this->redirect('/admin');
        }

        Session::flash('errors', ['Admin email or password is incorrect, or this account is unavailable.']);
        Session::flash('old', ['email' => $email]);
        $this->redirect('/admin/login');
    }

    public function logout(): void
    {
        $this->requireValidCsrf();
        Auth::logout();
        Session::flash('success', 'You have been signed out.');
        $this->redirect('/admin/login');
    }
}
