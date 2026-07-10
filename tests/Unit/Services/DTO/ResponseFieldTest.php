<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Services\DTO;

use PHPUnit\Framework\TestCase;
use Yab\LaravelApiContract\Services\DTO\ResponseField;

class ResponseFieldTest extends TestCase
{
    public function test_constructs_with_minimal_arguments(): void
    {
        $field = new ResponseField(
            name: 'id',
            type: 'integer',
            nullable: false,
            source: '$this->id',
        );

        $this->assertSame('id', $field->name());
        $this->assertSame('integer', $field->type());
        $this->assertFalse($field->nullable());
        $this->assertSame('$this->id', $field->source());
        $this->assertNull($field->relationClass());
        $this->assertFalse($field->collection());
        $this->assertFalse($field->isRelationship());
    }

    public function test_constructs_with_relationship(): void
    {
        $field = new ResponseField(
            name: 'posts',
            type: 'App\Resources\PostResource',
            nullable: true,
            source: '$this->posts',
            relationClass: 'App\Resources\PostResource',
            collection: true,
        );

        $this->assertSame('posts', $field->name());
        $this->assertSame('App\Resources\PostResource', $field->type());
        $this->assertTrue($field->nullable());
        $this->assertTrue($field->isRelationship());
        $this->assertTrue($field->collection());
        $this->assertSame('App\Resources\PostResource', $field->relationClass());
    }

    public function test_to_array(): void
    {
        $field = new ResponseField(
            name: 'email',
            type: 'string',
            nullable: false,
            source: '$this->email',
        );

        $this->assertSame([
            'name' => 'email',
            'type' => 'string',
            'nullable' => false,
            'source' => '$this->email',
            'relation_class' => null,
            'collection' => false,
        ], $field->toArray());
    }

    public function test_from_array_round_trip(): void
    {
        $original = new ResponseField('id', 'integer', false, '$this->id', 'App\Models\User', true);
        $restored = ResponseField::fromArray($original->toArray());

        $this->assertSame($original->name(), $restored->name());
        $this->assertSame($original->type(), $restored->type());
        $this->assertSame($original->nullable(), $restored->nullable());
        $this->assertSame($original->source(), $restored->source());
        $this->assertSame($original->relationClass(), $restored->relationClass());
        $this->assertSame($original->collection(), $restored->collection());
    }
}
