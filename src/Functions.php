<?php

declare(strict_types=1);

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $default;
    }
}

if (!function_exists('dd')) {
    function dd(...$args): void
    {
        dump(...$args);
        exit;
    }
}
