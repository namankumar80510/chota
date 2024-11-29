<?php

declare(strict_types=1);

namespace Reacted;

use Dikki\DotEnv\DotEnv;
use Tracy\Debugger;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use League\Route\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class App
{
    private ServerRequestInterface $request;
    private Router $router;
    private SapiEmitter $emitter;

    public function __construct(protected array $config)
    {
        $this->initializeServices();
        $this->loadEnv();
        $this->loadDebugger();
        $this->loadRouter();
    }

    private function initializeServices(): void
    {
        $this->request = ServerRequestFactory::fromGlobals();
        $this->router = new Router();
        $this->emitter = new SapiEmitter();
    }

    private function loadEnv(): void
    {
        static $loaded = false;
        if (!$loaded) {
            (new DotEnv($this->config['paths']['root']))->load();
            $loaded = true;
        }
    }

    private function loadDebugger(): void
    {
        $logPath = $this->config['paths']['log'] ?? null;
        $mode = env('ENVIRONMENT') === 'development' ? Debugger::Development : Debugger::Production;
        Debugger::enable($mode, $logPath);
    }

    private function loadRouter(): void
    {
        foreach ($this->config['routes'] as [$method, $path, $handler]) {
            $this->router->map($method, $path, $handler);
        }

        $response = $this->router->dispatch($this->request);
        $this->emitResponse($response);
    }

    private function emitResponse(ResponseInterface $response): void
    {
        $this->emitter->emit($response);
    }
}
