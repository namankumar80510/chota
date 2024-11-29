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
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Reacted\View\ViewInterface;
use Reacted\View\ViewRenderer;
use Laminas\Diactoros\Response\HtmlResponse;

class App
{
    private ServerRequestInterface $request;
    private Router $router;
    private SapiEmitter $emitter;
    private ContainerBuilder $container;
    private static bool $initialized = false;

    public function __construct(protected array $config)
    {
        $this->initializeCore();
        if (!self::$initialized) {
            $this->initializeEnvironment();
            self::$initialized = true;
        }
        $this->initializeRoutes();
    }

    private function initializeCore(): void 
    {
        $this->request = ServerRequestFactory::fromGlobals();
        $this->router = new Router();
        $this->emitter = new SapiEmitter();
        
        $this->container = new ContainerBuilder();
        $this->container->register(ViewInterface::class, ViewRenderer::class)
            ->setArguments([$this->config['paths']['templates'] ?? null])
            ->setPublic(true)
            ->setAutowired(true);
            
        if (isset($this->config['services'])) {
            foreach ($this->config['services'] as $id => $class) {
                $this->container->register($id, $class)
                    ->setPublic(true)
                    ->setAutowired(true);
            }
        }
        
        $this->container->compile();
    }

    private function initializeEnvironment(): void
    {
        if (isset($this->config['paths']['root'])) {
            (new DotEnv($this->config['paths']['root']))->load();
        }
        
        $logPath = $this->config['paths']['log'] ?? null;
        $mode = env('ENVIRONMENT') === 'development' ? Debugger::Development : Debugger::Production;
        Debugger::enable($mode, $logPath);
    }

    private function initializeRoutes(): void
    {
        foreach ($this->config['routes'] as [$method, $path, $handler]) {
            if (is_callable($handler)) {
                $this->router->map($method, $path, $handler);
            } elseif (is_array($handler) && count($handler) === 2) {
                [$controller, $action] = $handler;
                $this->router->map($method, $path, [$this->container->get($controller), $action]);
            }
        }
    }

    public function run(): void
    {
        try {
            $response = $this->router->dispatch($this->request);
            $this->emitter->emit($response);
        } catch (\Throwable $e) {
            if (env('ENVIRONMENT') === 'development') {
                throw $e;
            }
            $this->emitter->emit(new HtmlResponse('Server Error', 500));
        }
    }

    public function get(string $id): mixed
    {
        return $this->container->get($id);
    }
}
