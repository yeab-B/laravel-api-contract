<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Services\Markdown;

use Yab\LaravelApiContract\Contracts\ApiContractContract;
use Yab\LaravelApiContract\Services\Contract\EndpointDefinition;
use Yab\LaravelApiContract\Services\DTO\RequestDefinition;
use Yab\LaravelApiContract\Services\DTO\ResourceDefinition;
use Yab\LaravelApiContract\Services\DTO\ResponseField;
use Yab\LaravelApiContract\Services\DTO\ValidationField;

class MarkdownBuilder
{
    private string $output = '';

    public function build(ApiContractContract $contract): string
    {
        $this->output = '';

        $this->addTitle($contract);
        $this->newLine();
        $this->addVersion($contract);
        $this->newLine();
        $this->addAuthentication($contract);
        $this->newLine();
        $this->addTableOfContents($contract);
        $this->newLine();
        $this->addEndpoints($contract);

        return $this->output;
    }

    private function addTitle(ApiContractContract $contract): void
    {
        $this->line('# ' . $contract->name() . ' API');
    }

    private function addVersion(ApiContractContract $contract): void
    {
        $this->line('**Version:** ' . $contract->version());
    }

    private function addAuthentication(ApiContractContract $contract): void
    {
        $auth = $contract->authentication();

        if ($auth === '' || $auth === 'none') {
            return;
        }

        $this->line('## Authentication');
        $this->newLine();

        $label = match ($auth) {
            'sanctum' => 'Sanctum (Laravel)',
            'passport' => 'OAuth (Passport)',
            'jwt' => 'JWT Token',
            'bearer' => 'Bearer Token',
            'apikey', 'api_key' => 'API Key',
            default => ucfirst($auth),
        };

        $this->line('**Type:** ' . $label);
        $this->newLine();

        if ($auth === 'sanctum' || $auth === 'passport' || $auth === 'jwt' || $auth === 'bearer') {
            $this->line('The API uses bearer token authentication. Include the token in the `Authorization` header:');
            $this->newLine();
            $this->codeBlock('Authorization: Bearer {token}');
        } elseif ($auth === 'apikey' || $auth === 'api_key') {
            $this->line('The API uses API key authentication. Include the key in the `Authorization` header:');
            $this->newLine();
            $this->codeBlock('Authorization: {api_key}');
        }

        $this->newLine();
        $this->line('---');
    }

    private function addTableOfContents(ApiContractContract $contract): void
    {
        $endpoints = $contract->endpoints();

        if ($endpoints === []) {
            return;
        }

        $this->line('## API Endpoints');
        $this->newLine();

        /** @var array<string, array<int, EndpointDefinition>> $groups */
        $groups = $this->groupEndpoints($endpoints);

        foreach ($groups as $resource => $resourceEndpoints) {
            $folderName = $this->resourceLabel($resource);
            $folderAnchor = $this->anchor($folderName);

            $this->line('- [' . $folderName . '](#' . $folderAnchor . ')');

            foreach ($resourceEndpoints as $endpoint) {
                $itemName = $endpoint->method() . ' /' . $endpoint->uri();
                $itemAnchor = $this->anchor($endpoint->method() . ' ' . $endpoint->uri());

                $this->line('  - [' . $itemName . '](#' . $itemAnchor . ')');
            }
        }

        $this->newLine();
        $this->line('---');
    }

    private function addEndpoints(ApiContractContract $contract): void
    {
        $endpoints = $contract->endpoints();

        if ($endpoints === []) {
            return;
        }

        /** @var array<string, array<int, EndpointDefinition>> $groups */
        $groups = $this->groupEndpoints($endpoints);

        foreach ($groups as $resource => $resourceEndpoints) {
            $this->line('## ' . $this->resourceLabel($resource));
            $this->newLine();

            foreach ($resourceEndpoints as $endpoint) {
                $this->addEndpoint($endpoint);
                $this->newLine();
            }

            $this->line('---');
            $this->newLine();
        }
    }

    private function addEndpoint(EndpointDefinition $endpoint): void
    {
        $method = $endpoint->method();
        $uri = $endpoint->uri();

        $this->line('### ' . $method . ' /' . $uri);
        $this->newLine();

        $description = $endpoint->name();

        if ($description !== null) {
            $this->line($description);
            $this->newLine();
        }

        $parameters = $endpoint->parameters();

        if ($parameters !== []) {
            $this->line('**Parameters:**');
            $this->newLine();
            $this->line('| Parameter | Type | Description |');
            $this->line('|-----------|------|-------------|');

            foreach ($parameters as $param) {
                $this->line('| `' . $param . '` | string | URL path parameter |');
            }

            $this->newLine();
        } else {
            $this->line('**Parameters:** None');
            $this->newLine();
        }

        $requestDefinition = $endpoint->request();

        if ($requestDefinition !== null) {
            $this->addRequestSection($requestDefinition);
        }

        $resourceDefinition = $endpoint->response();

        if ($resourceDefinition !== null) {
            $this->addResponseSection($resourceDefinition);
        }
    }

