<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Generators;

use PHPUnit\Framework\TestCase;
use Yab\LaravelApiContract\Generators\Swagger\SchemaGenerator;
use Yab\LaravelApiContract\Services\DTO\RequestDefinition;
use Yab\LaravelApiContract\Services\DTO\ResourceDefinition;
use Yab\LaravelApiContract\Services\DTO\ResponseField;
use Yab\LaravelApiContract\Services\DTO\ValidationField;

class SchemaGeneratorTest extends TestCase
{
    private SchemaGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new SchemaGenerator();
    }

    public function test_derive_schema_name_strips_resource_suffix(): void
    {
        $this->assertSame('User', $this->generator->deriveSchemaName('App\Http\Resources\UserResource'));
        $this->assertSame('Post', $this->generator->deriveSchemaName('App\Http\Resources\PostResource'));
    }

    public function test_derive_schema_name_handles_non_resource(): void
    {
        $this->assertSame('User', $this->generator->deriveSchemaName('App\Models\User'));
    }

    public function test_derive_request_schema_name_strips_request_suffix(): void
    {
        $this->assertSame('StoreUserRequest', $this->generator->deriveRequestSchemaName('App\Http\Requests\StoreUserRequest'));
    }

    public function test_field_to_property_maps_scalar_types(): void
    {
        $field = new ResponseField('id', 'integer', false, '$this->id');
        $property = $this->generator->fieldToProperty($field);

        $this->assertSame(['type' => 'integer'], $property);
    }

    public function test_field_to_property_maps_float(): void
    {
        $field = new ResponseField('price', 'float', false, '$this->price');
        $property = $this->generator->fieldToProperty($field);

        $this->assertSame(['type' => 'number', 'format' => 'float'], $property);
    }

    public function test_field_to_property_maps_boolean(): void
    {
        $field = new ResponseField('active', 'boolean', false, '$this->active');
        $property = $this->generator->fieldToProperty($field);

        $this->assertSame(['type' => 'boolean'], $property);
    }

    public function test_field_to_property_maps_string_format_types(): void
    {
        $this->assertSame(
            ['type' => 'string', 'format' => 'email'],
            $this->generator->fieldToProperty(new ResponseField('email', 'email', false, '$this->email')),
        );

        $this->assertSame(
            ['type' => 'string', 'format' => 'uri'],
            $this->generator->fieldToProperty(new ResponseField('site', 'url', false, '$this->site')),
        );

        $this->assertSame(
            ['type' => 'string', 'format' => 'date'],
            $this->generator->fieldToProperty(new ResponseField('birthday', 'date', false, '$this->birthday')),
        );
    }

    public function test_field_to_property_maps_file_and_image(): void
    {
        $this->assertSame(
            ['type' => 'string', 'format' => 'binary'],
            $this->generator->fieldToProperty(new ResponseField('avatar', 'file', false, '$this->avatar')),
        );

        $this->assertSame(
            ['type' => 'string', 'format' => 'binary'],
            $this->generator->fieldToProperty(new ResponseField('photo', 'image', false, '$this->photo')),
        );
    }

    public function test_field_to_property_maps_array_to_array_type(): void
    {
        $this->assertSame(
            ['type' => 'array'],
            $this->generator->fieldToProperty(new ResponseField('tags', 'array', false, '$this->tags')),
        );
    }

    public function test_field_to_property_maps_relationship(): void
    {
        $field = new ResponseField('user', 'object', false, null, 'App\Http\Resources\UserResource', false);
        $property = $this->generator->fieldToProperty($field);

        $this->assertSame(['$ref' => '#/components/schemas/User'], $property);
    }

    public function test_field_to_property_maps_collection_relationship(): void
    {
        $field = new ResponseField('posts', 'object', false, null, 'App\Http\Resources\PostResource', true);
        $property = $this->generator->fieldToProperty($field);

        $this->assertSame([
            'type' => 'array',
            'items' => ['$ref' => '#/components/schemas/Post'],
        ], $property);
    }

    public function test_resource_to_schema_returns_correct_structure(): void
    {
        $resource = new ResourceDefinition(
            resourceClass: 'App\Http\Resources\UserResource',
            fields: [
                new ResponseField('id', 'integer', false, '$this->id'),
                new ResponseField('name', 'string', false, '$this->name'),
                new ResponseField('email', 'email', false, '$this->email'),
            ],
            relationships: [],
            collection: false,
        );

        $schemas = $this->generator->resourceToSchema($resource);

        $this->assertArrayHasKey('User', $schemas);
        $this->assertSame('object', $schemas['User']['type']);
        $this->assertArrayHasKey('id', $schemas['User']['properties']);
        $this->assertArrayHasKey('name', $schemas['User']['properties']);
        $this->assertArrayHasKey('email', $schemas['User']['properties']);
        $this->assertArrayNotHasKey('required', $schemas['User']);
    }

    public function test_resource_to_schema_with_nullable_field(): void
    {
        $resource = new ResourceDefinition(
            resourceClass: 'App\Http\Resources\UserResource',
            fields: [
                new ResponseField('email', 'email', true, '$this->email'),
            ],
            relationships: [],
            collection: false,
        );

        $schemas = $this->generator->resourceToSchema($resource);

        $this->assertTrue($schemas['User']['properties']['email']['nullable']);
    }

    public function test_validation_field_to_property(): void
    {
        $field = new ValidationField('name', 'string', true, ['required', 'string', 'max:255']);
        $property = $this->generator->validationFieldToProperty($field);

        $this->assertSame(['type' => 'string'], $property);
    }

    public function test_validation_field_to_property_with_rule_format(): void
    {
        $field = new ValidationField('email', 'email', true, ['required', 'email']);
        $property = $this->generator->validationFieldToProperty($field);

        $this->assertSame(['type' => 'string', 'format' => 'email'], $property);
    }

    public function test_validation_field_to_property_with_url_rule(): void
    {
        $field = new ValidationField('website', 'url', false, ['nullable', 'url']);
        $property = $this->generator->validationFieldToProperty($field);

        $this->assertSame(['type' => 'string', 'format' => 'uri'], $property);
    }

    public function test_request_to_schema(): void
    {
        $request = new RequestDefinition(
            className: 'App\Http\Requests\StoreUserRequest',
            fields: [
                new ValidationField('name', 'string', true, ['required', 'string', 'max:255']),
                new ValidationField('email', 'email', true, ['required', 'email']),
                new ValidationField('bio', 'string', false, ['nullable', 'string']),
            ],
            authorizeMethod: true,
            rawRules: [
                'name' => 'required|string|max:255',
                'email' => 'required|email',
                'bio' => 'nullable|string',
            ],
        );

        $schema = $this->generator->requestToSchema($request);

        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('name', $schema['properties']);
        $this->assertArrayHasKey('email', $schema['properties']);
        $this->assertArrayHasKey('bio', $schema['properties']);
        $this->assertContains('name', $schema['required']);
        $this->assertContains('email', $schema['required']);
        $this->assertNotContains('bio', $schema['required']);
    }

    public function test_field_to_property_maps_unknown_type_to_string(): void
    {
        $this->assertSame(
            ['type' => 'string'],
            $this->generator->fieldToProperty(new ResponseField('custom', 'custom_type', false, '$this->custom')),
        );
    }

    public function test_field_to_property_maps_json_to_object(): void
    {
        $this->assertSame(
            ['type' => 'object'],
            $this->generator->fieldToProperty(new ResponseField('metadata', 'json', false, '$this->metadata')),
        );
    }

    public function test_field_to_property_maps_null_to_nullable_string(): void
    {
        $this->assertSame(
            ['type' => 'string', 'nullable' => true],
            $this->generator->fieldToProperty(new ResponseField('deleted_at', 'null', false, '$this->deleted_at')),
        );
    }

    public function test_derive_schema_name_for_resource_only_class(): void
    {
        $this->assertSame('User', $this->generator->deriveSchemaName('UserResource'));
    }

    public function test_derive_request_schema_name_for_non_request_class(): void
    {
        $this->assertSame('MyClassRequest', $this->generator->deriveRequestSchemaName('MyClass'));
    }
}
