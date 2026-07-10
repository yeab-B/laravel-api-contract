<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Support;

class TypeScriptTypeMapper
{
    private const MAP = [
        'integer' => 'number',
        'float' => 'number',
        'string' => 'string',
        'boolean' => 'boolean',
        'array' => 'any[]',
        'object' => 'Record<string, any>',
        'datetime' => 'string',
        'email' => 'string',
        'url' => 'string',
        'date' => 'string',
        'file' => 'string',
        'image' => 'string',
        'ip' => 'string',
        'json' => 'Record<string, any>',
        'mixed' => 'any',
        'null' => 'null',
    ];

    public function toTypeScript(string $phpType): string
    {
        return self::MAP[$phpType] ?? 'string';
    }

    /**
     * Convert a relationship class name to its TypeScript interface name.
     */
    public function relationToInterface(string $relationClass): string
    {
        $shortName = class_basename($relationClass);

        return preg_replace('/Resource$/', '', $shortName) ?: $shortName;
    }

    /**
     * Convert a request class name to its TypeScript interface name.
     */
    public function requestToInterface(string $requestClass): string
    {
        $shortName = class_basename($requestClass);

        return preg_replace('/Request$/', '', $shortName) ?: $shortName;
    }
}
