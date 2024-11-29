<?php

declare(strict_types=1);

/**
 * procedural functions;
 */

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $default;
    }
}
