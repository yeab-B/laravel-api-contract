<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Generators\TypeScript;

use PHPUnit\Framework\TestCase;
use Yab\LaravelApiContract\Generators\TypeScript\TypeScriptGenerator;
use Yab\LaravelApiContract\Services\Contract\ApiContract;
use Yab\LaravelApiContract\Services\Contract\EndpointDefinition;
use Yab\LaravelApiContract\Services\DTO\RequestDefinition;
use Yab\LaravelApiContract\Services\DTO\ResourceDefinition;
use Yab\LaravelApiContract\Services\DTO\ResponseField;
use Yab\LaravelApiContract\Services\DTO\ValidationField;
use Yab\LaravelApiContract\Services\TypeScript\TypeScriptBuilder;
use Yab\LaravelApiContract\Support\TypeScriptTypeMapper;

class TypeScriptGeneratorTest extends TestCase
{
    private TypeScriptGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new TypeScriptGenerator(
            builder: new TypeScriptBuilder(),
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

    public function test_generate_returns_single_file(): void
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

        $files = $this->generator->generate($contract);

        $this->assertCount(1, $files);
        $this->assertSame('api.ts', $files[0]['filename']);
    }

    public function test_generate_creates_resource_interface(): void
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
                    new ResponseField('name', 'string', false, '$this->name'),
                    new ResponseField('email', 'email', false, '$this->email'),
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
        $content = $files[0]['content'];

        $this->assertStringContainsString('export interface User {', $content);
        $this->assertStringContainsString('id: number;', $content);
        $this->assertStringContainsString('name: string;', $content);
        $this->assertStringContainsString('email: string;', $content);
    }

    public function test_generate_creates_request_interface(): void
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
                    new ValidationField('bio', 'string', false, ['nullable', 'string']),
                ],
                authorizeMethod: true,
                rawRules: [
                    'name' => 'required|string',
                    'email' => 'required|email',
                    'bio' => 'nullable|string',
                ],
            ),
        );

        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [$endpoint],
            authentication: 'none',
        );

        $files = $this->generator->generate($contract);
        $content = $files[0]['content'];

        $this->assertStringContainsString('export interface StoreUser {', $content);
        $this->assertStringContainsString('name: string;', $content);
        $this->assertStringContainsString('email: string;', $content);
    }

    public function test_generate_handles_nullable_fields(): void
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
                    new ResponseField('email', 'email', true, '$this->email'),
                    new ResponseField('bio', 'string', true, '$this->bio'),
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
        $content = $files[0]['content'];

        $this->assertStringContainsString('email: string | null;', $content);
        $this->assertStringContainsString('bio: string | null;', $content);
    }

    public function test_generate_handles_optional_request_fields(): void
    {
        $endpoint = new EndpointDefinition(
            method: 'POST',
            uri: 'api/users',
            name: null,
            controller: null,
            middleware: [],
            parameters: [],
            request: new RequestDefinition(
                className: 'App\Http\Requests\UpdateUserRequest',
                fields: [
                    new ValidationField('name', 'string', true, ['required', 'string']),
                    new ValidationField('bio', 'string', false, ['nullable', 'string']),
                ],
                authorizeMethod: true,
                rawRules: [
                    'name' => 'required|string',
                    'bio' => 'nullable|string',
                ],
            ),
        );

        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [$endpoint],
            authentication: 'none',
        );

        $files = $this->generator->generate($contract);
        $content = $files[0]['content'];

        $this->assertStringContainsString('name: string;', $content);
        $this->assertStringContainsString('bio: string | null;', $content);
    }

    public function test_generate_handles_single_relationship(): void
    {
        $endpoint = new EndpointDefinition(
            method: 'GET',
            uri: 'api/users/1',
            name: null,
            controller: null,
            middleware: [],
            parameters: [],
            response: new ResourceDefinition(
                resourceClass: 'App\Http\Resources\UserResource',
                fields: [
                    new ResponseField('id', 'integer', false, '$this->id'),
                    new ResponseField('profile', 'object', false, null, 'App\Http\Resources\ProfileResource', false),
                ],
                relationships: ['profile'],
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
        $content = $files[0]['content'];

        $this->assertStringContainsString('profile: Profile;', $content);
    }

    public function test_generate_handles_collection_relationship(): void
    {
        $endpoint = new EndpointDefinition(
            method: 'GET',
            uri: 'api/users/1',
            name: null,
            controller: null,
            middleware: [],
            parameters: [],
            response: new ResourceDefinition(
                resourceClass: 'App\Http\Resources\UserResource',
                fields: [
                    new ResponseField('id', 'integer', false, '$this->id'),
                    new ResponseField('posts', 'object', false, null, 'App\Http\Resources\PostResource', true),
                ],
                relationships: ['posts'],
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
        $content = $files[0]['content'];

        $this->assertStringContainsString('posts: Post[];', $content);
    }

    public function test_generate_deduplicates_interfaces(): void
    {
        $endpoint1 = new EndpointDefinition(
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
                collection: false,
            ),
        );

        $endpoint2 = new EndpointDefinition(
            method: 'GET',
            uri: 'api/users/1',
            name: null,
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
                collection: false,
            ),
        );

        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [$endpoint1, $endpoint2],
            authentication: 'none',
        );

        $files = $this->generator->generate($contract);

        // First endpoint's User interface wins, second is ignored since name already exists
        $content = $files[0]['content'];
        $this->assertStringContainsString('id: number;', $content);
        $this->assertStringNotContainsString('name: string;', $content);
    }

    public function test_generate_includes_both_resource_and_request_types(): void
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
                    new ValidationField('name', 'string', true, ['required']),
                ],
                authorizeMethod: true,
                rawRules: ['name' => 'required'],
            ),
            response: new ResourceDefinition(
                resourceClass: 'App\Http\Resources\UserResource',
                fields: [
                    new ResponseField('id', 'integer', false, '$this->id'),
                    new ResponseField('name', 'string', false, '$this->name'),
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
        $content = $files[0]['content'];

        $this->assertStringContainsString('export interface User {', $content);
        $this->assertStringContainsString('export interface StoreUser {', $content);
    }

    public function test_generate_handles_boolean_fields(): void
    {
        $endpoint = new EndpointDefinition(
            method: 'GET',
            uri: 'api/users/1',
            name: null,
            controller: null,
            middleware: [],
            parameters: [],
            response: new ResourceDefinition(
                resourceClass: 'App\Http\Resources\UserResource',
                fields: [
                    new ResponseField('active', 'boolean', false, '$this->active'),
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
        $content = $files[0]['content'];

        $this->assertStringContainsString('active: boolean;', $content);
    }

    public function test_generate_handles_float_fields(): void
    {
        $endpoint = new EndpointDefinition(
            method: 'GET',
            uri: 'api/users/1',
            name: null,
            controller: null,
            middleware: [],
            parameters: [],
            response: new ResourceDefinition(
                resourceClass: 'App\Http\Resources\UserResource',
                fields: [
                    new ResponseField('price', 'float', false, '$this->price'),
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
        $content = $files[0]['content'];

        $this->assertStringContainsString('price: number;', $content);
    }

    public function test_generate_adds_header_comment(): void
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
        $content = $files[0]['content'];

        $this->assertStringContainsString('Auto-generated by laravel-api-contract', $content);
        $this->assertStringContainsString('Do not edit manually', $content);
    }
}
