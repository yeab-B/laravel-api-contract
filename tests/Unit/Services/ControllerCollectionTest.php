<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Yab\LaravelApiContract\Services\DTO\ControllerCollection;
use Yab\LaravelApiContract\Services\DTO\ControllerDefinition;

class ControllerCollectionTest extends TestCase
{
    private ControllerCollection $collection;

    protected function setUp(): void
    {
        $this->collection = new ControllerCollection(
            new ControllerDefinition('App\Http\UserController', 'index', 'public', [], 'JsonResponse', []),
            new ControllerDefinition('App\Http\UserController', 'store', 'public', [['name' => 'request', 'type' => 'Request', 'class' => 'Illuminate\Http\Request']], 'JsonResponse', ['Illuminate\Http\Request']),
            new ControllerDefinition('App\Http\PostController', 'index', 'public', [], 'JsonResponse', []),
        );
    }

    public function test_all_returns_all(): void
    {
        $this->assertCount(3, $this->collection->all());
    }

    public function test_find_by_controller(): void
    {
        $found = $this->collection->findByController('App\Http\UserController@index');
        $this->assertNotNull($found);
        $this->assertSame('index', $found->method());

        $notFound = $this->collection->findByController('App\Http\MissingController@index');
        $this->assertNull($notFound);
    }

    public function test_find_by_class(): void
    {
        $results = $this->collection->findByClass('App\Http\UserController');
        $this->assertCount(2, $results);
    }

    public function test_filter(): void
    {
        $filtered = $this->collection->filter(
            static fn (ControllerDefinition $d) => $d->hasDependencies(),
        );

        $this->assertCount(1, $filtered);
    }

    public function test_is_empty(): void
    {
        $empty = new ControllerCollection();
        $this->assertTrue($empty->isEmpty());
        $this->assertFalse($this->collection->isEmpty());
    }

    public function test_to_array(): void
    {
        $array = $this->collection->toArray();
        $this->assertCount(3, $array);
        $this->assertSame('App\Http\UserController', $array[0]['class_name']);
    }

    public function test_is_iterable(): void
    {
        $count = 0;

        foreach ($this->collection as $definition) {
            $this->assertInstanceOf(ControllerDefinition::class, $definition);
            $count++;
        }

        $this->assertSame(3, $count);
    }
}
