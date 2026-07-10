<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Generators\Markdown;

use PHPUnit\Framework\TestCase;
use Yab\LaravelApiContract\Generators\Markdown\MarkdownGenerator;
use Yab\LaravelApiContract\Services\Contract\ApiContract;
use Yab\LaravelApiContract\Services\Contract\EndpointDefinition;
use Yab\LaravelApiContract\Services\DTO\RequestDefinition;
use Yab\LaravelApiContract\Services\DTO\ValidationField;
use Yab\LaravelApiContract\Services\Markdown\MarkdownBuilder;

class MarkdownGeneratorTest extends TestCase
{
    private MarkdownGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new MarkdownGenerator(
            builder: new MarkdownBuilder(),
        );
    }

    public function test_generate_returns_markdown(): void
    {
        $contract = new ApiContract(
            name: 'My API',
            version: '2.0',
            endpoints: [],
            authentication: 'none',
        );

        $markdown = $this->generator->generate($contract);

        $this->assertStringStartsWith('# My API API', $markdown);
        $this->assertStringContainsString('**Version:** 2.0', $markdown);
    }

    public function test_generate_with_endpoints(): void
    {
        $contract = new ApiContract(
            name: 'Test',
            version: '1.0',
            endpoints: [
                new EndpointDefinition('GET', 'api/users', 'users.index', null, [], []),
                new EndpointDefinition('POST', 'api/users', 'users.store', null, [], []),
            ],
            authentication: 'bearer',
        );

        $markdown = $this->generator->generate($contract);

        $this->assertStringContainsString('## Authentication', $markdown);
        $this->assertStringContainsString('## API Endpoints', $markdown);
        $this->assertStringContainsString('## Users', $markdown);
        $this->assertStringContainsString('### GET /api/users', $markdown);
        $this->assertStringContainsString('### POST /api/users', $markdown);
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
                        ],
                        authorizeMethod: true,
                        rawRules: [],
                    ),
                ),
            ],
            authentication: 'none',
        );

        $markdown = $this->generator->generate($contract);

        $this->assertStringContainsString('**Request Body:**', $markdown);
        $this->assertStringContainsString('**Example Request:**', $markdown);
    }
}
