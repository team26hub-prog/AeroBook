<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    public static function render(string $template, array $data = []): void
    {
        $template = str_replace('\\', '/', $template);
        if (str_contains($template, '..') || str_starts_with($template, '/')) {
            throw new RuntimeException('Invalid view template path.');
        }

        $file = BASE_PATH . '/app/Views/' . $template . '.php';
        if (!is_file($file)) {
            throw new RuntimeException('View not found: ' . $template);
        }

        extract($data, EXTR_SKIP);
        require $file;
    }
}
