<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Yab\LaravelApiContract\Services\DTO\ControllerDefinition;

class ControllerDefinitionTest extends TestCase
{
    public function test_can_create_definition(): void
    {
        $definition = new ControllerDefinition(
            className: 'App\Http\Controllers\UserController',
            method: 'index',
            visibility: 'public',
            parameters: [],
            returnType: 'Illuminate\Http\JsonResponse',
            dependencies: [],
        );

        $this->assertSame('App\Http\Controllers\UserController', $definition->className());
        $this->assertSame('index', $definition->method());
        $this->assertSame('public', $definition->visibility());
        $this->assertSame([], $definition->parameters());
        $this->assertSame('Illuminate\Http\JsonResponse', $definition->returnType());
        $this->assertSame([], $definition->dependencies());
    }

    public function test_has_dependencies(): void
    {
        $noDeps = new ControllerDefinition('C', 'm', 'public', [], null, []);
        $withDeps = new ControllerDefinition('C', 'm', 'public', [], null, ['Illuminate\Http\Request']);

        $this->assertFalse($noDeps->hasDependencies());
        $this->assertTrue($withDeps->hasDependencies());
    }

    public function test_controller_action(): void
    {
        $definition = new ControllerDefinition(
            className: 'App\Http\Controllers\UserController',
            method: 'store',
            visibility: 'public',
            parameters: [['name' => 'request', 'type' => 'Illuminate\Http\Request', 'class' => 'Illuminate\Http\Request']],
            returnType: null,
            dependencies: ['Illuminate\Http\Request'],
        );

        $this->assertSame('App\Http\Controllers\UserController@store', $definition->controllerAction());
    }

    public function test_to_array(): void
    {
        $definition = new ControllerDefinition(
            className: 'App\Http\Controllers\UserController',
            method: 'show',
            visibility: 'public',
            parameters: [['name' => 'id', 'type' => 'int', 'class' => null]],
            returnType: 'Illuminate\Http\JsonResponse',
            dependencies: [],
        );

        $expected = [
            'class_name' => 'App\Http\Controllers\UserController',
            'method' => 'show',
            'visibility' => 'public',
            'parameters' => [['name' => 'id', 'type' => 'int', 'class' => null]],
            'return_type' => 'Illuminate\Http\JsonResponse',
            'dependencies' => [],
        ];

        $this->assertSame($expected, $definition->toArray());
    }

    public function test_can_have_null_return_type(): void
    {
        $definition = new ControllerDefinition('C', 'm', 'public', [], null, []);

        $this->assertNull($definition->returnType());
    }
}
