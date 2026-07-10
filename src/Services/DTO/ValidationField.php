<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Services\DTO;

class ValidationField
{
    /**
     * @param array<int, string> $rules
     */
    public function __construct(
        private readonly string $name,
        private readonly string $type,
        private readonly bool $required,
        private readonly array $rules,
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

    public function required(): bool
    {
        return $this->required;
    }

    /** @return array<int, string> */
    public function rules(): array
    {
        return $this->rules;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'required' => $this->required,
            'rules' => $this->rules,
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
            required: (bool) $data['required'],
            rules: is_array($data['rules']) ? $data['rules'] : [],
        );
    }
}
