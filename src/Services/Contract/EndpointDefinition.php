<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Services\Contract;

use Yab\LaravelApiContract\Services\DTO\RequestDefinition;
use Yab\LaravelApiContract\Services\DTO\ResourceDefinition;

class EndpointDefinition
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
        private readonly ?RequestDefinition $request = null,
        private readonly ?ResourceDefinition $response = null,
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

    public function request(): ?RequestDefinition
    {
        return $this->request;
    }

    public function response(): ?ResourceDefinition
    {
        return $this->response;
    }

    public function hasRequest(): bool
    {
        return $this->request !== null;
    }

    public function hasResponse(): bool
    {
        return $this->response !== null;
    }

    public function key(): string
    {
        return $this->method . ' ' . $this->uri;
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
            'request' => $this->request?->toArray(),
            'response' => $this->response?->toArray(),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            method: is_string($data['method']) ? $data['method'] : '',
            uri: is_string($data['uri']) ? $data['uri'] : '',
            name: isset($data['name']) && is_string($data['name']) ? $data['name'] : null,
            controller: isset($data['controller']) && is_string($data['controller']) ? $data['controller'] : null,
            middleware: isset($data['middleware']) && is_array($data['middleware']) ? $data['middleware'] : [],
            parameters: isset($data['parameters']) && is_array($data['parameters']) ? $data['parameters'] : [],
            request: isset($data['request']) && is_array($data['request'])
                ? RequestDefinition::fromArray($data['request'])
                : null,
            response: isset($data['response']) && is_array($data['response'])
                ? ResourceDefinition::fromArray($data['response'])
                : null,
        );
    }
}
