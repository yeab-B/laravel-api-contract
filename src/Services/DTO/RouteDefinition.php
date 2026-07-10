<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Services\DTO;

class RouteDefinition
{
    /**
     * @param array<int, string> $middleware
     * @param array<int, string> $parameters
     */
    public function __construct(
        private readonly string $method,
        private readonly string $uri,
        private readonly ?string $name,
        private readonly ?string $controller,
        private readonly array $middleware,
        private readonly array $parameters,
    ) {
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function controller(): ?string
    {
        return $this->controller;
    }

    /** @return array<int, string> */
    public function middleware(): array
    {
        return $this->middleware;
    }

    /** @return array<int, string> */
    public function parameters(): array
    {
        return $this->parameters;
    }

    public function hasParameter(string $name): bool
    {
        return in_array($name, $this->parameters, true);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'method' => $this->method,
            'uri' => $this->uri,
            'name' => $this->name,
            'controller' => $this->controller,
            'middleware' => $this->middleware,
            'parameters' => $this->parameters,
        ];
    }
}
