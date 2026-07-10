<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Services\DTO;

class ResourceDefinition
{
    /**
     * @param array<int, ResponseField> $fields
     * @param array<int, string> $relationships
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private readonly string $resourceClass,
        private readonly array $fields,
        private readonly array $relationships,
        private readonly bool $collection,
        private readonly array $metadata = [],
    ) {
    }

    public function resourceClass(): string
    {
        return $this->resourceClass;
    }

    /** @return array<int, ResponseField> */
    public function fields(): array
    {
        return $this->fields;
    }

    /** @return array<int, string> */
    public function relationships(): array
    {
        return $this->relationships;
    }

    public function collection(): bool
    {
        return $this->collection;
    }

    /** @return array<string, mixed> */
    public function metadata(): array
    {
        return $this->metadata;
    }

    public function hasRelationships(): bool
    {
        return $this->relationships !== [];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'resource_class' => $this->resourceClass,
            'fields' => array_map(
                static fn (ResponseField $field) => $field->toArray(),
                $this->fields,
            ),
            'relationships' => $this->relationships,
            'collection' => $this->collection,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $fieldsData = is_array($data['fields']) ? $data['fields'] : [];
        $fields = array_map(
            static fn (mixed $field) => ResponseField::fromArray(is_array($field) ? $field : []),
            $fieldsData,
        );

        return new self(
            resourceClass: is_string($data['resource_class']) ? $data['resource_class'] : '',
            fields: $fields,
            relationships: is_array($data['relationships']) ? $data['relationships'] : [],
            collection: (bool) $data['collection'],
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : [],
        );
    }
}
