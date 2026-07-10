<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Services\DTO;

class ControllerDefinition
{
    /**
     * @param array<int, array{name: string, type: ?string, class: ?string}> $parameters
     * @param array<int, string> $dependencies
     */
    public function __construct(
        private readonly string $className,
        private readonly string $method,
        private readonly string $visibility,
        private readonly array $parameters,
        private readonly ?string $returnType,
        private readonly array $dependencies,
    ) {
    }

    public function className(): string
    {
        return $this->className;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function visibility(): string
    {
        return $this->visibility;
    }

    /**
     * @return array<int, array{name: string, type: ?string, class: ?string}>
     */
    public function parameters(): array
    {
        return $this->parameters;
    }

    public function returnType(): ?string
    {
        return $this->returnType;
    }

    /**
     * @return array<int, string>
     */
    public function dependencies(): array
    {
        return $this->dependencies;
    }

    public function hasDependencies(): bool
    {
        return $this->dependencies !== [];
    }

    public function controllerAction(): string
    {
        return $this->className . '@' . $this->method;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'class_name' => $this->className,
            'method' => $this->method,
            'visibility' => $this->visibility,
            'parameters' => $this->parameters,
            'return_type' => $this->returnType,
            'dependencies' => $this->dependencies,
        ];
    }
}
