<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Services\Postman;

use PHPUnit\Framework\TestCase;
use Yab\LaravelApiContract\Services\Contract\ApiContract;
use Yab\LaravelApiContract\Services\Contract\EndpointDefinition;
use Yab\LaravelApiContract\Services\DTO\RequestDefinition;
use Yab\LaravelApiContract\Services\DTO\ResourceDefinition;
use Yab\LaravelApiContract\Services\DTO\ResponseField;
use Yab\LaravelApiContract\Services\DTO\ValidationField;
use Yab\LaravelApiContract\Services\Postman\PostmanBuilder;

class PostmanBuilderTest extends TestCase
{
    private PostmanBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new PostmanBuilder();
    }

    public function test_build_returns_minimal_collection(): void
    {
        $contract = new ApiContract(
            name: 'Test API',
            version: '1.0',
            endpoints: [],
            authentication: 'none',
        );

        $collection = $this->builder->build($contract);

        $this->assertSame('Test API', $collection['info']['name']);
        $this->assertSame(
            'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            $collection['info']['schema'],
        );
        $this->assertSame([], $collection['item']);
    }

    public function test_build_includes_description_with_version(): void
    {
        $contract = new ApiContract(
            name: 'Test API',
            version: '2.0',
            endpoints: [],
            authentication: 'none',
        );

        $collection = $this->builder->build($contract);

        $this->assertStringContainsString('API Version: 2.0', $collection['info']['description']);
    }

    public function test_build_includes_metadata_in_description(): void
    {
        $contract = new ApiContract(
            name: 'Test API',
            version: '1.0',
            endpoints: [],
            authentication: 'none',
            metadata: ['environment' => 'production', 'team' => 'backend'],
        );

        $collection = $this->builder->build($contract);

        $this->assertStringContainsString('Environment: production', $collection['info']['description']);
        $this->assertStringContainsString('Team: backend', $collection['info']['description']);
    }

    public function test_build_groups_endpoints_by_resource(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [
                new EndpointDefinition('GET', 'api/users', 'users.index', null, [], []),
                new EndpointDefinition('POST', 'api/users', 'users.store', null, [], []),
                new EndpointDefinition('GET', 'api/posts', 'posts.index', null, [], []),
            ],
            authentication: 'none',
        );

        $collection = $this->builder->build($contract);

        $this->assertCount(2, $collection['item']);

        $folderNames = array_map(fn (array $folder): string => $folder['name'], $collection['item']);
        $this->assertContains('Users', $folderNames);
        $this->assertContains('Posts', $folderNames);
    }

    public function test_build_get_endpoint(): void
    {
        $contract = $this->createContractWithEndpoint('GET', 'api/users');

        $collection = $this->builder->build($contract);

        $request = $collection['item'][0]['item'][0];

        $this->assertSame('List Users', $request['name']);
        $this->assertSame('GET', $request['request']['method']);
        $this->assertSame('{{base_url}}/api/users', $request['request']['url']['raw']);
    }

    public function test_build_post_endpoint(): void
    {
        $contract = $this->createContractWithEndpoint('POST', 'api/users');

        $collection = $this->builder->build($contract);

        $request = $collection['item'][0]['item'][0];

        $this->assertSame('Create User', $request['name']);
        $this->assertSame('POST', $request['request']['method']);
    }

    public function test_build_put_endpoint(): void
    {
        $contract = $this->createContractWithEndpoint('PUT', 'api/users/1');

        $collection = $this->builder->build($contract);

        $request = $collection['item'][0]['item'][0];

        $this->assertSame('Update User', $request['name']);
        $this->assertSame('PUT', $request['request']['method']);
    }

    public function test_build_patch_endpoint(): void
    {
        $contract = $this->createContractWithEndpoint('PATCH', 'api/users/1');

        $collection = $this->builder->build($contract);

        $request = $collection['item'][0]['item'][0];

        $this->assertSame('Patch User', $request['name']);
        $this->assertSame('PATCH', $request['request']['method']);
    }

    public function test_build_delete_endpoint(): void
    {
        $contract = $this->createContractWithEndpoint('DELETE', 'api/users/1');

        $collection = $this->builder->build($contract);

        $request = $collection['item'][0]['item'][0];

        $this->assertSame('Delete User', $request['name']);
        $this->assertSame('DELETE', $request['request']['method']);
    }

    public function test_build_endpoint_with_path_parameters(): void
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

        $collection = $this->builder->build($contract);

        $request = $collection['item'][0]['item'][0];

        $this->assertSame('{{base_url}}/api/users/{{user}}', $request['request']['url']['raw']);

        $variables = $request['request']['url']['variable'];
        $this->assertCount(1, $variables);
        $this->assertSame('user', $variables[0]['key']);
    }

    public function test_build_endpoint_with_request_body(): void
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
                    new ValidationField('email', 'email', true, ['required', 'email']),
                ],
                authorizeMethod: true,
                rawRules: ['name' => 'required|string', 'email' => 'required|email'],
            ),
        );

        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [$endpoint],
            authentication: 'none',
        );

        $collection = $this->builder->build($contract);

        $request = $collection['item'][0]['item'][0];
        $body = $request['request']['body'];

        $this->assertSame('raw', $body['mode']);
        $this->assertSame('json', $body['options']['raw']['language']);

        $decodedBody = json_decode($body['raw'], true);

        $this->assertArrayHasKey('name', $decodedBody);
        $this->assertArrayHasKey('email', $decodedBody);
        $this->assertSame('user@example.com', $decodedBody['email']);
    }

    public function test_build_endpoint_without_request_body_on_get(): void
    {
        $contract = $this->createContractWithEndpoint('GET', 'api/users');

        $collection = $this->builder->build($contract);

        $request = $collection['item'][0]['item'][0];

        $this->assertArrayNotHasKey('body', $request['request']);
    }

    public function test_build_sets_content_type_header_for_mutation_methods(): void
    {
        $contract = $this->createContractWithEndpoint('POST', 'api/users');

        $collection = $this->builder->build($contract);

        $request = $collection['item'][0]['item'][0];
        $headers = $request['request']['header'];

        $contentTypes = array_values(array_filter(
            $headers,
            fn (array $h): bool => $h['key'] === 'Content-Type',
        ));

        $this->assertCount(1, $contentTypes);
        $this->assertSame('application/json', $contentTypes[0]['value']);
    }

    public function test_build_sets_accept_header(): void
    {
        $contract = $this->createContractWithEndpoint('GET', 'api/users');

        $collection = $this->builder->build($contract);

        $request = $collection['item'][0]['item'][0];
        $headers = $request['request']['header'];

        $accepts = array_values(array_filter(
            $headers,
            fn (array $h): bool => $h['key'] === 'Accept',
        ));

        $this->assertCount(1, $accepts);
        $this->assertSame('application/json', $accepts[0]['value']);
    }

    public function test_build_with_bearer_authentication(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [],
            authentication: 'sanctum',
        );

        $collection = $this->builder->build($contract);

        $this->assertSame('bearer', $collection['auth']['type']);
        $this->assertSame('token', $collection['auth']['bearer'][0]['key']);
        $this->assertSame('{{token}}', $collection['auth']['bearer'][0]['value']);
    }

    public function test_build_with_passport_authentication(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [],
            authentication: 'passport',
        );

        $collection = $this->builder->build($contract);

        $this->assertSame('bearer', $collection['auth']['type']);
    }

    public function test_build_with_jwt_authentication(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [],
            authentication: 'jwt',
        );

        $collection = $this->builder->build($contract);

        $this->assertSame('bearer', $collection['auth']['type']);
    }

    public function test_build_with_api_key_authentication(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [],
            authentication: 'apikey',
        );

        $collection = $this->builder->build($contract);

        $this->assertSame('apikey', $collection['auth']['type']);
        $this->assertSame('Authorization', $collection['auth']['apikey'][1]['value']);
    }

    public function test_build_without_authentication_omits_auth(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [],
            authentication: 'none',
        );

        $collection = $this->builder->build($contract);

        $this->assertArrayNotHasKey('auth', $collection);
    }

    public function test_build_variables_include_base_url(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [],
            authentication: 'none',
        );

        $collection = $this->builder->build($contract);

        $variables = $collection['variable'];

        $baseUrlVar = array_values(array_filter(
            $variables,
            fn (array $v): bool => $v['key'] === 'base_url',
        ));

        $this->assertCount(1, $baseUrlVar);
        $this->assertSame('http://localhost', $baseUrlVar[0]['value']);
    }

    public function test_build_variables_include_token_when_authenticated(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [],
            authentication: 'sanctum',
        );

        $collection = $this->builder->build($contract);

        $tokenVar = array_values(array_filter(
            $collection['variable'],
            fn (array $v): bool => $v['key'] === 'token',
        ));

        $this->assertCount(1, $tokenVar);
    }

    public function test_build_variables_omit_token_when_unauthenticated(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [],
            authentication: 'none',
        );

        $collection = $this->builder->build($contract);

        $tokenVar = array_values(array_filter(
            $collection['variable'],
            fn (array $v): bool => $v['key'] === 'token',
        ));

        $this->assertCount(0, $tokenVar);
    }

    public function test_build_includes_endpoint_name_as_description(): void
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

        $collection = $this->builder->build($contract);

        $this->assertSame('users.index', $collection['item'][0]['item'][0]['request']['description']);
    }

    public function test_build_multiple_endpoints_in_same_folder(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [
                new EndpointDefinition('GET', 'api/users', 'users.index', null, [], []),
                new EndpointDefinition('POST', 'api/users', 'users.store', null, [], []),
                new EndpointDefinition('GET', 'api/users/{user}', 'users.show', null, [], ['user']),
                new EndpointDefinition('PUT', 'api/users/{user}', 'users.update', null, [], ['user']),
                new EndpointDefinition('DELETE', 'api/users/{user}', 'users.destroy', null, [], ['user']),
            ],
            authentication: 'none',
        );

        $collection = $this->builder->build($contract);

        $this->assertCount(1, $collection['item']);
        $this->assertSame('Users', $collection['item'][0]['name']);
        $this->assertCount(5, $collection['item'][0]['item']);
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
