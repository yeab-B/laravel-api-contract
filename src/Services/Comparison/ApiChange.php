<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Services\Comparison;

class ApiChange
{
    public const ADDED_ENDPOINT = 'ADDED_ENDPOINT';
    public const REMOVED_ENDPOINT = 'REMOVED_ENDPOINT';
    public const CHANGED_METHOD = 'CHANGED_METHOD';
    public const CHANGED_AUTH = 'CHANGED_AUTH';
    public const ADDED_REQUEST_FIELD = 'ADDED_REQUEST_FIELD';
    public const REMOVED_REQUEST_FIELD = 'REMOVED_REQUEST_FIELD';
    public const CHANGED_REQUEST_FIELD_TYPE = 'CHANGED_REQUEST_FIELD_TYPE';
    public const ADDED_RESPONSE_FIELD = 'ADDED_RESPONSE_FIELD';
    public const REMOVED_RESPONSE_FIELD = 'REMOVED_RESPONSE_FIELD';
    public const CHANGED_RESPONSE_FIELD_TYPE = 'CHANGED_RESPONSE_FIELD_TYPE';

    public const BREAKING = 'BREAKING';
    public const NON_BREAKING = 'NON_BREAKING';

    private const SEVERITY_MAP = [
        self::REMOVED_ENDPOINT => self::BREAKING,
        self::CHANGED_METHOD => self::BREAKING,
        self::CHANGED_AUTH => self::BREAKING,
        self::REMOVED_REQUEST_FIELD => self::BREAKING,
        self::CHANGED_REQUEST_FIELD_TYPE => self::BREAKING,
        self::REMOVED_RESPONSE_FIELD => self::BREAKING,
        self::CHANGED_RESPONSE_FIELD_TYPE => self::BREAKING,
        self::ADDED_ENDPOINT => self::NON_BREAKING,
        self::ADDED_REQUEST_FIELD => self::NON_BREAKING,
        self::ADDED_RESPONSE_FIELD => self::NON_BREAKING,
    ];

    private readonly ?string $severity;

    public function __construct(
        private readonly string $type,
        private readonly string $location,
        private readonly string $description,
        ?string $severity = null,
    ) {
        $this->severity = $severity;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function location(): string
    {
        return $this->location;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function severity(): string
    {
        return $this->severity ?? (self::SEVERITY_MAP[$this->type] ?? self::NON_BREAKING);
    }

    public function isBreaking(): bool
    {
        return $this->severity() === self::BREAKING;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'location' => $this->location,
            'description' => $this->description,
            'severity' => $this->severity(),
        ];
    }
}
