<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Generators\Test;

use Yab\LaravelApiContract\Contracts\ApiContractContract;
use Yab\LaravelApiContract\Contracts\TestGeneratorContract;
use Yab\LaravelApiContract\Services\Contract\EndpointDefinition;
use Yab\LaravelApiContract\Services\DTO\ValidationField;
use Yab\LaravelApiContract\Services\Test\TestBuilder;

class TestGenerator implements TestGeneratorContract
{
    private const API_PREFIX = 'api';
    private const NAMESPACE = 'Tests\Feature\API';
    private const BASE_CLASS = 'Tests\TestCase';

    public function __construct(
        private readonly TestBuilder $builder,
    ) {
    }

    /**
     * @return array<int, array{filename: string, content: string}>
     */
    public function generate(ApiContractContract $contract): array
    {
        $files = [];

        $groups = $this->groupEndpointsByResource($contract->endpoints());

        foreach ($groups as $resource => $endpoints) {
            $className = $this->className($resource);
            $filename = $className . 'Test.php';

            $this->builder->reset();
            $this->buildTestFile($contract, $className, $resource, $endpoints);

            $files[] = [
                'filename' => $filename,
                'content' => $this->builder->getOutput(),
            ];
        }

        return $files;
    }

    /**
     * @param array<int, EndpointDefinition> $endpoints
     */
    private function buildTestFile(
        ApiContractContract $contract,
        string $className,
        string $resource,
        array $endpoints,
    ): void {
        $this->builder->writePhpTag();
        $this->builder->blankLine();
        $this->builder->writeDeclareStrict();
        $this->builder->blankLine();
        $this->builder->writeNamespace(self::NAMESPACE);
        $this->builder->blankLine();
        $this->builder->writeUse(self::BASE_CLASS);
        $this->builder->openClass($className . 'Test');

        foreach ($endpoints as $endpoint) {
            $this->buildSuccessTest($endpoint, $resource);
        }

        $hasAuth = $contract->authentication() !== '' && $contract->authentication() !== 'none';

        if ($hasAuth) {
            $firstEndpoint = $endpoints[0];
            $this->buildAuthTest($firstEndpoint);
        }

        foreach ($endpoints as $endpoint) {
            $this->buildValidationTests($endpoint);
        }

        $this->builder->closeClass();
    }

    private function buildSuccessTest(EndpointDefinition $endpoint, string $resource): void
    {
        $method = $endpoint->method();
        $hasParams = $endpoint->parameters() !== [];
        $uri = $this->testUri($endpoint);
        $testName = $this->successTestName($endpoint, $resource);

        $this->builder->openMethod($testName);

        $requestDefinition = $endpoint->request();

        if ($requestDefinition !== null && ($method === 'POST' || $method === 'PUT' || $method === 'PATCH')) {
            $fields = $requestDefinition->fields();

            if ($fields !== []) {
                $this->builder->writePayload($fields);
            }
        }

        if ($method === 'GET') {
            $this->builder->writeGet($uri);
        } elseif ($method === 'POST') {
            $this->builder->writePost($uri);
        } elseif ($method === 'PUT') {
            $this->builder->writePut($uri);
        } elseif ($method === 'PATCH') {
            $this->builder->writePatch($uri);
        } elseif ($method === 'DELETE') {
            $this->builder->writeDelete($uri);
        }

        if ($method === 'POST') {
            $this->builder->writeAssertCreated();
        } elseif ($method === 'DELETE') {
            $this->builder->writeAssertNoContent();
        } else {
            $this->builder->writeAssertOk();
        }

        $this->builder->closeMethod();
    }

    private function buildAuthTest(EndpointDefinition $endpoint): void
    {
        $uri = $this->testUri($endpoint);
        $method = $endpoint->method();

        $this->builder->openMethod('test_requires_authentication');

        if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
            $this->builder->writeEmptyPayload();
        }

        if ($method === 'GET') {
            $this->builder->writeGet($uri);
        } elseif ($method === 'POST') {
            $this->builder->writePost($uri);
        } elseif ($method === 'PUT') {
            $this->builder->writePut($uri);
        } elseif ($method === 'PATCH') {
            $this->builder->writePatch($uri);
        } elseif ($method === 'DELETE') {
            $this->builder->writeDelete($uri);
        }

