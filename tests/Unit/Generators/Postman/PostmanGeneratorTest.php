<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Generators\Postman;

use PHPUnit\Framework\TestCase;
use Yab\LaravelApiContract\Generators\Postman\PostmanGenerator;
use Yab\LaravelApiContract\Services\Contract\ApiContract;
use Yab\LaravelApiContract\Services\Contract\EndpointDefinition;
use Yab\LaravelApiContract\Services\DTO\RequestDefinition;
use Yab\LaravelApiContract\Services\DTO\ValidationField;
use Yab\LaravelApiContract\Services\Postman\PostmanBuilder;

class PostmanGeneratorTest extends TestCase
{
    private PostmanGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new PostmanGenerator(
            builder: new PostmanBuilder(),
        );
    }

    public function test_generate_returns_valid_json(): void
    {
        $contract = new ApiContract(
            name: 'Test API',
            version: '1.0',
            endpoints: [],
            authentication: 'none',
        );

        $json = $this->generator->generate($contract);

        $this->assertJson($json);

        $decoded = json_decode($json, true);

        $this->assertSame('Test API', $decoded['info']['name']);
        $this->assertSame([], $decoded['item']);
    }

    public function test_generate_with_endpoints(): void
    {
        $contract = new ApiContract(
            name: 'My API',
            version: '1.0.0',
            endpoints: [
                new EndpointDefinition('GET', 'api/users', 'users.index', null, [], []),
                new EndpointDefinition('POST', 'api/users', 'users.store', null, [], []),
            ],
            authentication: 'bearer',
        );

        $json = $this->generator->generate($contract);

        $this->assertJson($json);

        $decoded = json_decode($json, true);

        $this->assertCount(1, $decoded['item']);
        $this->assertSame('Users', $decoded['item'][0]['name']);
        $this->assertCount(2, $decoded['item'][0]['item']);
        $this->assertArrayHasKey('auth', $decoded);
        $this->assertArrayHasKey('variable', $decoded);
    }

    public function test_generate_with_request_body(): void
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

        $json = $this->generator->generate($contract);

        $this->assertJson($json);

        $decoded = json_decode($json, true);

        $body = $decoded['item'][0]['item'][0]['request']['body'];
        $this->assertSame('raw', $body['mode']);

        $rawBody = json_decode($body['raw'], true);
        $this->assertArrayHasKey('name', $rawBody);
        $this->assertArrayHasKey('email', $rawBody);
    }

    public function test_generate_throws_on_encode_failure(): void
    {
        $contract = $this->createMock(\Yab\LaravelApiContract\Contracts\ApiContractContract::class);
        $contract->method('name')->willReturn("Test\x80API");
        $contract->method('version')->willReturn('1.0');
        $contract->method('endpoints')->willReturn([]);
        $contract->method('authentication')->willReturn('none');
        $contract->method('metadata')->willReturn([]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to encode Postman collection');

        $this->generator->generate($contract);
    }
}
