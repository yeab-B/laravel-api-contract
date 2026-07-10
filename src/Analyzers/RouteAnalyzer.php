<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Analyzers;

use Illuminate\Routing\Router;
use Illuminate\Routing\Route;
use Yab\LaravelApiContract\Config\Configuration;
use Yab\LaravelApiContract\Contracts\RouteAnalyzerContract;
use Yab\LaravelApiContract\Services\DTO\RouteCollection;
use Yab\LaravelApiContract\Services\DTO\RouteDefinition;

class RouteAnalyzer implements RouteAnalyzerContract
{
    private const EXCLUDED_METHODS = ['HEAD'];

    public function __construct(
        private readonly Router $router,
        private readonly Configuration $configuration,
    ) {
    }

    public function discover(): RouteCollection
    {
        $apiPrefix = $this->configuration->apiPrefix();

        $routes = array_filter(
            $this->router->getRoutes()->getRoutes(),
            fn (Route $route) => $this->isApiRoute($route, $apiPrefix),
        );

        $definitions = array_values(array_filter(
            array_map(
                fn (Route $route) => $this->createDefinition($route),
                $routes,
            ),
            fn (?RouteDefinition $definition) => $definition !== null,
        ));

        return new RouteCollection(...$definitions);
    }

    private function isApiRoute(Route $route, string $apiPrefix): bool
    {
        if (str_starts_with($route->uri(), $apiPrefix . '/')) {
            return true;
        }

        if ($route->uri() === $apiPrefix) {
            return true;
        }

        $middleware = (array) $route->middleware();

        return in_array('api', $middleware, true);
    }

    private function createDefinition(Route $route): ?RouteDefinition
    {
        $methods = array_filter(
            $route->methods(),
            fn (string $method) => !in_array($method, self::EXCLUDED_METHODS, true),
        );

        $mainMethod = reset($methods);

        if ($mainMethod === false) {
            return null;
        }

        $controller = $this->resolveController($route);

        if ($controller === null) {
            return null;
        }

        return new RouteDefinition(
            method: $mainMethod,
            uri: $route->uri(),
            name: $route->getName(),
            controller: $controller,
            middleware: (array) $route->middleware(),
            parameters: $route->parameterNames(),
        );
    }

    private function resolveController(Route $route): ?string
    {
        $action = (array) $route->getAction();

        $controller = $action['controller'] ?? null;

        if (is_string($controller)) {
            return $controller;
        }

        $uses = $action['uses'] ?? null;

        if (is_string($uses)) {
            return $uses;
        }

        return null;
    }
}
