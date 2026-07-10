<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Yab\LaravelApiContract\Services\DTO\RouteCollection;
use Yab\LaravelApiContract\Services\DTO\RouteDefinition;

class RouteCollectionTest extends TestCase
{
    private RouteCollection $collection;

    protected function setUp(): void
    {
        $this->collection = new RouteCollection(
            new RouteDefinition('GET', 'api/users', 'users.index', 'UserController@index', ['api'], []),
            new RouteDefinition('POST', 'api/users', 'users.store', 'UserController@store', ['api'], []),
            new RouteDefinition('GET', 'api/users/{id}', 'users.show', 'UserController@show', ['api'], ['id']),
            new RouteDefinition('PUT', 'api/users/{id}', 'users.update', 'UserController@update', ['api'], ['id']),
            new RouteDefinition('DELETE', 'api/users/{id}', 'users.destroy', 'UserController@destroy', ['api'], ['id']),
        );
    }

    public function test_all_returns_all_routes(): void
    {
        $this->assertCount(5, $this->collection->all());
    }

    public function test_find_by_method(): void
    {
        $getRoutes = $this->collection->findByMethod('GET');
        $this->assertCount(2, $getRoutes);
    }

    public function test_find_by_method_is_case_insensitive(): void
    {
        $this->assertCount(2, $this->collection->findByMethod('get'));
        $this->assertCount(1, $this->collection->findByMethod('post'));
    }

    public function test_find_by_name(): void
    {
        $route = $this->collection->findByName('users.index');
        $this->assertNotNull($route);
        $this->assertSame('users.index', $route->name());
    }

    public function test_find_by_name_returns_null_when_not_found(): void
    {
        $this->assertNull($this->collection->findByName('nonexistent'));
    }

    public function test_find_by_controller(): void
    {
        $routes = $this->collection->findByController('UserController@index');
        $this->assertCount(1, $routes);
        $this->assertSame('users.index', $routes->all()[0]->name());
    }

    public function test_filter(): void
    {
        $filtered = $this->collection->filter(
            static fn (RouteDefinition $r) => $r->parameters() !== [],
        );

        $this->assertCount(3, $filtered);
    }

    public function test_merge(): void
    {
        $other = new RouteCollection(
            new RouteDefinition('GET', 'api/posts', 'posts.index', 'PostController@index', ['api'], []),
        );

        $merged = $this->collection->merge($other);

        $this->assertCount(6, $merged);
    }

    public function test_is_empty(): void
    {
        $empty = new RouteCollection();
        $this->assertTrue($empty->isEmpty());
        $this->assertFalse($this->collection->isEmpty());
    }

    public function test_count(): void
    {
        $this->assertSame(5, $this->collection->count());
    }

    public function test_to_array(): void
    {
        $array = $this->collection->toArray();

        $this->assertCount(5, $array);
        $this->assertSame('GET', $array[0]['method']);
        $this->assertSame('api/users', $array[0]['uri']);
    }

    public function test_is_iterable(): void
    {
        $count = 0;

        foreach ($this->collection as $route) {
            $this->assertInstanceOf(RouteDefinition::class, $route);
            $count++;
        }

        $this->assertSame(5, $count);
    }
}
