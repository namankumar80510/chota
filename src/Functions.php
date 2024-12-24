<?php

declare(strict_types=1);

if (!function_exists('app')) {
    function app(): Chota\App
    {
        if (!\Chota\App::isInitialized()) {
            throw new RuntimeException('App has not been initialized. Create an instance first.');
        }
        return \Chota\App::getInstance();
    }
}

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

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return app()->config($key, $default);
    }
}
