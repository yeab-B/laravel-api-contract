<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Generators\Client;

use PHPUnit\Framework\TestCase;
use Yab\LaravelApiContract\Generators\Client\ClientGenerator;
use Yab\LaravelApiContract\Services\Client\ClientBuilder;
use Yab\LaravelApiContract\Services\Contract\ApiContract;
use Yab\LaravelApiContract\Services\Contract\EndpointDefinition;
use Yab\LaravelApiContract\Services\DTO\RequestDefinition;
use Yab\LaravelApiContract\Services\DTO\ResourceDefinition;
use Yab\LaravelApiContract\Services\DTO\ResponseField;
use Yab\LaravelApiContract\Services\DTO\ValidationField;
use Yab\LaravelApiContract\Support\TypeScriptTypeMapper;

class ClientGeneratorTest extends TestCase
{
    private ClientGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new ClientGenerator(
            builder: new ClientBuilder(),
            mapper: new TypeScriptTypeMapper(),
        );
    }

    public function test_generate_returns_empty_array_when_no_endpoints(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [],
            authentication: 'none',
        );

        $files = $this->generator->generate($contract);

        $this->assertSame([], $files);
    }

    public function test_generate_returns_client_file(): void
    {
        $endpoint = new EndpointDefinition(
            method: 'GET',
            uri: 'api/users',
            name: null,
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

        $files = $this->generator->generate($contract);

        $this->assertCount(2, $files);
        $this->assertSame('client.ts', $files[0]['filename']);
    }

    public function test_generate_creates_service_file(): void
    {
        $endpoint = new EndpointDefinition(
            method: 'GET',
            uri: 'api/users',
            name: null,
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

        $files = $this->generator->generate($contract);

        $this->assertCount(2, $files);
        $this->assertSame('user.service.ts', $files[1]['filename']);
    }

    public function test_generates_get_users_function(): void
    {
        $endpoint = new EndpointDefinition(
            method: 'GET',
            uri: 'api/users',
            name: null,
            controller: null,
            middleware: [],
            parameters: [],
            response: new ResourceDefinition(
                resourceClass: 'App\Http\Resources\UserResource',
                fields: [
                    new ResponseField('id', 'integer', false, '$this->id'),
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

        $files = $this->generator->generate($contract);
        $content = $files[1]['content'];

        $this->assertStringContainsString('getUsers', $content);
        $this->assertStringContainsString('/api/users', $content);
    }

    public function test_generates_get_user_function(): void
    {
        $endpoint = new EndpointDefinition(
            method: 'GET',
            uri: 'api/users/{user}',
            name: null,
            controller: null,
            middleware: [],
            parameters: ['user'],
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

        $files = $this->generator->generate($contract);
        $content = $files[1]['content'];

        $this->assertStringContainsString('getUser', $content);
        $this->assertStringContainsString('user: number', $content);
    }

    public function test_generates_all_crud_functions(): void
    {
        $endpoints = [
            new EndpointDefinition('GET', 'api/posts', null, null, [], []),
            new EndpointDefinition('GET', 'api/posts/{post}', null, null, [], ['post']),
            new EndpointDefinition('POST', 'api/posts', null, null, [], []),
            new EndpointDefinition('PUT', 'api/posts/{post}', null, null, [], ['post']),
            new EndpointDefinition('PATCH', 'api/posts/{post}', null, null, [], ['post']),
            new EndpointDefinition('DELETE', 'api/posts/{post}', null, null, [], ['post']),
        ];

        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: $endpoints,
            authentication: 'none',
        );

        $files = $this->generator->generate($contract);
        $content = $files[1]['content'];

        $this->assertStringContainsString('getPosts', $content);
        $this->assertStringContainsString('getPost', $content);
        $this->assertStringContainsString('createPost', $content);
        $this->assertStringContainsString('updatePost', $content);
        $this->assertStringContainsString('patchPost', $content);
        $this->assertStringContainsString('deletePost', $content);
    }

    public function test_generates_post_with_request_body(): void
    {
        $endpoint = new EndpointDefinition(
            method: 'POST',
            uri: 'api/users',
            name: null,
            controller: null,
            middleware: [],
            parameters: [],
            request: new RequestDefinition(
                className: 'App\Http\Requests\StoreUserRequest',
                fields: [
                    new ValidationField('name', 'string', true, ['required']),
                ],
                authorizeMethod: true,
                rawRules: ['name' => 'required'],
            ),
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

        $files = $this->generator->generate($contract);
        $content = $files[1]['content'];

        $this->assertStringContainsString('data: StoreUser', $content);
    }

    public function test_generates_put_with_parameter_and_body(): void
    {
        $endpoint = new EndpointDefinition(
            method: 'PUT',
            uri: 'api/users/{user}',
            name: null,
            controller: null,
            middleware: [],
            parameters: ['user'],
            request: new RequestDefinition(
                className: 'App\Http\Requests\UpdateUserRequest',
                fields: [
                    new ValidationField('name', 'string', true, ['required']),
                ],
                authorizeMethod: true,
                rawRules: ['name' => 'required'],
            ),
        );

        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [$endpoint],
            authentication: 'none',
        );

        $files = $this->generator->generate($contract);
        $content = $files[1]['content'];

        $this->assertStringContainsString('user: number', $content);
        $this->assertStringContainsString('data: UpdateUser', $content);
    }

    public function test_generates_delete_function(): void
    {
        $endpoint = new EndpointDefinition(
            method: 'DELETE',
            uri: 'api/users/{user}',
            name: null,
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

        $files = $this->generator->generate($contract);
        $content = $files[1]['content'];

        $this->assertStringContainsString('deleteUser', $content);
        $this->assertStringContainsString('user: number', $content);
    }

    public function test_groups_multiple_resources(): void
    {
        $endpoints = [
            new EndpointDefinition('GET', 'api/users', null, null, [], []),
            new EndpointDefinition('GET', 'api/posts', null, null, [], []),
        ];

        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: $endpoints,
            authentication: 'none',
        );

        $files = $this->generator->generate($contract);

        // client.ts + user.service.ts + post.service.ts
        $this->assertCount(3, $files);
        $filenames = array_map(fn ($f) => $f['filename'], $files);
        $this->assertContains('user.service.ts', $filenames);
        $this->assertContains('post.service.ts', $filenames);
    }

    public function test_client_file_has_axios_setup(): void
    {
        $endpoint = new EndpointDefinition('GET', 'api/users', null, null, [], []);

        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [$endpoint],
            authentication: 'none',
        );

        $files = $this->generator->generate($contract);
        $content = $files[0]['content'];

        $this->assertStringContainsString("import axios from 'axios'", $content);
        $this->assertStringContainsString('baseURL', $content);
        $this->assertStringContainsString('/api', $content);
    }

    public function test_client_file_with_sanctum_auth(): void
    {
        $endpoint = new EndpointDefinition('GET', 'api/users', null, null, [], []);

        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [$endpoint],
            authentication: 'sanctum',
        );

        $files = $this->generator->generate($contract);
        $content = $files[0]['content'];

        $this->assertStringContainsString('withCredentials', $content);
        $this->assertStringContainsString('withXSRFToken', $content);
    }

    public function test_client_file_with_bearer_auth(): void
    {
        $endpoint = new EndpointDefinition('GET', 'api/users', null, null, [], []);

        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [$endpoint],
            authentication: 'jwt',
        );

        $files = $this->generator->generate($contract);
        $content = $files[0]['content'];

        $this->assertStringContainsString('auth_token', $content);
        $this->assertStringContainsString('interceptors', $content);
    }

    public function test_service_file_imports_client(): void
    {
        $endpoint = new EndpointDefinition('GET', 'api/users', null, null, [], []);

        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [$endpoint],
            authentication: 'none',
        );

        $files = $this->generator->generate($contract);
        $content = $files[1]['content'];

        $this->assertStringContainsString("import { api } from './client'", $content);
    }

    public function test_skips_nested_uri_without_resource(): void
    {
        $endpoint = new EndpointDefinition('GET', 'api/{param}', null, null, [], ['param']);

        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [$endpoint],
            authentication: 'none',
        );

        $files = $this->generator->generate($contract);

        $this->assertSame([], $files);
    }

    public function test_multiple_params_in_uri(): void
    {
        $endpoint = new EndpointDefinition(
            method: 'GET',
            uri: 'api/posts/{post}/comments/{comment}',
            name: null,
            controller: null,
            middleware: [],
            parameters: ['post', 'comment'],
        );

        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [$endpoint],
            authentication: 'none',
        );

        $files = $this->generator->generate($contract);
        $content = $files[1]['content'];

        $this->assertStringContainsString('post: number', $content);
        $this->assertStringContainsString('comment: number', $content);
    }
}
