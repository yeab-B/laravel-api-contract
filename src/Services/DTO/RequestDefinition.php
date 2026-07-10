<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Services\DTO;

class RequestDefinition
{
    /**
     * @param array<int, ValidationField> $fields
     * @param array<string, mixed> $rawRules
     */
    public function __construct(
        private readonly string $className,
        private readonly array $fields,
        private readonly bool $authorizeMethod,
        private readonly array $rawRules,
    ) {
    }

    public function className(): string
    {
        return $this->className;
    }

    /** @return array<int, ValidationField> */
    public function fields(): array
    {
        return $this->fields;
    }

    public function authorizeMethod(): bool
    {
        return $this->authorizeMethod;
    }

    /** @return array<string, mixed> */
    public function rawRules(): array
    {
        return $this->rawRules;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'class_name' => $this->className,
            'fields' => array_map(
                static fn (ValidationField $field) => $field->toArray(),
                $this->fields,
            ),
            'authorize' => $this->authorizeMethod,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $fieldsData = is_array($data['fields']) ? $data['fields'] : [];
        $fields = array_map(
            static fn (mixed $field) => ValidationField::fromArray(is_array($field) ? $field : []),
            $fieldsData,
        );

        return new self(
            className: is_string($data['class_name']) ? $data['class_name'] : '',
            fields: $fields,
            authorizeMethod: (bool) $data['authorize'],
            rawRules: [],
        );
    }
}
