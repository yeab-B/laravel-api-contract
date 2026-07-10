<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Services\Contract;

use Yab\LaravelApiContract\Contracts\ApiContractContract;

class ApiContract implements ApiContractContract
{
    /**
     * @param array<int, EndpointDefinition> $endpoints
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private readonly string $name,
        private readonly string $version,
        private readonly array $endpoints,
        private readonly string $authentication,
        private readonly array $metadata = [],
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function version(): string
    {
        return $this->version;
    }

    /** @return array<int, EndpointDefinition> */
    public function endpoints(): array
    {
        return $this->endpoints;
    }

    public function authentication(): string
    {
        return $this->authentication;
    }

    /** @return array<string, mixed> */
    public function metadata(): array
    {
        return $this->metadata;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'version' => $this->version,
            'authentication' => $this->authentication,
            'endpoints' => array_map(
                static fn (EndpointDefinition $endpoint) => $endpoint->toArray(),
                $this->endpoints,
            ),
            'metadata' => $this->metadata,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $endpointsData = is_array($data['endpoints']) ? $data['endpoints'] : [];
        $endpoints = array_map(
            static fn (mixed $endpoint) => EndpointDefinition::fromArray(is_array($endpoint) ? $endpoint : []),
            $endpointsData,
        );

        return new self(
            name: is_string($data['name']) ? $data['name'] : '',
            version: is_string($data['version']) ? $data['version'] : '',
            endpoints: $endpoints,
            authentication: is_string($data['authentication']) ? $data['authentication'] : '',
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : [],
        );
    }
}
