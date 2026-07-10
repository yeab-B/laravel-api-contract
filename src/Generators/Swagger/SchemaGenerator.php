<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Generators\Swagger;

use Yab\LaravelApiContract\Services\DTO\RequestDefinition;
use Yab\LaravelApiContract\Services\DTO\ResourceDefinition;
use Yab\LaravelApiContract\Services\DTO\ResponseField;
use Yab\LaravelApiContract\Services\DTO\ValidationField;

class SchemaGenerator
{
    private const TYPE_MAP = [
        'integer' => ['type' => 'integer'],
        'float' => ['type' => 'number', 'format' => 'float'],
        'boolean' => ['type' => 'boolean'],
        'array' => ['type' => 'array'],
        'string' => ['type' => 'string'],
        'email' => ['type' => 'string', 'format' => 'email'],
        'url' => ['type' => 'string', 'format' => 'uri'],
        'ip' => ['type' => 'string'],
        'date' => ['type' => 'string', 'format' => 'date'],
        'json' => ['type' => 'object'],
        'file' => ['type' => 'string', 'format' => 'binary'],
        'image' => ['type' => 'string', 'format' => 'binary'],
        'mixed' => ['type' => 'string'],
        'null' => ['type' => 'string', 'nullable' => true],
    ];

    /**
     * Convert a ResourceDefinition into OpenAPI schema components.
     *
     * @return array<string, mixed>
     */
    public function resourceToSchema(ResourceDefinition $resource): array
    {
        $schemaName = $this->deriveSchemaName($resource->resourceClass());
        $properties = [];

        foreach ($resource->fields() as $field) {
            $property = $this->fieldToProperty($field);

            if ($field->nullable()) {
                $property['nullable'] = true;
            }

            $properties[$field->name()] = $property;
        }

        $schema = [
            'type' => 'object',
            'properties' => $properties,
        ];

        return [$schemaName => $schema];
    }

    /**
     * Convert a RequestDefinition into an OpenAPI request body schema.
     *
     * @return array<string, mixed>
     */
    public function requestToSchema(RequestDefinition $request): array
    {
        $properties = [];
        $required = [];

        foreach ($request->fields() as $field) {
            $property = $this->validationFieldToProperty($field);

            if ($field->required()) {
                $required[] = $field->name();
            }

            $properties[$field->name()] = $property;
        }

        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => $required,
        ];
    }

    /**
     * Derive a human-readable schema name from a resource class FQCN.
     */
    public function deriveSchemaName(string $resourceClass): string
    {
        $shortName = class_basename($resourceClass);

        $shortName = preg_replace('/Resource$/', '', $shortName) ?? $shortName;

        return $shortName !== '' ? $shortName : class_basename($resourceClass);
    }

    /**
     * Derive a request schema name from a request class FQCN.
     */
    public function deriveRequestSchemaName(string $requestClass): string
    {
        $shortName = class_basename($requestClass);

        $shortName = preg_replace('/Request$/', '', $shortName) ?? $shortName;

        return $shortName !== '' ? $shortName . 'Request' : class_basename($requestClass);
    }

    /**
     * @return array<string, mixed>
     */
    public function fieldToProperty(ResponseField $field): array
    {
        if ($field->isRelationship()) {
            $refName = $this->deriveSchemaName($field->relationClass() ?? '');

            if ($field->collection()) {
                return [
                    'type' => 'array',
                    'items' => ['$ref' => '#/components/schemas/' . $refName],
                ];
            }

            return ['$ref' => '#/components/schemas/' . $refName];
        }

        return $this->mapType($field->type());
    }

    /**
     * @return array<string, mixed>
     */
    public function validationFieldToProperty(ValidationField $field): array
    {
        $property = $this->mapType($field->type());

        $format = $this->resolveFormatFromRules($field->rules());

        if ($format !== null) {
            $property['format'] = $format;
        }

        return $property;
    }

    /**
     * @param array<int, string> $rules
     */
    private function resolveFormatFromRules(array $rules): ?string
    {
        foreach ($rules as $rule) {
            $ruleName = explode(':', $rule, 2)[0];

            $format = match ($ruleName) {
                'email' => 'email',
                'url' => 'uri',
                'ip' => 'ipv4',
                'date' => 'date',
                'file' => 'binary',
                'image' => 'binary',
                default => null,
            };

            if ($format !== null) {
                return $format;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapType(string $type): array
    {
        return self::TYPE_MAP[$type] ?? ['type' => 'string'];
    }
}
