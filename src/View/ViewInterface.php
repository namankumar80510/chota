<?php

declare(strict_types=1);

namespace Chota\View;

interface ViewInterface
{
    public function render(string $template, array $data = []): string;
}
