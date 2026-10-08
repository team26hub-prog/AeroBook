<?php
declare(strict_types=1);

namespace App\Core;

final class HttpError
{
    public static function render(int $status, ?string $message = null): void
    {
        $messages = [
            403 => 'You do not have permission to access this page.',
            404 => 'We could not find the page you requested.',
            405 => 'This action is not available for this request.',
            419 => 'This page expired. Refresh it and try again.',
            500 => 'Something went wrong. Please try again in a moment.',
        ];
        http_response_code($status);
        View::render('errors/http', [
            'status' => $status,
            'message' => $message ?? ($messages[$status] ?? $messages[500]),
            'home' => Auth::role() === 'admin' ? '/admin' : (Auth::check() ? '/account' : '/login'),
        ]);
    }
}
