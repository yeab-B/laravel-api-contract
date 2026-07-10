<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Services\DTO;

use Countable;
use IteratorAggregate;
use Traversable;
use ArrayIterator;

/** @implements IteratorAggregate<int, ControllerDefinition> */
class ControllerCollection implements Countable, IteratorAggregate
{
    /** @var array<int, ControllerDefinition> */
    private array $definitions;

    public function __construct(ControllerDefinition ...$definitions)
    {
        $this->definitions = array_values($definitions);
    }

    /** @return array<int, ControllerDefinition> */
    public function all(): array
    {
        return $this->definitions;
    }

    public function findByController(string $controllerAction): ?ControllerDefinition
    {
        foreach ($this->definitions as $definition) {
            if ($definition->controllerAction() === $controllerAction) {
                return $definition;
            }
        }

        return null;
    }

    public function findByClass(string $className): self
    {
        return $this->filter(
            static fn (ControllerDefinition $d) => $d->className() === $className,
        );
    }

    public function filter(callable $callback): self
    {
        return new self(
            ...array_filter($this->definitions, $callback),
        );
    }

    public function count(): int
    {
        return count($this->definitions);
    }

    public function isEmpty(): bool
    {
        return $this->definitions === [];
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->definitions);
    }

    /** @return array<int, array<string, mixed>> */
    public function toArray(): array
    {
        return array_map(
            static fn (ControllerDefinition $d) => $d->toArray(),
            $this->definitions,
        );
    }
}
