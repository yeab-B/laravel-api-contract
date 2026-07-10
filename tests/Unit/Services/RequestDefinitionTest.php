<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Yab\LaravelApiContract\Services\DTO\RequestDefinition;
use Yab\LaravelApiContract\Services\DTO\ValidationField;

class RequestDefinitionTest extends TestCase
{
    public function test_can_create_definition(): void
    {
        $fields = [
            new ValidationField('name', 'string', true, ['required', 'string']),
        ];

        $definition = new RequestDefinition(
            className: 'App\Http\Requests\StoreUserRequest',
            fields: $fields,
            authorizeMethod: true,
            rawRules: ['name' => 'required|string'],
        );

        $this->assertSame('App\Http\Requests\StoreUserRequest', $definition->className());
        $this->assertCount(1, $definition->fields());
        $this->assertTrue($definition->authorizeMethod());
        $this->assertSame(['name' => 'required|string'], $definition->rawRules());
    }

    public function test_to_array(): void
    {
        $fields = [
            new ValidationField('name', 'string', true, ['required', 'string']),
        ];

        $definition = new RequestDefinition('R', $fields, true, []);

        $array = $definition->toArray();

        $this->assertSame('R', $array['class_name']);
        $this->assertCount(1, $array['fields']);
        $this->assertTrue($array['authorize']);
    }

    public function test_from_array_round_trip(): void
    {
        $original = new RequestDefinition(
            'App\Http\Requests\StoreUserRequest',
            [new ValidationField('email', 'email', true, ['required', 'email'])],
            true,
            ['email' => 'required|email'],
        );

        $restored = RequestDefinition::fromArray($original->toArray());

        $this->assertSame($original->className(), $restored->className());
        $this->assertSame($original->authorizeMethod(), $restored->authorizeMethod());
        $this->assertCount(count($original->fields()), $restored->fields());
    }
}
