<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Services\OpenApi;

use Yab\LaravelApiContract\Contracts\ApiContractContract;
use Yab\LaravelApiContract\Generators\Swagger\SchemaGenerator;
use Yab\LaravelApiContract\Services\Contract\EndpointDefinition;
use Yab\LaravelApiContract\Services\DTO\ResourceDefinition;

class OpenApiBuilder
{
    public function __construct(
        private readonly SchemaGenerator $schemaGenerator,
    ) {
    }

    /**
     * Build the full OpenAPI 3.0 document as an array.
     *
     * @return array<string, mixed>
     */
    public function build(ApiContractContract $contract): array
    {
        $paths = [];
        $schemas = [];
        $securitySchemes = [];

        foreach ($contract->endpoints() as $endpoint) {
            $path = $this->normalizePath($endpoint->uri());
            $operation = $this->buildOperation($endpoint);

            $operation['parameters'] = $this->buildParameters($endpoint);
            $operation['responses'] = $this->buildResponses($endpoint);

            $request = $endpoint->request();

            if ($request !== null) {
                $schemaName = $this->schemaGenerator->deriveRequestSchemaName($request->className());
                $schemas[$schemaName] = $this->schemaGenerator->requestToSchema($request);

                $operation['requestBody'] = [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/' . $schemaName],
                        ],
                    ],
                ];
            }

            $response = $endpoint->response();

            if ($response !== null) {
                $schemas += $this->schemaGenerator->resourceToSchema($response);

                $responseContent = $this->buildResponseContent($response);
                $operation['responses']['200']['content'] = $responseContent;
            }

            if ($endpoint->name() !== null) {
                $operation['operationId'] = $endpoint->name();
            }

            $paths[$path][strtolower($endpoint->method())] = $operation;
        }

        if ($this->hasAuthentication($contract)) {
            $securitySchemes = $this->buildSecuritySchemes($contract->authentication());
        }

        $document = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => $contract->name(),
                'version' => $contract->version(),
            ],
            'paths' => $paths,
        ];

        if ($schemas !== [] || $securitySchemes !== []) {
            $document['components'] = [];

            if ($schemas !== []) {
                $document['components']['schemas'] = $schemas;
            }

            if ($securitySchemes !== []) {
                $document['components']['securitySchemes'] = $securitySchemes;
                $document['security'] = [
                    [key($securitySchemes) => []],
                ];
            }
        }

        return $document;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildOperation(EndpointDefinition $endpoint): array
    {
        $operation = [
            'summary' => $endpoint->name() ?? $endpoint->method() . ' ' . $endpoint->uri(),
        ];

        return $operation;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildParameters(EndpointDefinition $endpoint): array
    {
        $parameters = [];

        foreach ($endpoint->parameters() as $param) {
            $parameters[] = [
                'name' => $param,
                'in' => 'path',
                'required' => true,
                'schema' => [
                    'type' => 'string',
                ],
            ];
        }

        return $parameters;
    }

    /**
     * @return array<int|string, array<string, string>>
     */
    private function buildResponses(EndpointDefinition $endpoint): array
    {
        if ($endpoint->method() === 'POST' || $endpoint->method() === 'PUT' || $endpoint->method() === 'PATCH') {
            return [
                '201' => ['description' => 'Created'],
                '401' => ['description' => 'Unauthenticated'],
                '403' => ['description' => 'Forbidden'],
            ];
        }

        if ($endpoint->method() === 'DELETE') {
            return [
                '204' => ['description' => 'No Content'],
                '401' => ['description' => 'Unauthenticated'],
                '403' => ['description' => 'Forbidden'],
            ];
        }

        return [
            '200' => ['description' => 'Successful response'],
            '401' => ['description' => 'Unauthenticated'],
            '403' => ['description' => 'Forbidden'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildResponseContent(ResourceDefinition $resource): array
    {
        $schemaName = $this->schemaGenerator->deriveSchemaName($resource->resourceClass());

        if ($resource->collection()) {
            return [
                'application/json' => [
                    'schema' => [
                        'type' => 'array',
                        'items' => ['$ref' => '#/components/schemas/' . $schemaName],
                    ],
                ],
            ];
        }

        return [
            'application/json' => [
                'schema' => ['$ref' => '#/components/schemas/' . $schemaName],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSecuritySchemes(string $auth): array
    {
        return [
            'bearerAuth' => [
                'type' => 'http',
                'scheme' => 'bearer',
            ],
        ];
    }

    private function hasAuthentication(ApiContractContract $contract): bool
    {
        $auth = $contract->authentication();

        return $auth !== '' && $auth !== 'none' && $auth !== 'session';
    }

    private function normalizePath(string $uri): string
    {
        return '/' . $uri;
    }
}
