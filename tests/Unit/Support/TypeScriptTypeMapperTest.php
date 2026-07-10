<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use Yab\LaravelApiContract\Support\TypeScriptTypeMapper;

class TypeScriptTypeMapperTest extends TestCase
{
    private TypeScriptTypeMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mapper = new TypeScriptTypeMapper();
    }

    public function test_maps_integer_to_number(): void
    {
        $this->assertSame('number', $this->mapper->toTypeScript('integer'));
    }

    public function test_maps_float_to_number(): void
    {
        $this->assertSame('number', $this->mapper->toTypeScript('float'));
    }

    public function test_maps_string_to_string(): void
    {
        $this->assertSame('string', $this->mapper->toTypeScript('string'));
    }

    public function test_maps_boolean_to_boolean(): void
    {
        $this->assertSame('boolean', $this->mapper->toTypeScript('boolean'));
    }

    public function test_maps_array_to_any_array(): void
    {
        $this->assertSame('any[]', $this->mapper->toTypeScript('array'));
    }

    public function test_maps_object_to_record(): void
    {
        $this->assertSame('Record<string, any>', $this->mapper->toTypeScript('object'));
    }

    public function test_maps_datetime_to_string(): void
    {
        $this->assertSame('string', $this->mapper->toTypeScript('datetime'));
    }

    public function test_maps_email_to_string(): void
    {
        $this->assertSame('string', $this->mapper->toTypeScript('email'));
    }

    public function test_maps_url_to_string(): void
    {
        $this->assertSame('string', $this->mapper->toTypeScript('url'));
    }

    public function test_maps_date_to_string(): void
    {
        $this->assertSame('string', $this->mapper->toTypeScript('date'));
    }

    public function test_maps_file_to_string(): void
    {
        $this->assertSame('string', $this->mapper->toTypeScript('file'));
    }

    public function test_maps_image_to_string(): void
    {
        $this->assertSame('string', $this->mapper->toTypeScript('image'));
    }

    public function test_maps_ip_to_string(): void
    {
        $this->assertSame('string', $this->mapper->toTypeScript('ip'));
    }

    public function test_maps_json_to_record(): void
    {
        $this->assertSame('Record<string, any>', $this->mapper->toTypeScript('json'));
    }

    public function test_maps_mixed_to_any(): void
    {
        $this->assertSame('any', $this->mapper->toTypeScript('mixed'));
    }

    public function test_maps_null_to_null(): void
    {
        $this->assertSame('null', $this->mapper->toTypeScript('null'));
    }

    public function test_maps_unknown_to_string(): void
    {
        $this->assertSame('string', $this->mapper->toTypeScript('unknown_type'));
    }

    public function test_relation_to_interface_strips_resource_suffix(): void
    {
        $this->assertSame('User', $this->mapper->relationToInterface('App\Http\Resources\UserResource'));
        $this->assertSame('Post', $this->mapper->relationToInterface('App\Http\Resources\PostResource'));
    }

    public function test_request_to_interface_strips_request_suffix(): void
    {
        $this->assertSame('StoreUser', $this->mapper->requestToInterface('App\Http\Requests\StoreUserRequest'));
    }

    public function test_relation_to_interface_preserves_non_resource(): void
    {
        $this->assertSame('User', $this->mapper->relationToInterface('App\Models\User'));
    }
}