        $this->builder->writeAssertUnauthorized();
        $this->builder->closeMethod();
    }

    private function buildValidationTests(EndpointDefinition $endpoint): void
    {
        if ($endpoint->method() !== 'POST' && $endpoint->method() !== 'PUT' && $endpoint->method() !== 'PATCH') {
            return;
        }

        $requestDefinition = $endpoint->request();

        if ($requestDefinition === null) {
            return;
        }

        $fields = $requestDefinition->fields();
        $uri = $this->testUri($endpoint);

        $requiredFields = array_values(array_filter(
            $fields,
            static fn (ValidationField $field): bool => $field->required(),
        ));

        if ($requiredFields === []) {
            return;
        }

        $skipFirst = count($requiredFields) > 1;
        $missingField = $requiredFields[0];

        $testName = $this->validationTestName($missingField);

        $this->builder->openMethod($testName);

        $optionalFields = array_values(array_filter(
            $fields,
            static fn (ValidationField $field): bool => !$field->required(),
        ));

        $partialPayload = [];

        if ($skipFirst) {
            foreach ($requiredFields as $field) {
                if ($field !== $missingField) {
                    $partialPayload[] = $field;
                }
            }

            foreach ($optionalFields as $field) {
                $partialPayload[] = $field;
            }
        }

        if ($partialPayload !== []) {
            $this->builder->writePayload($partialPayload);
        } else {
            $this->builder->writeEmptyPayload();
        }

        if ($endpoint->method() === 'POST') {
            $this->builder->writePost($uri);
        } elseif ($endpoint->method() === 'PUT') {
            $this->builder->writePut($uri);
        } elseif ($endpoint->method() === 'PATCH') {
            $this->builder->writePatch($uri);
        }

        $this->builder->writeAssertUnprocessable();
        $this->builder->closeMethod();
    }

    private function successTestName(EndpointDefinition $endpoint, string $resource): string
    {
        $method = $endpoint->method();
        $hasParams = $endpoint->parameters() !== [];
        $singular = $this->singularize($resource);

        return match ($method) {
            'GET' => $hasParams ? 'test_can_show_' . $singular : 'test_can_list_' . $resource,
            'POST' => 'test_can_create_' . $singular,
            'PUT' => 'test_can_update_' . $singular,
            'PATCH' => 'test_can_patch_' . $singular,
            'DELETE' => 'test_can_delete_' . $singular,
            default => 'test_' . strtolower($method) . '_' . $singular,
        };
    }

    private function validationTestName(ValidationField $field): string
    {
        return 'test_' . $field->name() . '_is_required';
    }

    /**
     * @param array<int, EndpointDefinition> $endpoints
     * @return array<string, array<int, EndpointDefinition>>
     */
    private function groupEndpointsByResource(array $endpoints): array
    {
        $groups = [];

        foreach ($endpoints as $endpoint) {
            $resource = $this->extractResourceName($endpoint->uri());

            if ($resource === null) {
                continue;
            }

            $groups[$resource][] = $endpoint;
        }

        return $groups;
    }

    private function extractResourceName(string $uri): ?string
    {
        $parts = explode('/', $uri);

        $segments = array_values(array_filter($parts, static fn (string $part): bool => $part !== ''));

        if ($segments === []) {
            return null;
        }

        if ($segments[0] === self::API_PREFIX) {
            if (isset($segments[1])) {
                $resource = $segments[1];

                if (str_starts_with($resource, '{')) {
                    return null;
                }

                return $resource;
            }

            return null;
        }

        $resource = $segments[0];

        if (str_starts_with($resource, '{')) {
            return null;
        }

        return $resource;
    }

    private function testUri(EndpointDefinition $endpoint): string
    {
        $uri = $endpoint->uri();

        foreach ($endpoint->parameters() as $param) {
            $uri = str_replace('{' . $param . '}', '1', $uri);
        }

        return '/' . $uri;
    }

    private function className(string $resource): string
    {
        return ucfirst($this->singularize($resource));
    }

    private function singularize(string $word): string
    {
        if (str_ends_with($word, 'ies')) {
            return substr($word, 0, -3) . 'y';
        }

        if (
            str_ends_with($word, 'ses') || str_ends_with($word, 'xes') ||
            str_ends_with($word, 'ches') || str_ends_with($word, 'shes')
        ) {
            return substr($word, 0, -2);
        }

        if (str_ends_with($word, 's') && !str_ends_with($word, 'ss')) {
            return substr($word, 0, -1);
        }

        return $word;
    }
}
