<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Services;

use PHPUnit\Framework\MockObject\MockObject;
use Yab\LaravelApiContract\Config\Configuration;
use Yab\LaravelApiContract\Contracts\ControllerAnalyzerContract;
use Yab\LaravelApiContract\Contracts\RequestAnalyzerContract;
use Yab\LaravelApiContract\Contracts\ResourceAnalyzerContract;
use Yab\LaravelApiContract\Contracts\RouteAnalyzerContract;
use Yab\LaravelApiContract\Services\ContractBuilder;
use Yab\LaravelApiContract\Services\DTO\ControllerDefinition;
use Yab\LaravelApiContract\Services\DTO\RequestDefinition;
use Yab\LaravelApiContract\Services\DTO\ResourceDefinition;
use Yab\LaravelApiContract\Services\DTO\RouteCollection;
use Yab\LaravelApiContract\Services\DTO\RouteDefinition;
use Yab\LaravelApiContract\Tests\TestCase;

class ContractBuilderTest extends TestCase
{
    private MockObject&RouteAnalyzerContract $routeAnalyzer;
    private MockObject&ControllerAnalyzerContract $controllerAnalyzer;
    private MockObject&RequestAnalyzerContract $requestAnalyzer;
    private MockObject&ResourceAnalyzerContract $resourceAnalyzer;
    private ContractBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->routeAnalyzer = $this->createMock(RouteAnalyzerContract::class);
        $this->controllerAnalyzer = $this->createMock(ControllerAnalyzerContract::class);
        $this->requestAnalyzer = $this->createMock(RequestAnalyzerContract::class);
        $this->resourceAnalyzer = $this->createMock(ResourceAnalyzerContract::class);

        $config = $this->app->make(Configuration::class);

