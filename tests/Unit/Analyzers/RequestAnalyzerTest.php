<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Analyzers;

use Illuminate\Http\Request;
use Yab\LaravelApiContract\Analyzers\RequestAnalyzer;
use Yab\LaravelApiContract\Services\DTO\ControllerDefinition;
use Yab\LaravelApiContract\Support\ValidationRuleParser;
use Yab\LaravelApiContract\Tests\Support\TestControllers\StoreUserRequest;
use Yab\LaravelApiContract\Tests\TestCase;

class RequestAnalyzerTest extends TestCase
{
    private RequestAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->analyzer = new RequestAnalyzer(new ValidationRuleParser());
    }

    public function test_analyzes_form_request(): void
    {
        $definition = $this->makeControllerDefinition(StoreUserRequest::class);
        $result = $this->analyzer->analyze($definition);

        $this->assertNotNull($result);
        $this->assertSame(StoreUserRequest::class, $result->className());
        $this->assertTrue($result->authorizeMethod());
    }

    public function test_extracts_request_fields(): void
    {
        $definition = $this->makeControllerDefinition(StoreUserRequest::class);
        $result = $this->analyzer->analyze($definition);

        $this->assertNotNull($result);
        $this->assertCount(3, $result->fields());

        $nameField = $result->fields()[0];
        $this->assertSame('name', $nameField->name());
        $this->assertSame('string', $nameField->type());
        $this->assertTrue($nameField->required());

        $emailField = $result->fields()[1];
        $this->assertSame('email', $emailField->name());
        $this->assertSame('email', $emailField->type());
        $this->assertTrue($emailField->required());

        $ageField = $result->fields()[2];
        $this->assertSame('age', $ageField->name());
        $this->assertSame('integer', $ageField->type());
        $this->assertFalse($ageField->required());
    }

    public function test_returns_null_when_no_form_request(): void
    {
        $definition = $this->makeControllerDefinition(Request::class);
        $result = $this->analyzer->analyze($definition);

        $this->assertNull($result);
    }

    public function test_returns_null_when_no_typed_parameters(): void
    {
        $definition = new ControllerDefinition(
            className: 'App\Http\Controller',
            method: 'index',
            visibility: 'public',
            parameters: [],
            returnType: null,
            dependencies: [],
        );

        $this->assertNull($this->analyzer->analyze($definition));
    }

    public function test_analyzer_is_reusable(): void
    {
        $def1 = $this->makeControllerDefinition(StoreUserRequest::class);
        $def2 = $this->makeControllerDefinition(StoreUserRequest::class);

        $this->assertNotNull($this->analyzer->analyze($def1));
        $this->assertNotNull($this->analyzer->analyze($def2));
    }

    private function makeControllerDefinition(string $requestClass): ControllerDefinition
    {
        return new ControllerDefinition(
            className: 'App\Http\Controllers\UserController',
            method: 'store',
            visibility: 'public',
            parameters: [
                ['name' => 'request', 'type' => $requestClass, 'class' => $requestClass],
            ],
            returnType: 'Illuminate\Http\JsonResponse',
            dependencies: [$requestClass],
        );
    }
}
