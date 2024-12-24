<?php

namespace Chota\Router;

use League\Route\Http\Exception\NotFoundException;
use League\Route\Strategy\ApplicationStrategy;
use Psr\Http\Server\MiddlewareInterface;

class ChotaRouterStrategy extends ApplicationStrategy
{
    public function getNotFoundDecorator(NotFoundException $exception): MiddlewareInterface
    {
        return new \Chota\Middleware\NotFoundMiddleware();
    }
}