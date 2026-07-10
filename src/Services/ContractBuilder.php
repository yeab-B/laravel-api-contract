<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Services;

use Yab\LaravelApiContract\Config\Configuration;
use Yab\LaravelApiContract\Contracts\ContractBuilderContract;
use Yab\LaravelApiContract\Contracts\ControllerAnalyzerContract;
use Yab\LaravelApiContract\Contracts\RequestAnalyzerContract;
use Yab\LaravelApiContract\Contracts\ResourceAnalyzerContract;
use Yab\LaravelApiContract\Contracts\RouteAnalyzerContract;
use Yab\LaravelApiContract\Services\Contract\ApiContract;
use Yab\LaravelApiContract\Services\Contract\EndpointDefinition;

class ContractBuilder implements ContractBuilderContract
{
    public function __construct(
        private readonly RouteAnalyzerContract $routeAnalyzer,
        private readonly ControllerAnalyzerContract $controllerAnalyzer,
        private readonly RequestAnalyzerContract $requestAnalyzer,
        private readonly ResourceAnalyzerContract $resourceAnalyzer,
        private readonly Configuration $configuration,
    ) {
    }

    public function build(): ApiContract
    {
        $routeCollection = $this->routeAnalyzer->discover();
        $endpoints = [];

        foreach ($routeCollection->all() as $route) {
            $controllerDefinition = $this->controllerAnalyzer->analyze($route);

            $requestDefinition = null;
            $resourceDefinition = null;

            if ($controllerDefinition !== null) {
                $requestDefinition = $this->requestAnalyzer->analyze($controllerDefinition);
                $resourceDefinition = $this->resourceAnalyzer->analyze($controllerDefinition);
            }

            $endpoints[] = new EndpointDefinition(
                method: $route->method(),
                uri: $route->uri(),
                name: $route->name(),
                controller: $route->controller(),
                middleware: $route->middleware(),
                parameters: $route->parameters(),
                request: $requestDefinition,
                response: $resourceDefinition,
            );
        }

        $name = $this->configuration->contractName();
        $version = $this->configuration->contractVersion();
        $auth = $this->configuration->authenticationDriver();

        return new ApiContract(
            name: $name,
            version: $version,
            endpoints: $endpoints,
            authentication: $auth,
            metadata: [
                'generated_at' => date('c'),
                'endpoint_count' => count($endpoints),
            ],
        );
    }
}
