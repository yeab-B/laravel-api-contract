<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Services\Postman;

use Yab\LaravelApiContract\Contracts\ApiContractContract;
use Yab\LaravelApiContract\Services\Contract\EndpointDefinition;
use Yab\LaravelApiContract\Services\DTO\RequestDefinition;
use Yab\LaravelApiContract\Services\DTO\ValidationField;

class PostmanBuilder
{
    private const POSTMAN_SCHEMA = 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json';

    /**
     * @return array<string, mixed>
     */
    public function build(ApiContractContract $contract): array
    {
        $collection = [
            'info' => $this->buildInfo($contract),
            'item' => $this->buildItems($contract),
        ];

        $auth = $this->buildAuth($contract);

        if ($auth !== null) {
            $collection['auth'] = $auth;
        }

        $collection['variable'] = $this->buildVariables($contract);

        return $collection;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildInfo(ApiContractContract $contract): array
    {
        $info = [
            'name' => $contract->name(),
            'schema' => self::POSTMAN_SCHEMA,
        ];

        $description = $this->buildDescription($contract);

        if ($description !== '') {
            $info['description'] = $description;
        }

        return $info;
    }

    private function buildDescription(ApiContractContract $contract): string
    {
        $parts = [];

        $parts[] = 'API Version: ' . $contract->version();

        $metadata = $contract->metadata();

        if ($metadata !== []) {
            foreach ($metadata as $key => $value) {
                if (is_string($value)) {
                    $parts[] = ucfirst((string) $key) . ': ' . $value;
                }
            }
        }

        return implode("\n", $parts);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildItems(ApiContractContract $contract): array
    {
        $groups = $this->groupEndpoints($contract->endpoints());

        $items = [];

        foreach ($groups as $resource => $endpoints) {
            $folder = [
                'name' => $this->resourceToFolderName($resource),
                'item' => [],
            ];

            foreach ($endpoints as $endpoint) {
                $folder['item'][] = $this->buildRequestItem($endpoint);
            }

            $items[] = $folder;
        }

        return $items;
    }

    /**
     * @param array<int, EndpointDefinition> $endpoints
     * @return array<string, array<int, EndpointDefinition>>
     */
    private function groupEndpoints(array $endpoints): array
    {
        $groups = [];

        foreach ($endpoints as $endpoint) {
            $resource = $this->extractResource($endpoint->uri());

            if (!isset($groups[$resource])) {
                $groups[$resource] = [];
            }

            $groups[$resource][] = $endpoint;
        }

        ksort($groups);

        return $groups;
    }

    private function extractResource(string $uri): string
    {
        $parts = explode('/', $uri);

        $parts = array_values(array_filter($parts, static fn (string $part): bool => $part !== ''));

        return $parts[1] ?? 'default';
    }

    private function resourceToFolderName(string $resource): string
    {
        return ucfirst($resource);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRequestItem(EndpointDefinition $endpoint): array
    {
        $item = [
            'name' => $this->buildRequestName($endpoint),
            'request' => $this->buildRequest($endpoint),
        ];

        return $item;
    }

    private function buildRequestName(EndpointDefinition $endpoint): string
    {
        $method = $endpoint->method();
        $uri = $endpoint->uri();
        $hasPathParams = $endpoint->parameters() !== [];

        $resource = $this->extractResource($uri);
        $singular = rtrim((string) $resource, 's');

        return match ($method) {
            'GET' => $hasPathParams ? 'Get ' . ucfirst($singular) : 'List ' . ucfirst($resource),
            'POST' => 'Create ' . ucfirst($singular),
            'PUT' => 'Update ' . ucfirst($singular),
            'PATCH' => 'Patch ' . ucfirst($singular),
            'DELETE' => 'Delete ' . ucfirst($singular),
            default => $method . ' ' . ucfirst($singular),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRequest(EndpointDefinition $endpoint): array
    {
        $request = [
            'method' => $endpoint->method(),
            'header' => $this->buildHeaders($endpoint),
            'url' => $this->buildUrl($endpoint),
        ];

        $requestDefinition = $endpoint->request();

        if ($requestDefinition !== null) {
            $body = $this->buildBody($requestDefinition);

            if ($body !== null) {
                $request['body'] = $body;
            }
        }

        $description = $endpoint->name();

        if ($description !== null) {
            $request['description'] = $description;
        }

        return $request;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildHeaders(EndpointDefinition $endpoint): array
    {
        $headers = [];

        if ($endpoint->method() === 'POST' || $endpoint->method() === 'PUT' || $endpoint->method() === 'PATCH') {
            $headers[] = [
                'key' => 'Content-Type',
                'value' => 'application/json',
            ];
        }

        $headers[] = [
            'key' => 'Accept',
            'value' => 'application/json',
        ];

        return $headers;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildUrl(EndpointDefinition $endpoint): array
    {
        $path = $endpoint->uri();

        $variables = [];

        foreach ($endpoint->parameters() as $param) {
            $variables[] = [
                'key' => $param,
                'value' => '',
            ];
        }

        $url = [
            'raw' => $this->buildRawUrl($path, $endpoint->parameters()),
            'host' => ['{{base_url}}'],
            'path' => explode('/', $path),
        ];

        $pathWithParams = $this->replacePathParams($path);

        if ($pathWithParams !== $path) {
            $url['path'] = explode('/', $pathWithParams);
        }

        if ($variables !== []) {
            $url['variable'] = $variables;
        }

        return $url;
    }

    /**
     * @param array<int, string> $parameters
     */
    private function buildRawUrl(string $path, array $parameters): string
    {
        $url = '{{base_url}}/' . $path;

        foreach ($parameters as $param) {
            $url = str_replace('{' . $param . '}', '{{' . $param . '}}', $url);
        }

        return $url;
    }

    private function replacePathParams(string $path): string
    {
        $result = $path;

        foreach ($this->extractAllPathParams($path) as $param) {
            $result = str_replace('{' . $param . '}', ':' . $param, $result);
        }

        return $result;
    }

    /**
     * @return array<int, string>
     */
    private function extractAllPathParams(string $path): array
    {
        preg_match_all('/\{(\w+)\}/', $path, $matches);

        return $matches[1];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildBody(RequestDefinition $request): ?array
    {
        $fields = $request->fields();

        if ($fields === []) {
            return null;
        }

        $body = [];

        foreach ($fields as $field) {
            $body[$field->name()] = $this->exampleValue($field);
        }

        return [
            'mode' => 'raw',
            'raw' => json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'options' => [
                'raw' => [
                    'language' => 'json',
                ],
            ],
        ];
    }

    private function exampleValue(ValidationField $field): string
    {
        if ($field->type() === 'string') {
            return '';
        }

        if ($field->type() === 'integer' || $field->type() === 'int') {
            return '0';
        }

        if ($field->type() === 'boolean' || $field->type() === 'bool') {
            return 'false';
        }

        if ($field->type() === 'email') {
            return 'user@example.com';
        }

        if ($field->type() === 'array') {
            return '[]';
        }

        return '';
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildAuth(ApiContractContract $contract): ?array
    {
        $auth = $contract->authentication();

        if ($auth === '' || $auth === 'none') {
            return null;
        }

        return match ($auth) {
            'sanctum', 'passport', 'jwt', 'bearer' => [
                'type' => 'bearer',
                'bearer' => [
                    [
                        'key' => 'token',
                        'value' => '{{token}}',
                        'type' => 'string',
                    ],
                ],
            ],
            'apikey', 'api_key' => [
                'type' => 'apikey',
                'apikey' => [
                    [
                        'key' => 'value',
                        'value' => '{{token}}',
                        'type' => 'string',
                    ],
                    [
                        'key' => 'key',
                        'value' => 'Authorization',
                        'type' => 'string',
                    ],
                ],
            ],
            default => null,
        };
    }

    /**
     * @return array<int, array<string, string|bool>>
     */
    private function buildVariables(ApiContractContract $contract): array
    {
        $variables = [
            [
                'key' => 'base_url',
                'value' => 'http://localhost',
                'type' => 'string',
            ],
        ];

        $auth = $contract->authentication();

        if ($auth !== '' && $auth !== 'none') {
            $variables[] = [
                'key' => 'token',
                'value' => '',
                'type' => 'string',
            ];
        }

        return $variables;
    }
}
