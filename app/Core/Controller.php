<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $template, array $data = []): void
    {
        View::render($template, $data);
    }

    protected function redirect(string $path): never
    {
        header('Location: ' . $path, true, 303);
        exit;
    }

    protected function requireValidCsrf(): void
    {
        if (Csrf::verify($_POST['_csrf'] ?? null)) {
            return;
        }

        HttpError::render(419);
        exit;
    }

    protected function redirectAuthenticatedUser(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $this->redirect(Auth::role() === 'admin' ? '/admin' : '/account');
    }
}
