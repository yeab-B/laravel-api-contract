<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Yab\LaravelApiContract\Services\DTO\RouteDefinition;

class RouteDefinitionTest extends TestCase
{
    public function test_can_create_route_definition(): void
    {
        $definition = new RouteDefinition(
            method: 'GET',
            uri: 'api/users/{id}',
            name: 'users.show',
            controller: 'UserController@show',
            middleware: ['auth:sanctum'],
            parameters: ['id'],
        );

        $this->assertSame('GET', $definition->method());
        $this->assertSame('api/users/{id}', $definition->uri());
        $this->assertSame('users.show', $definition->name());
        $this->assertSame('UserController@show', $definition->controller());
        $this->assertSame(['auth:sanctum'], $definition->middleware());
        $this->assertSame(['id'], $definition->parameters());
    }

    public function test_has_parameter(): void
    {
        $definition = new RouteDefinition(
            method: 'GET',
            uri: 'api/users/{id}/posts/{postId}',
            name: null,
            controller: null,
            middleware: [],
            parameters: ['id', 'postId'],
        );

        $this->assertTrue($definition->hasParameter('id'));
        $this->assertTrue($definition->hasParameter('postId'));
        $this->assertFalse($definition->hasParameter('slug'));
    }

    public function test_can_have_nullable_fields(): void
    {
        $definition = new RouteDefinition(
            method: 'GET',
            uri: 'api/health',
            name: null,
            controller: null,
            middleware: [],
            parameters: [],
        );

        $this->assertNull($definition->name());
        $this->assertNull($definition->controller());
        $this->assertSame([], $definition->middleware());
        $this->assertSame([], $definition->parameters());
    }

    public function test_to_array(): void
    {
        $definition = new RouteDefinition(
            method: 'POST',
            uri: 'api/users',
            name: 'users.store',
            controller: 'UserController@store',
            middleware: ['api'],
            parameters: [],
        );

        $expected = [
            'method' => 'POST',
            'uri' => 'api/users',
            'name' => 'users.store',
            'controller' => 'UserController@store',
            'middleware' => ['api'],
            'parameters' => [],
        ];

        $this->assertSame($expected, $definition->toArray());
    }
}
