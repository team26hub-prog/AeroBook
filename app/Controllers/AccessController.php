<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;

final class AccessController extends Controller
{
    public function customer(): void
    {
        $this->view('auth/access', [
            'title' => 'Customer account',
            'heading' => 'Welcome, ' . Auth::name(),
            'message' => 'You are signed in with a customer account.',
            'logoutPath' => '/logout',
            'csrf' => Csrf::token(),
            'errors' => [],
            'success' => Session::pullFlash('success'),
        ]);
    }

    public function admin(): void
    {
        $this->view('auth/access', [
            'title' => 'Admin Panel',
            'heading' => 'Admin Panel',
            'message' => 'Welcome, ' . Auth::name() . '. You are signed in as an administrator.',
            'logoutPath' => '/admin/logout',
            'csrf' => Csrf::token(),
            'errors' => [],
            'success' => Session::pullFlash('success'),
        ]);
    }
}
