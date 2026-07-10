<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Yab\LaravelApiContract\Services\DTO\ValidationField;

class ValidationFieldTest extends TestCase
{
    public function test_can_create_field(): void
    {
        $field = new ValidationField(
            name: 'email',
            type: 'email',
            required: true,
            rules: ['required', 'email'],
        );

        $this->assertSame('email', $field->name());
        $this->assertSame('email', $field->type());
        $this->assertTrue($field->required());
        $this->assertSame(['required', 'email'], $field->rules());
    }

    public function test_to_array(): void
    {
        $field = new ValidationField('name', 'string', true, ['required', 'string', 'max:255']);

        $expected = [
            'name' => 'name',
            'type' => 'string',
            'required' => true,
            'rules' => ['required', 'string', 'max:255'],
        ];

        $this->assertSame($expected, $field->toArray());
    }

    public function test_from_array_round_trip(): void
    {
        $original = new ValidationField('email', 'email', true, ['required', 'email']);
        $restored = ValidationField::fromArray($original->toArray());

        $this->assertSame($original->name(), $restored->name());
        $this->assertSame($original->type(), $restored->type());
        $this->assertSame($original->required(), $restored->required());
        $this->assertSame($original->rules(), $restored->rules());
    }
}
