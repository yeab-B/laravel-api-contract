<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Services\Markdown;

use PHPUnit\Framework\TestCase;
use Yab\LaravelApiContract\Services\Contract\ApiContract;
use Yab\LaravelApiContract\Services\Contract\EndpointDefinition;
use Yab\LaravelApiContract\Services\DTO\RequestDefinition;
use Yab\LaravelApiContract\Services\DTO\ResourceDefinition;
use Yab\LaravelApiContract\Services\DTO\ResponseField;
use Yab\LaravelApiContract\Services\DTO\ValidationField;
use Yab\LaravelApiContract\Services\Markdown\MarkdownBuilder;

class MarkdownBuilderTest extends TestCase
{
    private MarkdownBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new MarkdownBuilder();
    }

    public function test_build_returns_title_and_version(): void
    {
        $contract = new ApiContract(
            name: 'Test API',
            version: '1.0',
            endpoints: [],
            authentication: 'none',
        );

        $markdown = $this->builder->build($contract);

        $this->assertStringContainsString('# Test API API', $markdown);
        $this->assertStringContainsString('**Version:** 1.0', $markdown);
    }

    public function test_build_empty_contract_omits_toc_and_endpoints(): void
    {
        $contract = new ApiContract(
            name: 'Empty',
            version: '1.0',
            endpoints: [],
            authentication: 'none',
        );

        $markdown = $this->builder->build($contract);

        $this->assertStringNotContainsString('## API Endpoints', $markdown);
        $this->assertStringNotContainsString('## Authentication', $markdown);
    }

    public function test_build_omits_authentication_for_none(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [],
            authentication: 'none',
        );

        $markdown = $this->builder->build($contract);

        $this->assertStringNotContainsString('## Authentication', $markdown);
    }

    public function test_build_with_sanctum_auth(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [],
            authentication: 'sanctum',
        );

        $markdown = $this->builder->build($contract);

        $this->assertStringContainsString('## Authentication', $markdown);
        $this->assertStringContainsString('Sanctum (Laravel)', $markdown);
        $this->assertStringContainsString('Authorization: Bearer {token}', $markdown);
    }

    public function test_build_with_bearer_auth(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [],
            authentication: 'bearer',
        );

        $markdown = $this->builder->build($contract);

        $this->assertStringContainsString('Bearer Token', $markdown);
    }

    public function test_build_with_apikey_auth(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [],
            authentication: 'apikey',
        );

        $markdown = $this->builder->build($contract);

        $this->assertStringContainsString('API Key', $markdown);
        $this->assertStringContainsString('Authorization: {api_key}', $markdown);
    }

    public function test_build_generates_table_of_contents(): void
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

        $markdown = $this->builder->build($contract);

        $this->assertStringContainsString('## API Endpoints', $markdown);
        $this->assertStringContainsString('- [Users]', $markdown);
        $this->assertStringContainsString('- [Posts]', $markdown);
        $this->assertStringContainsString('GET /api/users', $markdown);
        $this->assertStringContainsString('POST /api/users', $markdown);
        $this->assertStringContainsString('GET /api/posts', $markdown);
    }

    public function test_build_generates_endpoint_sections(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [
                new EndpointDefinition('GET', 'api/users', 'users.index', null, [], []),
            ],
            authentication: 'none',
        );

        $markdown = $this->builder->build($contract);

        $this->assertStringContainsString('## Users', $markdown);
        $this->assertStringContainsString('### GET /api/users', $markdown);
        $this->assertStringContainsString('users.index', $markdown);
    }

    public function test_build_get_endpoint_shows_no_parameters(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [
                new EndpointDefinition('GET', 'api/users', null, null, [], []),
            ],
            authentication: 'none',
        );

        $markdown = $this->builder->build($contract);

        $this->assertStringContainsString('**Parameters:** None', $markdown);
    }

    public function test_build_endpoint_with_path_parameters(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [
                new EndpointDefinition('GET', 'api/users/{user}', 'users.show', null, [], ['user']),
            ],
            authentication: 'none',
        );

        $markdown = $this->builder->build($contract);

        $this->assertStringContainsString('| Parameter | Type | Description |', $markdown);
        $this->assertStringContainsString('| `user` | string | URL path parameter |', $markdown);
    }

    public function test_build_with_request_body_table(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [
                new EndpointDefinition(
                    method: 'POST',
                    uri: 'api/users',
                    name: null,
                    controller: null,
                    middleware: [],
                    parameters: [],
                    request: new RequestDefinition(
                        className: 'App\Http\Requests\StoreUserRequest',
                        fields: [
                            new ValidationField('name', 'string', true, ['required', 'string', 'max:255']),
                            new ValidationField('email', 'email', true, ['required', 'email']),
                            new ValidationField('age', 'integer', false, ['nullable', 'integer']),
                        ],
                        authorizeMethod: true,
                        rawRules: [],
                    ),
                ),
            ],
            authentication: 'none',
        );

        $markdown = $this->builder->build($contract);

        $this->assertStringContainsString('**Request Body:**', $markdown);
        $this->assertStringContainsString('| Field | Type | Required | Description |', $markdown);
        $this->assertStringContainsString('| `name` | string | Yes | required, string, max:255 |', $markdown);
        $this->assertStringContainsString('| `email` | email | Yes | required, email |', $markdown);
        $this->assertStringContainsString('| `age` | integer | No | nullable, integer |', $markdown);
    }

    public function test_build_with_example_request(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [
                new EndpointDefinition(
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
                            new ValidationField('email', 'email', true, ['required']),
                        ],
                        authorizeMethod: true,
                        rawRules: [],
                    ),
                ),
            ],
            authentication: 'none',
        );

        $markdown = $this->builder->build($contract);

        $this->assertStringContainsString('**Example Request:**', $markdown);
        $this->assertStringContainsString('```json', $markdown);
        $this->assertStringContainsString('"name": "string"', $markdown);
        $this->assertStringContainsString('"email": "user@example.com"', $markdown);
    }

    public function test_build_with_response_fields(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [
                new EndpointDefinition(
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
                            new ResponseField('email', 'string', true, '$this->email'),
                        ],
                        relationships: [],
                        collection: true,
                    ),
                ),
            ],
            authentication: 'none',
        );

        $markdown = $this->builder->build($contract);

        $this->assertStringContainsString('**Response:**', $markdown);
        $this->assertStringContainsString('| `id` | integer |', $markdown);
        $this->assertStringContainsString('| `name` | string |', $markdown);
        $this->assertStringContainsString('| `email` | string (nullable) |', $markdown);
    }

    public function test_build_with_example_response(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [
                new EndpointDefinition(
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
                        ],
                        relationships: [],
                        collection: false,
                    ),
                ),
            ],
            authentication: 'none',
        );

        $markdown = $this->builder->build($contract);

        $this->assertStringContainsString('**Example Response:**', $markdown);
        $this->assertStringContainsString('"id": 0', $markdown);
        $this->assertStringContainsString('"name": "string"', $markdown);
    }

    public function test_build_collection_response_wraps_in_array(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [
                new EndpointDefinition(
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
                ),
            ],
            authentication: 'none',
        );

        $markdown = $this->builder->build($contract);

        $this->assertStringContainsString('"id": 0', $markdown);
    }

    public function test_build_groups_endpoints_by_resource(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [
                new EndpointDefinition('GET', 'api/users', null, null, [], []),
                new EndpointDefinition('POST', 'api/users', null, null, [], []),
                new EndpointDefinition('GET', 'api/posts', null, null, [], []),
            ],
            authentication: 'none',
        );

        $markdown = $this->builder->build($contract);

        $usersPos = strpos($markdown, '## Users');
        $postsPos = strpos($markdown, '## Posts');

        $this->assertNotFalse($usersPos);
        $this->assertNotFalse($postsPos);
        $this->assertLessThan($usersPos, $postsPos);
    }

    public function test_build_with_metadata(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [],
            authentication: 'none',
            metadata: ['environment' => 'staging'],
        );

        $markdown = $this->builder->build($contract);

        $this->assertStringContainsString('**Version:** 1.0', $markdown);
    }
}
