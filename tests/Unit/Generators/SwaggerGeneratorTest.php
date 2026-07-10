<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Generators;

use PHPUnit\Framework\TestCase;
use Yab\LaravelApiContract\Contracts\ApiContractContract;
use Yab\LaravelApiContract\Generators\Swagger\SwaggerGenerator;
use Yab\LaravelApiContract\Generators\Swagger\SchemaGenerator;
use Yab\LaravelApiContract\Services\OpenApi\OpenApiBuilder;

class SwaggerGeneratorTest extends TestCase
{
    private SwaggerGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new SwaggerGenerator(
            builder: new OpenApiBuilder(
                schemaGenerator: new SchemaGenerator(),
            ),
        );
    }

    public function test_generate_returns_valid_json(): void
    {
        $contract = $this->createMock(ApiContractContract::class);
        $contract->method('name')->willReturn('Test API');
        $contract->method('version')->willReturn('1.0');
        $contract->method('endpoints')->willReturn([]);
        $contract->method('authentication')->willReturn('none');
        $contract->method('metadata')->willReturn([]);

        $json = $this->generator->generate($contract);

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertSame('3.0.0', $decoded['openapi']);
        $this->assertSame('Test API', $decoded['info']['title']);
        $this->assertSame('1.0', $decoded['info']['version']);
    }

    public function test_generate_includes_components_when_authentication_set(): void
    {
        $contract = $this->createMock(ApiContractContract::class);
        $contract->method('name')->willReturn('Auth API');
        $contract->method('version')->willReturn('1.0');
        $contract->method('endpoints')->willReturn([]);
        $contract->method('authentication')->willReturn('sanctum');
        $contract->method('metadata')->willReturn([]);

        $json = $this->generator->generate($contract);

        $decoded = json_decode($json, true);
        $this->assertArrayHasKey('components', $decoded);
        $this->assertArrayHasKey('securitySchemes', $decoded['components']);
    }

    public function test_generated_json_has_pretty_formatting(): void
    {
        $contract = $this->createMock(ApiContractContract::class);
        $contract->method('name')->willReturn('Test API');
        $contract->method('version')->willReturn('1.0');
        $contract->method('endpoints')->willReturn([]);
        $contract->method('authentication')->willReturn('none');
        $contract->method('metadata')->willReturn([]);

        $json = $this->generator->generate($contract);

        $this->assertStringContainsString("\n", $json);
    }
}
