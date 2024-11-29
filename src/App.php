<?php

declare(strict_types=1);

namespace Chota;

use Dikki\DotEnv\DotEnv;
use Tracy\Debugger;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use League\Route\Router;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Chota\View\ViewInterface;
use Chota\View\ViewRenderer;
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
        if (isset($this->config['middlewares'])) {
            $this->router->middlewares($this->config['middlewares']);
        }
        $this->emitter = new SapiEmitter();

        $this->container = new ContainerBuilder();

        // Register core services
        $this->container->register(ViewInterface::class, ViewRenderer::class)
            ->setArguments([$this->config['paths']['templates'] ?? null])
            ->setPublic(true)
            ->setAutowired(true);

        // Register explicit services if any
        if (isset($this->config['services'])) {
            foreach ($this->config['services'] as $id => $class) {
                $this->container->register($id, $class)
                    ->setPublic(true)
                    ->setAutowired(true);
            }
        }

        // Auto-register controllers
        foreach ($this->config['routes'] as $route) {
            if (is_array($route[2]) && count($route[2]) === 2) {
                $controllerClass = $route[2][0];
                if (!$this->container->hasDefinition($controllerClass)) {
                    $this->container->register($controllerClass, $controllerClass)
                        ->setPublic(true)
                        ->setAutowired(true);
                }
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
        foreach ($this->config['routes'] as $route) {
            [$method, $path, $handler] = $route;
            $name = $route[3] ?? null;
            $middleware = $route[4] ?? null;

            $routeMap = $this->router->map($method, $path, $this->resolveHandler($handler));

            if ($name) {
                $routeMap->setName($name);
            }

            if ($middleware) {
                $routeMap->middleware($middleware);
            }
        }
    }

    private function resolveHandler(callable|array $handler): callable
    {
        if (is_array($handler) && count($handler) === 2) {
            [$controller, $action] = $handler;
            return [$this->container->get($controller), $action];
        }
        return $handler;
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
