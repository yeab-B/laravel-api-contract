<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Services\DTO;

use PHPUnit\Framework\TestCase;
use Yab\LaravelApiContract\Services\DTO\ResourceDefinition;
use Yab\LaravelApiContract\Services\DTO\ResponseField;

class ResourceDefinitionTest extends TestCase
{
    public function test_constructs_with_basic_arguments(): void
    {
        $field = new ResponseField(
            name: 'id',
            type: 'integer',
            nullable: false,
            source: '$this->id',
        );

        $definition = new ResourceDefinition(
            resourceClass: 'App\Http\Resources\UserResource',
            fields: [$field],
            relationships: [],
            collection: false,
        );

        $this->assertSame('App\Http\Resources\UserResource', $definition->resourceClass());
        $this->assertCount(1, $definition->fields());
        $this->assertSame([], $definition->relationships());
        $this->assertFalse($definition->collection());
        $this->assertFalse($definition->hasRelationships());
        $this->assertSame([], $definition->metadata());
    }

    public function test_constructs_with_relationships(): void
    {
        $field = new ResponseField(
            name: 'posts',
            type: 'App\Http\Resources\PostResource',
            nullable: true,
            source: '$this->posts',
            relationClass: 'App\Http\Resources\PostResource',
            collection: true,
        );

        $definition = new ResourceDefinition(
            resourceClass: 'App\Http\Resources\UserResource',
            fields: [$field],
            relationships: ['App\Http\Resources\PostResource'],
            collection: true,
            metadata: ['key' => 'value'],
        );

        $this->assertTrue($definition->collection());
        $this->assertTrue($definition->hasRelationships());
        $this->assertSame(['App\Http\Resources\PostResource'], $definition->relationships());
        $this->assertSame(['key' => 'value'], $definition->metadata());
    }

    public function test_to_array(): void
    {
        $field = new ResponseField(
            name: 'id',
            type: 'integer',
            nullable: false,
            source: '$this->id',
        );

        $definition = new ResourceDefinition(
            resourceClass: 'App\Http\Resources\UserResource',
            fields: [$field],
            relationships: [],
            collection: false,
        );

        $array = $definition->toArray();

        $this->assertSame('App\Http\Resources\UserResource', $array['resource_class']);
        $this->assertCount(1, $array['fields']);
        $this->assertSame('id', $array['fields'][0]['name']);
        $this->assertFalse($array['collection']);
    }

    public function test_from_array_round_trip(): void
    {
        $original = new ResourceDefinition(
            'App\Http\Resources\UserResource',
            [new ResponseField('id', 'integer', false, '$this->id')],
            ['App\Http\Resources\PostResource'],
            true,
            ['key' => 'value'],
        );

        $restored = ResourceDefinition::fromArray($original->toArray());

        $this->assertSame($original->resourceClass(), $restored->resourceClass());
        $this->assertSame($original->relationships(), $restored->relationships());
        $this->assertSame($original->collection(), $restored->collection());
        $this->assertSame($original->metadata(), $restored->metadata());
        $this->assertCount(count($original->fields()), $restored->fields());
    }
}
