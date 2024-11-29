<?php

declare(strict_types=1);

namespace Reacted\View;

use League\Plates\Engine;

class ViewRenderer implements ViewInterface
{
    private Engine $engine;

    public function __construct(string $templatesPath)
    {
        $this->engine = new Engine($templatesPath);
    }

    public function render(string $template, array $data = []): string
    {
        return $this->engine->render($template, $data);
    }
}
