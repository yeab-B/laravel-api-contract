<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Services\OpenApi;

use PHPUnit\Framework\TestCase;
use Yab\LaravelApiContract\Generators\Swagger\SchemaGenerator;
use Yab\LaravelApiContract\Services\Contract\ApiContract;
use Yab\LaravelApiContract\Services\Contract\EndpointDefinition;
use Yab\LaravelApiContract\Services\DTO\RequestDefinition;
use Yab\LaravelApiContract\Services\DTO\ResourceDefinition;
use Yab\LaravelApiContract\Services\DTO\ResponseField;
use Yab\LaravelApiContract\Services\DTO\ValidationField;
use Yab\LaravelApiContract\Services\OpenApi\OpenApiBuilder;

class OpenApiBuilderTest extends TestCase
{
    private OpenApiBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new OpenApiBuilder(
            schemaGenerator: new SchemaGenerator(),
        );
    }

    public function test_build_returns_minimal_document(): void
    {
        $contract = new ApiContract(
            name: 'Test API',
            version: '1.0',
            endpoints: [],
            authentication: 'none',
        );

        $document = $this->builder->build($contract);

        $this->assertSame('3.0.0', $document['openapi']);
        $this->assertSame('Test API', $document['info']['title']);
        $this->assertSame('1.0', $document['info']['version']);
        $this->assertSame([], $document['paths']);
        $this->assertArrayNotHasKey('components', $document);
    }

    public function test_build_with_get_endpoint(): void
    {
        $contract = $this->createContractWithEndpoint('GET', 'api/users');

        $document = $this->builder->build($contract);

        $this->assertArrayHasKey('/api/users', $document['paths']);
        $this->assertArrayHasKey('get', $document['paths']['/api/users']);
    }

    public function test_build_with_post_endpoint(): void
    {
        $contract = $this->createContractWithEndpoint('POST', 'api/users');

        $document = $this->builder->build($contract);

        $this->assertArrayHasKey('post', $document['paths']['/api/users']);
    }

    public function test_build_with_put_endpoint(): void
    {
        $contract = $this->createContractWithEndpoint('PUT', 'api/users/1');

        $document = $this->builder->build($contract);

        $this->assertArrayHasKey('put', $document['paths']['/api/users/1']);
    }

    public function test_build_with_delete_endpoint(): void
    {
        $contract = $this->createContractWithEndpoint('DELETE', 'api/users/1');

        $document = $this->builder->build($contract);

        $this->assertArrayHasKey('delete', $document['paths']['/api/users/1']);
    }

    public function test_build_with_endpoint_name_sets_operation_id(): void
    {
        $endpoint = new EndpointDefinition(
            method: 'GET',
            uri: 'api/users',
            name: 'users.index',
            controller: null,
            middleware: [],
            parameters: [],
        );

        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [$endpoint],
            authentication: 'none',
        );

        $document = $this->builder->build($contract);

        $this->assertSame('users.index', $document['paths']['/api/users']['get']['operationId']);
    }

    public function test_build_with_route_parameters(): void
    {
        $endpoint = new EndpointDefinition(
            method: 'GET',
            uri: 'api/users/{user}',
            name: 'users.show',
            controller: null,
            middleware: [],
            parameters: ['user'],
        );

        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [$endpoint],
            authentication: 'none',
        );

        $document = $this->builder->build($contract);

        $params = $document['paths']['/api/users/{user}']['get']['parameters'];

        $this->assertCount(1, $params);
        $this->assertSame('user', $params[0]['name']);
        $this->assertSame('path', $params[0]['in']);
        $this->assertTrue($params[0]['required']);
        $this->assertSame(['type' => 'string'], $params[0]['schema']);
    }

    public function test_build_with_request_body(): void
    {
        $endpoint = new EndpointDefinition(
            method: 'POST',
            uri: 'api/users',
            name: 'users.store',
            controller: null,
            middleware: [],
            parameters: [],
            request: new RequestDefinition(
                className: 'App\Http\Requests\StoreUserRequest',
                fields: [
                    new ValidationField('name', 'string', true, ['required', 'string']),
                ],
                authorizeMethod: true,
                rawRules: ['name' => 'required|string'],
            ),
        );

        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [$endpoint],
            authentication: 'none',
        );

        $document = $this->builder->build($contract);

        $requestBody = $document['paths']['/api/users']['post']['requestBody'];

        $this->assertNotNull($requestBody);
        $this->assertTrue($requestBody['required']);
        $this->assertArrayHasKey('application/json', $requestBody['content']);
        $this->assertSame(
            '#/components/schemas/StoreUserRequest',
            $requestBody['content']['application/json']['schema']['$ref'],
        );

        $this->assertArrayHasKey('StoreUserRequest', $document['components']['schemas']);
    }

    public function test_build_with_response(): void
    {
        $endpoint = new EndpointDefinition(
            method: 'GET',
            uri: 'api/users',
            name: 'users.index',
            controller: null,
            middleware: [],
            parameters: [],
            response: new ResourceDefinition(
                resourceClass: 'App\Http\Resources\UserResource',
                fields: [
                    new ResponseField('id', 'integer', false, '$this->id'),
                    new ResponseField('name', 'string', false, '$this->name'),
                ],
                relationships: [],
                collection: true,
            ),
        );

        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [$endpoint],
            authentication: 'none',
        );

        $document = $this->builder->build($contract);

        $this->assertArrayHasKey('User', $document['components']['schemas']);

        $response = $document['paths']['/api/users']['get']['responses']['200'];
        $this->assertArrayHasKey('content', $response);
        $this->assertSame(
            'array',
            $response['content']['application/json']['schema']['type'],
        );
    }

    public function test_build_with_single_response(): void
    {
        $endpoint = new EndpointDefinition(
            method: 'GET',
            uri: 'api/users/1',
            name: 'users.show',
            controller: null,
            middleware: [],
            parameters: ['id'],
            response: new ResourceDefinition(
                resourceClass: 'App\Http\Resources\UserResource',
                fields: [
                    new ResponseField('id', 'integer', false, '$this->id'),
                ],
                relationships: [],
                collection: false,
            ),
        );

        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [$endpoint],
            authentication: 'none',
        );

        $document = $this->builder->build($contract);

        $response = $document['paths']['/api/users/1']['get']['responses']['200'];
        $this->assertSame(
            '#/components/schemas/User',
            $response['content']['application/json']['schema']['$ref'],
        );
    }

    public function test_build_with_authentication_adds_security(): void
    {
        $contract = new ApiContract(
            name: 'Authenticated API',
            version: '1.0',
            endpoints: [],
            authentication: 'sanctum',
        );

        $document = $this->builder->build($contract);

        $this->assertArrayHasKey('securitySchemes', $document['components']);
        $this->assertArrayHasKey('bearerAuth', $document['components']['securitySchemes']);
        $this->assertSame('http', $document['components']['securitySchemes']['bearerAuth']['type']);
        $this->assertSame('bearer', $document['components']['securitySchemes']['bearerAuth']['scheme']);

        $this->assertArrayHasKey('security', $document);
        $this->assertSame(['bearerAuth' => []], $document['security'][0]);
    }

    public function test_build_without_authentication_omits_security(): void
    {
        $contract = new ApiContract(
            name: 'Public API',
            version: '1.0',
            endpoints: [],
            authentication: 'none',
        );

        $document = $this->builder->build($contract);

        $this->assertArrayNotHasKey('security', $document);
        $this->assertArrayNotHasKey('components', $document);
    }

    public function test_build_with_session_auth_omits_security(): void
    {
        $contract = new ApiContract(
            name: 'Session Auth',
            version: '1.0',
            endpoints: [],
            authentication: 'session',
        );

        $document = $this->builder->build($contract);

        $this->assertArrayNotHasKey('security', $document);
    }

    public function test_build_default_response_codes(): void
    {
        $contract = $this->createContractWithEndpoint('GET', 'api/users');

        $document = $this->builder->build($contract);

        $this->assertArrayHasKey('200', $document['paths']['/api/users']['get']['responses']);
        $this->assertArrayHasKey('401', $document['paths']['/api/users']['get']['responses']);
        $this->assertArrayHasKey('403', $document['paths']['/api/users']['get']['responses']);
    }

    public function test_build_post_response_code_is_201(): void
    {
        $contract = $this->createContractWithEndpoint('POST', 'api/users');

        $document = $this->builder->build($contract);

        $this->assertArrayHasKey('201', $document['paths']['/api/users']['post']['responses']);
        $this->assertSame('Created', $document['paths']['/api/users']['post']['responses']['201']['description']);
    }

    public function test_build_delete_response_code_is_204(): void
    {
        $contract = $this->createContractWithEndpoint('DELETE', 'api/users/1');

        $document = $this->builder->build($contract);

        $this->assertArrayHasKey('204', $document['paths']['/api/users/1']['delete']['responses']);
        $this->assertSame('No Content', $document['paths']['/api/users/1']['delete']['responses']['204']['description']);
    }

    private function createContractWithEndpoint(string $method, string $uri): ApiContract
    {
        $endpoint = new EndpointDefinition(
            method: $method,
            uri: $uri,
            name: null,
            controller: null,
            middleware: [],
            parameters: [],
        );

        return new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [$endpoint],
            authentication: 'none',
        );
    }
}
