<?php

declare(strict_types=1);

namespace Chota;

use Chota\Router\ChotaRouterStrategy;
use Dikki\DotEnv\DotEnv;
use Tracy\Debugger;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use League\Route\Router;
use League\Container\Container;
use League\Container\ReflectionContainer;
use Psr\Http\Message\ServerRequestInterface;
use Chota\View\ViewInterface;
use Chota\View\ViewRenderer;
use Laminas\Diactoros\Response\HtmlResponse;
use RuntimeException;

class App
{
    private ServerRequestInterface $request;
    private Router $router;
    private SapiEmitter $emitter;
    private Container $container;
    private static bool $initialized = false;
    private static ?self $instance = null;

    public function __construct(protected array $config)
    {
        $this->initializeCore();
        if (!self::$initialized) {
            $this->initializeEnvironment();
            self::$initialized = true;
        }
        $this->initializeRoutes();
        self::$instance = $this;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new RuntimeException('App has not been initialized. Create an instance first.');
        }
        return self::$instance;
    }

    public static function isInitialized(): bool
    {
        return self::$instance !== null;
    }

    private function initializeCore(): void
    {
        $this->request = ServerRequestFactory::fromGlobals();
        $this->router = new Router();
        $this->emitter = new SapiEmitter();

        // Initialize League Container with autowiring
        $this->container = new Container();
        $this->container->delegate(new ReflectionContainer());

        // Register core services
        $this->container
            ->add(ViewInterface::class, ViewRenderer::class)
            ->addArgument($this->config['paths']['templates'] ?? null);

        // Register explicit services if any
        if (isset($this->config['services'])) {
            foreach ($this->config['services'] as $id => $class) {
                $this->container->add($id, $class);
            }
        }

        // Routing Strategy
        $this->router->setStrategy((new ChotaRouterStrategy())->setContainer($this->container));

        // Auto-register controllers
        foreach ($this->config['routes'] as $route) {
            if (is_array($route[2]) && count($route[2]) === 2) {
                $controllerClass = $route[2][0];
                if (!$this->container->has($controllerClass)) {
                    $this->container->add($controllerClass);
                }
            }
        }

        // Middlewares (global)
        if (isset($this->config['middlewares'])) {
            $this->router->middlewares($this->config['middlewares']);
        }
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

    public function config(string $key, mixed $default = null): mixed
    {
        $configArray = $this->config['config'] ?? [];
        $keys = explode('.', $key);
        $value = $configArray;

        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value ?? $default;
    }
}
