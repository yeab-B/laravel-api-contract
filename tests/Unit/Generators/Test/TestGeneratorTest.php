<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Generators\Test;

use PHPUnit\Framework\TestCase;
use Yab\LaravelApiContract\Generators\Test\TestGenerator;
use Yab\LaravelApiContract\Services\Contract\ApiContract;
use Yab\LaravelApiContract\Services\Contract\EndpointDefinition;
use Yab\LaravelApiContract\Services\DTO\RequestDefinition;
use Yab\LaravelApiContract\Services\DTO\ValidationField;
use Yab\LaravelApiContract\Services\Test\TestBuilder;

class TestGeneratorTest extends TestCase
{
    private TestGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new TestGenerator(
            builder: new TestBuilder(),
        );
    }

    public function test_generate_returns_empty_array_for_no_endpoints(): void
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

    public function test_generate_returns_file_per_resource(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [
                new EndpointDefinition('GET', 'api/users', 'users.index', null, [], []),
                new EndpointDefinition('POST', 'api/posts', 'posts.store', null, [], []),
            ],
            authentication: 'none',
        );

        $files = $this->generator->generate($contract);

        $this->assertCount(2, $files);
        $this->assertSame('UserTest.php', $files[0]['filename']);
        $this->assertSame('PostTest.php', $files[1]['filename']);
    }

    public function test_generate_creates_php_tag_and_namespace(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [
                new EndpointDefinition('GET', 'api/users', null, null, [], []),
            ],
            authentication: 'none',
        );

        $files = $this->generator->generate($contract);

        $content = $files[0]['content'];

        $this->assertStringStartsWith('<?php', $content);
        $this->assertStringContainsString('namespace Tests\Feature\API;', $content);
        $this->assertStringContainsString('use Tests\TestCase;', $content);
        $this->assertStringContainsString('class UserTest extends TestCase', $content);
    }

    public function test_generates_get_list_test(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [
                new EndpointDefinition('GET', 'api/users', null, null, [], []),
            ],
            authentication: 'none',
        );

        $files = $this->generator->generate($contract);
        $content = $files[0]['content'];

        $this->assertStringContainsString('test_can_list_users', $content);
        $this->assertStringContainsString("\$response = \$this->getJson('/api/users');", $content);
        $this->assertStringContainsString('$response->assertOk();', $content);
    }

    public function test_generates_get_show_test(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [
                new EndpointDefinition('GET', 'api/users/{user}', null, null, [], ['user']),
            ],
            authentication: 'none',
        );

        $files = $this->generator->generate($contract);
        $content = $files[0]['content'];

        $this->assertStringContainsString('test_can_show_user', $content);
        $this->assertStringContainsString("\$response = \$this->getJson('/api/users/1');", $content);
    }

    public function test_generates_post_create_test(): void
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

        $files = $this->generator->generate($contract);
        $content = $files[0]['content'];

        $this->assertStringContainsString('test_can_create_user', $content);
        $this->assertStringContainsString("\$response = \$this->postJson('/api/users', \$payload);", $content);
        $this->assertStringContainsString("'name' => 'example'", $content);
        $this->assertStringContainsString("'email' => 'john@example.com'", $content);
        $this->assertStringContainsString('$response->assertCreated();', $content);
    }

    public function test_generates_put_update_test(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [
                new EndpointDefinition(
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
                        rawRules: [],
                    ),
                ),
            ],
            authentication: 'none',
        );

        $files = $this->generator->generate($contract);
        $content = $files[0]['content'];

        $this->assertStringContainsString('test_can_update_user', $content);
        $this->assertStringContainsString("\$response = \$this->putJson('/api/users/1', \$payload);", $content);
        $this->assertStringContainsString('$response->assertOk();', $content);
    }

    public function test_generates_patch_test(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [
                new EndpointDefinition(
                    method: 'PATCH',
                    uri: 'api/users/{user}',
                    name: null,
                    controller: null,
                    middleware: [],
                    parameters: ['user'],
                ),
            ],
            authentication: 'none',
        );

        $files = $this->generator->generate($contract);
        $content = $files[0]['content'];

        $this->assertStringContainsString('test_can_patch_user', $content);
        $this->assertStringContainsString("\$response = \$this->patchJson('/api/users/1', \$payload);", $content);
    }

    public function test_generates_delete_test(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [
                new EndpointDefinition('DELETE', 'api/users/{user}', null, null, [], ['user']),
            ],
            authentication: 'none',
        );

        $files = $this->generator->generate($contract);
        $content = $files[0]['content'];

        $this->assertStringContainsString('test_can_delete_user', $content);
        $this->assertStringContainsString("\$response = \$this->deleteJson('/api/users/1');", $content);
        $this->assertStringContainsString('$response->assertNoContent();', $content);
    }

    public function test_generates_validation_test(): void
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

        $files = $this->generator->generate($contract);
        $content = $files[0]['content'];

        $this->assertStringContainsString('test_name_is_required', $content);
        $this->assertStringContainsString('$response->assertStatus(422);', $content);
    }

    public function test_generates_authentication_test(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [
                new EndpointDefinition('GET', 'api/users', null, null, [], []),
            ],
            authentication: 'sanctum',
        );

        $files = $this->generator->generate($contract);
        $content = $files[0]['content'];

        $this->assertStringContainsString('test_requires_authentication', $content);
        $this->assertStringContainsString('$response->assertUnauthorized();', $content);
    }

    public function test_does_not_generate_auth_test_when_no_auth(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [
                new EndpointDefinition('GET', 'api/users', null, null, [], []),
            ],
            authentication: 'none',
        );

        $files = $this->generator->generate($contract);
        $content = $files[0]['content'];

        $this->assertStringNotContainsString('test_requires_authentication', $content);
    }

    public function test_does_not_generate_validation_for_get_endpoints(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [
                new EndpointDefinition('GET', 'api/users', null, null, [], []),
            ],
            authentication: 'none',
        );

        $files = $this->generator->generate($contract);
        $content = $files[0]['content'];

        $this->assertStringContainsString('test_can_list_users', $content);
        $this->assertStringNotContainsString('_is_required', $content);
    }
}
