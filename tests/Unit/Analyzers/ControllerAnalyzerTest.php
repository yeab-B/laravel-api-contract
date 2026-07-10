<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Analyzers;

use Illuminate\Http\Request;
use Yab\LaravelApiContract\Analyzers\ControllerAnalyzer;
use Yab\LaravelApiContract\Services\DTO\RouteDefinition;
use Yab\LaravelApiContract\Tests\Support\TestControllers\InvokableController;
use Yab\LaravelApiContract\Tests\Support\TestControllers\UserController;
use Yab\LaravelApiContract\Tests\TestCase;

class ControllerAnalyzerTest extends TestCase
{
    private ControllerAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->analyzer = $this->app->make(\Yab\LaravelApiContract\Contracts\ControllerAnalyzerContract::class);
    }

    public function test_analyzes_controller_with_no_parameters(): void
    {
        $route = $this->makeRoute('GET', 'api/users', UserController::class . '@index');
        $definition = $this->analyzer->analyze($route);

        $this->assertNotNull($definition);
        $this->assertSame(UserController::class, $definition->className());
        $this->assertSame('index', $definition->method());
        $this->assertSame('public', $definition->visibility());
        $this->assertSame([], $definition->parameters());
        $this->assertSame('Illuminate\\Http\\JsonResponse', $definition->returnType());
        $this->assertSame([], $definition->dependencies());
    }

    public function test_analyzes_controller_with_typed_parameters(): void
    {
        $route = $this->makeRoute('POST', 'api/users', UserController::class . '@store');
        $definition = $this->analyzer->analyze($route);

        $this->assertNotNull($definition);
        $this->assertSame('store', $definition->method());

        $params = $definition->parameters();
        $this->assertCount(1, $params);
        $this->assertSame('request', $params[0]['name']);
        $this->assertSame(Request::class, $params[0]['type']);
        $this->assertSame(Request::class, $params[0]['class']);

        $this->assertSame([Request::class], $definition->dependencies());
    }

    public function test_analyzes_controller_with_scalar_parameters(): void
    {
        $route = $this->makeRoute('GET', 'api/users/{id}', UserController::class . '@show');
        $definition = $this->analyzer->analyze($route);

        $this->assertNotNull($definition);
        $this->assertSame('show', $definition->method());

        $params = $definition->parameters();
        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]['name']);
        $this->assertSame('int', $params[0]['type']);
        $this->assertNull($params[0]['class']);

        $this->assertSame([], $definition->dependencies());
    }

    public function test_analyzes_controller_with_multiple_parameters(): void
    {
        $route = $this->makeRoute('PUT', 'api/users/{id}', UserController::class . '@update');
        $definition = $this->analyzer->analyze($route);

        $this->assertNotNull($definition);
        $this->assertSame('update', $definition->method());

        $params = $definition->parameters();
        $this->assertCount(2, $params);
        $this->assertSame('request', $params[0]['name']);
        $this->assertSame('id', $params[1]['name']);
    }

    public function test_detects_primitive_return_type(): void
    {
        $route = $this->makeRoute('DELETE', 'api/users/{id}', UserController::class . '@destroy');
        $definition = $this->analyzer->analyze($route);

        $this->assertNotNull($definition);
        $this->assertSame('bool', $definition->returnType());
    }

    public function test_detects_dependencies_from_multiple_route_definitions(): void
    {
        $route1 = $this->makeRoute('POST', 'api/users', UserController::class . '@store');
        $route2 = $this->makeRoute('PUT', 'api/users/{id}', UserController::class . '@update');

        $def1 = $this->analyzer->analyze($route1);
        $def2 = $this->analyzer->analyze($route2);

        $this->assertContains(Request::class, $def1->dependencies());
        $this->assertContains(Request::class, $def2->dependencies());
    }

    public function test_analyzes_invokable_controller(): void
    {
        $route = $this->makeRoute('GET', 'api/payments', InvokableController::class);
        $definition = $this->analyzer->analyze($route);

        $this->assertNotNull($definition);
        $this->assertSame(InvokableController::class, $definition->className());
        $this->assertSame('__invoke', $definition->method());
        $this->assertSame('Illuminate\\Http\\JsonResponse', $definition->returnType());
    }

    public function test_returns_null_for_nonexistent_controller(): void
    {
        $route = $this->makeRoute('GET', 'api/ghost', 'GhostController@index');
        $definition = $this->analyzer->analyze($route);

        $this->assertNull($definition);
    }

    public function test_returns_null_when_route_has_no_controller(): void
    {
        $route = new RouteDefinition(
            method: 'GET',
            uri: 'api/health',
            name: null,
            controller: null,
            middleware: [],
            parameters: [],
        );

        $definition = $this->analyzer->analyze($route);

        $this->assertNull($definition);
    }

    public function test_analyze_is_reusable(): void
    {
        $route1 = $this->makeRoute('GET', 'api/users', UserController::class . '@index');
        $route2 = $this->makeRoute('POST', 'api/users', UserController::class . '@store');

        $def1 = $this->analyzer->analyze($route1);
        $def2 = $this->analyzer->analyze($route2);

        $this->assertNotNull($def1);
        $this->assertNotNull($def2);
        $this->assertSame('index', $def1->method());
        $this->assertSame('store', $def2->method());
    }

    public function test_controller_action_method(): void
    {
        $route = $this->makeRoute('GET', 'api/users', UserController::class . '@index');
        $definition = $this->analyzer->analyze($route);

        $this->assertSame(UserController::class . '@index', $definition->controllerAction());
    }

    private function makeRoute(string $method, string $uri, string $controller): RouteDefinition
    {
        return new RouteDefinition(
            method: $method,
            uri: $uri,
            name: null,
            controller: $controller,
            middleware: ['api'],
            parameters: [],
        );
    }
}
