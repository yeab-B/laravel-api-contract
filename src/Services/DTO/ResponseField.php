<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Services\DTO;

class ResponseField
{
    public function __construct(
        private readonly string $name,
        private readonly string $type,
        private readonly bool $nullable,
        private readonly ?string $source,
        private readonly ?string $relationClass = null,
        private readonly bool $collection = false,
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function nullable(): bool
    {
        return $this->nullable;
    }

    public function source(): ?string
    {
        return $this->source;
    }

    public function relationClass(): ?string
    {
        return $this->relationClass;
    }

    public function collection(): bool
    {
        return $this->collection;
    }

    public function isRelationship(): bool
    {
        return $this->relationClass !== null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'nullable' => $this->nullable,
            'source' => $this->source,
            'relation_class' => $this->relationClass,
            'collection' => $this->collection,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: is_string($data['name']) ? $data['name'] : '',
            type: is_string($data['type']) ? $data['type'] : '',
            nullable: (bool) $data['nullable'],
            source: isset($data['source']) && is_string($data['source']) ? $data['source'] : null,
            relationClass: isset($data['relation_class']) && is_string($data['relation_class'])
                ? $data['relation_class']
                : null,
            collection: isset($data['collection']) ? (bool) $data['collection'] : false,
        );
    }
}