        $this->builder = new ContractBuilder(
            routeAnalyzer: $this->routeAnalyzer,
            controllerAnalyzer: $this->controllerAnalyzer,
            requestAnalyzer: $this->requestAnalyzer,
            resourceAnalyzer: $this->resourceAnalyzer,
            configuration: $config,
        );
    }

    public function test_builds_contract_with_endpoints(): void
    {
        $route = new RouteDefinition(
            method: 'GET',
            uri: 'api/users',
            name: 'users.index',
            controller: 'App\Http\Controllers\UserController@index',
            middleware: ['api'],
            parameters: [],
        );

        $this->routeAnalyzer
            ->expects($this->once())
            ->method('discover')
            ->willReturn(new RouteCollection($route));

        $controllerDef = new ControllerDefinition(
            className: 'App\Http\Controllers\UserController',
            method: 'index',
            visibility: 'public',
            parameters: [],
            returnType: null,
            dependencies: [],
        );

        $this->controllerAnalyzer
            ->expects($this->once())
            ->method('analyze')
            ->with($route)
            ->willReturn($controllerDef);

        $this->requestAnalyzer
            ->expects($this->once())
            ->method('analyze')
            ->with($controllerDef)
            ->willReturn(null);

        $this->resourceAnalyzer
            ->expects($this->once())
            ->method('analyze')
            ->with($controllerDef)
            ->willReturn(null);

        $contract = $this->builder->build();

        $this->assertSame('Laravel API', $contract->name());
        $this->assertSame('1.0', $contract->version());
        $this->assertCount(1, $contract->endpoints());

        $endpoint = $contract->endpoints()[0];
        $this->assertSame('GET', $endpoint->method());
        $this->assertSame('api/users', $endpoint->uri());
        $this->assertSame('users.index', $endpoint->name());
        $this->assertSame('App\Http\Controllers\UserController@index', $endpoint->controller());
        $this->assertFalse($endpoint->hasRequest());
        $this->assertFalse($endpoint->hasResponse());
    }

    public function test_builds_contract_with_request_and_response(): void
    {
        $route = new RouteDefinition(
            method: 'POST',
            uri: 'api/users',
            name: null,
            controller: 'App\Http\Controllers\UserController@store',
            middleware: ['api', 'auth:sanctum'],
            parameters: [],
        );

        $this->routeAnalyzer
            ->expects($this->once())
            ->method('discover')
            ->willReturn(new RouteCollection($route));

        $controllerDef = new ControllerDefinition(
            className: 'App\Http\Controllers\UserController',
            method: 'store',
            visibility: 'public',
            parameters: [],
            returnType: null,
            dependencies: [],
        );

        $this->controllerAnalyzer
            ->expects($this->once())
            ->method('analyze')
            ->with($route)
            ->willReturn($controllerDef);

        $requestDef = new RequestDefinition(
            className: 'App\Http\Requests\StoreUserRequest',
            fields: [],
            authorizeMethod: true,
            rawRules: [],
        );

        $this->requestAnalyzer
            ->expects($this->once())
            ->method('analyze')
            ->with($controllerDef)
            ->willReturn($requestDef);

        $resourceDef = new ResourceDefinition(
            resourceClass: 'App\Http\Resources\UserResource',
            fields: [],
            relationships: [],
            collection: false,
        );

        $this->resourceAnalyzer
            ->expects($this->once())
            ->method('analyze')
            ->with($controllerDef)
            ->willReturn($resourceDef);

        $contract = $this->builder->build();

        $this->assertCount(1, $contract->endpoints());

        $endpoint = $contract->endpoints()[0];
        $this->assertTrue($endpoint->hasRequest());
        $this->assertTrue($endpoint->hasResponse());
        $this->assertSame('App\Http\Requests\StoreUserRequest', $endpoint->request()->className());
        $this->assertSame('App\Http\Resources\UserResource', $endpoint->response()->resourceClass());
    }

    public function test_builds_empty_contract_when_no_routes(): void
    {
        $this->routeAnalyzer
            ->expects($this->once())
            ->method('discover')
            ->willReturn(new RouteCollection());

        $this->controllerAnalyzer
            ->expects($this->never())
            ->method('analyze');

        $contract = $this->builder->build();

        $this->assertCount(0, $contract->endpoints());
    }

    public function test_builds_contract_with_null_controller(): void
    {
        $route = new RouteDefinition(
            method: 'GET',
            uri: 'api/health',
            name: null,
            controller: null,
            middleware: [],
            parameters: [],
        );

        $this->routeAnalyzer
            ->expects($this->once())
            ->method('discover')
            ->willReturn(new RouteCollection($route));

        $this->controllerAnalyzer
            ->expects($this->once())
            ->method('analyze')
            ->with($route)
            ->willReturn(null);

        $this->requestAnalyzer
            ->expects($this->never())
            ->method('analyze');

        $this->resourceAnalyzer
            ->expects($this->never())
            ->method('analyze');

        $contract = $this->builder->build();

        $this->assertCount(1, $contract->endpoints());

        $endpoint = $contract->endpoints()[0];
        $this->assertSame('GET', $endpoint->method());
        $this->assertNull($endpoint->controller());
        $this->assertFalse($endpoint->hasRequest());
        $this->assertFalse($endpoint->hasResponse());
    }

    public function test_builds_contract_with_multiple_routes(): void
    {
        $route1 = new RouteDefinition(
            method: 'GET',
            uri: 'api/users',
            name: null,
            controller: 'UserController@index',
            middleware: ['api'],
            parameters: [],
        );

        $route2 = new RouteDefinition(
            method: 'POST',
            uri: 'api/users',
            name: null,
            controller: 'UserController@store',
            middleware: ['api'],
            parameters: [],
        );

        $this->routeAnalyzer
            ->expects($this->once())
            ->method('discover')
            ->willReturn(new RouteCollection($route1, $route2));

        $this->controllerAnalyzer
            ->expects($this->exactly(2))
            ->method('analyze')
            ->willReturnCallback(fn ($route) => match ($route->uri()) {
                'api/users' => new ControllerDefinition(
                    className: 'UserController',
                    method: $route->uri() === 'api/users' && $route->method() === 'GET' ? 'index' : 'store',
                    visibility: 'public',
                    parameters: [],
                    returnType: null,
                    dependencies: [],
                ),
                default => null,
            });

        $this->requestAnalyzer
            ->expects($this->exactly(2))
            ->method('analyze')
            ->willReturn(null);

        $this->resourceAnalyzer
            ->expects($this->exactly(2))
            ->method('analyze')
            ->willReturn(null);

        $contract = $this->builder->build();

        $this->assertCount(2, $contract->endpoints());
    }

    public function test_contract_contains_metadata(): void
    {
        $this->routeAnalyzer
            ->expects($this->once())
            ->method('discover')
            ->willReturn(new RouteCollection());

        $contract = $this->builder->build();

        $this->assertArrayHasKey('generated_at', $contract->metadata());
        $this->assertArrayHasKey('endpoint_count', $contract->metadata());
        $this->assertSame(0, $contract->metadata()['endpoint_count']);
    }
}
