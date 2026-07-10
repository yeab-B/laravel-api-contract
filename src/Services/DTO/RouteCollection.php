<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Services\DTO;

use Countable;
use IteratorAggregate;
use Traversable;
use ArrayIterator;

/** @implements IteratorAggregate<int, RouteDefinition> */
class RouteCollection implements Countable, IteratorAggregate
{
    /** @var array<int, RouteDefinition> */
    private array $routes;

    public function __construct(RouteDefinition ...$routes)
    {
        $this->routes = array_values($routes);
    }

    /** @return array<int, RouteDefinition> */
    public function all(): array
    {
        return $this->routes;
    }

    public function findByMethod(string $method): self
    {
        return $this->filter(
            static fn (RouteDefinition $route) => strtoupper($method) === $route->method(),
        );
    }

    public function findByName(string $name): ?RouteDefinition
    {
        foreach ($this->routes as $route) {
            if ($route->name() === $name) {
                return $route;
            }
        }

        return null;
    }

    public function findByController(string $controller): self
    {
        return $this->filter(
            static fn (RouteDefinition $route) => $route->controller() === $controller,
        );
    }

    public function filter(callable $callback): self
    {
        return new self(
            ...array_filter($this->routes, $callback),
        );
    }

    public function merge(self $other): self
    {
        return new self(
            ...array_merge($this->routes, $other->all()),
        );
    }

    public function count(): int
    {
        return count($this->routes);
    }

    public function isEmpty(): bool
    {
        return $this->routes === [];
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->routes);
    }

    /** @return array<int, array<string, mixed>> */
    public function toArray(): array
    {
        return array_map(
            static fn (RouteDefinition $route) => $route->toArray(),
            $this->routes,
        );
    }
}
