<?php

declare(strict_types=1);

use Laminas\Diactoros\Response\HtmlResponse;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/classes.php';

$app = new Chota\App([
    'paths' => [
        'root' => __DIR__ . '/',
        'log' => __DIR__ . '/',
        'templates' => __DIR__ . '/templates',
    ],
    'services' => [],
    'routes' => [
        ['GET', '/', [HomeController::class, 'index']],
        ['GET', '/test', function () {
            return new HtmlResponse('Does this work?');
        }, 'test', new class implements \Psr\Http\Server\MiddlewareInterface {
            public function process(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Server\RequestHandlerInterface $handler): \Psr\Http\Message\ResponseInterface
            {
                return $handler->handle($request);
            }
        }],
    ],
    'middlewares' => [],
    'config' => [
        'app' => [
            'name' => 'Dummy',
        ]
    ]
]);

$app->run();
