<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Analyzers;

use Yab\LaravelApiContract\Analyzers\ResourceAnalyzer;
use Yab\LaravelApiContract\Services\DTO\ControllerDefinition;
use Yab\LaravelApiContract\Support\ResourceParser;
use Yab\LaravelApiContract\Tests\Support\TestControllers\UserResourceController;
use Yab\LaravelApiContract\Tests\Support\TestResources\PostResource;
use Yab\LaravelApiContract\Tests\Support\TestResources\ProfileResource;
use Yab\LaravelApiContract\Tests\Support\TestResources\UserResource;
use Yab\LaravelApiContract\Tests\TestCase;

class ResourceAnalyzerTest extends TestCase
{
    private ResourceAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->analyzer = $this->app->make(\Yab\LaravelApiContract\Contracts\ResourceAnalyzerContract::class);
    }

    public function test_analyzes_resource_class(): void
    {
        $definition = $this->analyzer->analyzeResource(UserResource::class);

        $this->assertNotNull($definition);
        $this->assertSame(UserResource::class, $definition->resourceClass());
    }

    public function test_analyzes_resource_fields(): void
    {
        $definition = $this->analyzer->analyzeResource(UserResource::class);

        $this->assertNotNull($definition);
        $fields = $definition->fields();

        $this->assertCount(7, $fields);
        $this->assertSame('id', $fields[0]->name());
        $this->assertSame('name', $fields[1]->name());
        $this->assertSame('email', $fields[2]->name());
        $this->assertSame('full_name', $fields[3]->name());
        $this->assertSame('is_admin', $fields[4]->name());
        $this->assertSame('posts', $fields[5]->name());
        $this->assertSame('profile', $fields[6]->name());
    }

    public function test_analyzes_field_types(): void
    {
        $definition = $this->analyzer->analyzeResource(UserResource::class);

        $this->assertNotNull($definition);
        $fields = $definition->fields();

        // $this->id is mixed (no model analysis)
        $this->assertSame('mixed', $fields[0]->type());

        // String concatenation
        $this->assertSame('string', $fields[3]->type());

        // Relationship detection
        $this->assertTrue($fields[5]->isRelationship());
        $this->assertSame('PostResource', $fields[5]->relationClass());
        $this->assertTrue($fields[5]->collection());

        $this->assertTrue($fields[6]->isRelationship());
        $this->assertSame('ProfileResource', $fields[6]->relationClass());
        $this->assertFalse($fields[6]->collection());
    }

    public function test_analyzes_resource_relationships(): void
    {
        $definition = $this->analyzer->analyzeResource(UserResource::class);

        $this->assertNotNull($definition);
        $this->assertTrue($definition->hasRelationships());
        $relationships = $definition->relationships();

        $this->assertContains('PostResource', $relationships);
        $this->assertContains('ProfileResource', $relationships);
    }

    public function test_returns_null_for_nonexistent_class(): void
    {
        $this->assertNull($this->analyzer->analyzeResource('NonexistentResource'));
    }

    public function test_returns_null_for_non_json_resource(): void
    {
        $this->assertNull($this->analyzer->analyzeResource(\stdClass::class));
    }

    public function test_analyzes_from_controller_using_make(): void
    {
        $definition = new ControllerDefinition(
            className: UserResourceController::class,
            method: 'show',
            visibility: 'public',
            parameters: [
                ['name' => 'id', 'type' => 'int', 'class' => null],
            ],
            returnType: null,
            dependencies: [],
        );

        $resourceDef = $this->analyzer->analyze($definition);

        $this->assertNotNull($resourceDef);
        $this->assertSame(UserResource::class, $resourceDef->resourceClass());
        $this->assertCount(7, $resourceDef->fields());
    }

    public function test_analyzes_from_controller_using_collection(): void
    {
        $definition = new ControllerDefinition(
            className: UserResourceController::class,
            method: 'index',
            visibility: 'public',
            parameters: [],
            returnType: null,
            dependencies: [],
        );

        $resourceDef = $this->analyzer->analyze($definition);

        $this->assertNotNull($resourceDef);
        $this->assertSame(UserResource::class, $resourceDef->resourceClass());
    }

    public function test_analyzes_from_controller_using_new(): void
    {
        $definition = new ControllerDefinition(
            className: UserResourceController::class,
            method: 'store',
            visibility: 'public',
            parameters: [],
            returnType: null,
            dependencies: [],
        );

        $resourceDef = $this->analyzer->analyze($definition);

        $this->assertNotNull($resourceDef);
        $this->assertSame(UserResource::class, $resourceDef->resourceClass());
    }

    public function test_returns_null_when_controller_has_no_resource(): void
    {
        $definition = new ControllerDefinition(
            className: UserResourceController::class,
            method: 'noResource',
            visibility: 'public',
            parameters: [],
            returnType: 'array',
            dependencies: [],
        );

        $this->assertNull($this->analyzer->analyze($definition));
    }

    public function test_analyzes_post_resource(): void
    {
        $definition = $this->analyzer->analyzeResource(PostResource::class);

        $this->assertNotNull($definition);
        $this->assertSame(PostResource::class, $definition->resourceClass());
        $this->assertCount(4, $definition->fields());
        $this->assertSame('id', $definition->fields()[0]->name());
        $this->assertSame('title', $definition->fields()[1]->name());
        $this->assertSame('body', $definition->fields()[2]->name());
    }

    public function test_analyzes_profile_resource(): void
    {
        $definition = $this->analyzer->analyzeResource(ProfileResource::class);

        $this->assertNotNull($definition);
        $this->assertSame(ProfileResource::class, $definition->resourceClass());
        $this->assertCount(2, $definition->fields());
        $this->assertSame('bio', $definition->fields()[0]->name());
        $this->assertSame('avatar', $definition->fields()[1]->name());
    }

    public function test_analyzer_is_reusable(): void
    {
        $def1 = $this->analyzer->analyzeResource(UserResource::class);
        $def2 = $this->analyzer->analyzeResource(PostResource::class);

        $this->assertNotNull($def1);
        $this->assertNotNull($def2);
        $this->assertSame(UserResource::class, $def1->resourceClass());
        $this->assertSame(PostResource::class, $def2->resourceClass());
    }

    public function test_metadata_contains_file_and_parent(): void
    {
        $definition = $this->analyzer->analyzeResource(UserResource::class);

        $this->assertNotNull($definition);
        $this->assertArrayHasKey('file', $definition->metadata());
        $this->assertArrayHasKey('parent', $definition->metadata());
        $this->assertStringContainsString('UserResource.php', $definition->metadata()['file']);
    }
}
