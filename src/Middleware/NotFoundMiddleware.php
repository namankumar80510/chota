<?php

namespace Chota\Middleware;

use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class NotFoundMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return new HtmlResponse(file_get_contents(__DIR__ . '/../../templates/errors/404.html'), 404);
    }
}
