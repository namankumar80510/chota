<?php

declare(strict_types=1);

namespace Reacted\View;

interface ViewInterface
{
    public function render(string $template, array $data = []): string;
}