    private function addRequestSection(RequestDefinition $request): void
    {
        $fields = $request->fields();

        if ($fields === []) {
            return;
        }

        $this->line('**Request Body:**');
        $this->newLine();
        $this->line('| Field | Type | Required | Description |');
        $this->line('|-------|------|----------|-------------|');

        foreach ($fields as $field) {
            $required = $field->required() ? 'Yes' : 'No';
            $description = $this->fieldDescription($field);
            $this->line(
                '| `' . $field->name() . '` | ' . $field->type() .
                ' | ' . $required . ' | ' . $description . ' |'
            );
        }

        $this->newLine();
        $this->addExampleRequest($request);
    }

    private function addExampleRequest(RequestDefinition $request): void
    {
        $fields = $request->fields();

        if ($fields === []) {
            return;
        }

        $example = [];

        foreach ($fields as $field) {
            $example[$field->name()] = $this->exampleValue($field);
        }

        $json = json_encode($example, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            return;
        }

        $this->line('**Example Request:**');
        $this->newLine();
        $this->codeBlock($json);
        $this->newLine();
    }

    private function addResponseSection(ResourceDefinition $resource): void
    {
        $fields = $resource->fields();

        if ($fields === []) {
            return;
        }

        $this->line('**Response:**');
        $this->newLine();
        $this->line('| Field | Type |');

        if ($this->hasNestedResponses($fields)) {
            $this->line('|-------|------|-------------|');
        } else {
            $this->line('|-------|------|');
        }

        foreach ($fields as $field) {
            $type = $this->responseFieldType($field);
            $this->line('| `' . $field->name() . '` | ' . $type . ' |');
        }

        $this->newLine();
        $this->addExampleResponse($resource);
    }

    /**
     * @param array<int, ResponseField> $fields
     */
    private function hasNestedResponses(array $fields): bool
    {
        foreach ($fields as $field) {
            if ($field->isRelationship()) {
                return true;
            }
        }

        return false;
    }

    private function responseFieldType(ResponseField $field): string
    {
        $type = $field->type();

        if ($field->nullable()) {
            $type .= ' (nullable)';
        }

        if ($field->isRelationship()) {
            $relation = $field->relationClass() ?? 'Unknown';

            $parts = explode('\\', $relation);

            $type .= ' → ' . end($parts);

            if ($field->collection()) {
                $type .= '[]';
            }
        }

        return $type;
    }

    private function addExampleResponse(ResourceDefinition $resource): void
    {
        $fields = $resource->fields();

        if ($fields === []) {
            return;
        }

        $example = [];

        foreach ($fields as $field) {
            if ($field->isRelationship()) {
                $example[$field->name()] = $field->collection() ? [] : null;
            } else {
                $example[$field->name()] = $this->responseExampleValue($field);
            }
        }

        if ($resource->collection()) {
            $json = json_encode([$example], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            $json = json_encode($example, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        if ($json === false) {
            return;
        }

        $this->line('**Example Response:**');
        $this->newLine();
        $this->codeBlock($json);
        $this->newLine();
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

    private function resourceLabel(string $resource): string
    {
        return ucfirst($resource);
    }

    private function anchor(string $text): string
    {
        $lower = strtolower($text);
        $stripped = preg_replace('/[^a-z0-9\s-]/', '', $lower);
        $text = $stripped ?? $lower;
        $text = str_replace(' ', '-', $text);
        $collapsed = preg_replace('/-+/', '-', $text);

        return trim($collapsed ?? $text, '-');
    }

    private function fieldDescription(ValidationField $field): string
    {
        $parts = [];

        $rules = $field->rules();

        if ($rules !== []) {
            $parts[] = implode(', ', array_slice($rules, 0, 3));

            if (count($rules) > 3) {
                $parts[0] .= '…';
            }
        }

        return $parts !== [] ? implode(' ', $parts) : '-';
    }

    /**
     * @return string|int|bool|array<mixed>
     */
    private function exampleValue(ValidationField $field): string|int|bool|array
    {
        return match ($field->type()) {
            'string' => 'string',
            'email' => 'user@example.com',
            'integer', 'int' => 0,
            'boolean', 'bool' => false,
            'array' => [],
            default => 'string',
        };
    }

    /**
     * @return string|int|float|bool|array<mixed>
     */
    private function responseExampleValue(ResponseField $field): string|int|float|bool|array
    {
        return match ($field->type()) {
            'string' => 'string',
            'integer', 'int' => 0,
            'number', 'float', 'double' => 0.0,
            'boolean', 'bool' => false,
            'array' => [],
            default => 'string',
        };
    }

    private function line(string $text = ''): void
    {
        $this->output .= $text . "\n";
    }

    private function newLine(): void
    {
        $this->output .= "\n";
    }

    private function codeBlock(string $code, string $language = 'json'): void
    {
        $this->line('```' . $language);
        $this->line($code);
        $this->line('```');
    }
}
