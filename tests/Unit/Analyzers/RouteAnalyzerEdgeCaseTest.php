<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Analyzers;

use Illuminate\Support\Facades\Route;
use Yab\LaravelApiContract\Tests\TestCase;

class RouteAnalyzerEdgeCaseTest extends TestCase
{
    public function test_discovers_routes_with_custom_api_prefix(): void
    {
        $this->app['config']->set('api-contract.api_prefix', 'api/v1');

        Route::middleware('api')->group(function () {
            Route::get('api/v1/users', [self::class, 'dummy']);
            Route::post('api/v1/users', [self::class, 'dummy']);
        });

        Route::get('api/v2/items', [self::class, 'dummy']);

        $analyzer = $this->app->make(\Yab\LaravelApiContract\Contracts\RouteAnalyzerContract::class);
        $routes = $analyzer->discover();

        $this->assertCount(2, $routes);

        foreach ($routes->all() as $route) {
            $this->assertStringStartsWith('api/v1/', $route->uri());
        }
    }

    public function test_skips_closure_routes(): void
    {
        Route::middleware('api')->group(function () {
            Route::get('api/closure-route', function () {
                return 'closure';
            });
            Route::get('api/controller-route', [self::class, 'dummy']);
        });

        $analyzer = $this->app->make(\Yab\LaravelApiContract\Contracts\RouteAnalyzerContract::class);
        $routes = $analyzer->discover();

        foreach ($routes->all() as $route) {
            $this->assertNotNull($route->controller());
            $this->assertNotSame('api/closure-route', $route->uri());
        }
    }

    public function test_captures_named_routes(): void
    {
        Route::middleware('api')->group(function () {
            Route::get('api/users', [self::class, 'dummy'])->name('users.index');
            Route::post('api/users', [self::class, 'dummy'])->name('users.store');
            Route::get('api/users/{id}', [self::class, 'dummy'])->name('users.show');
        });

        $analyzer = $this->app->make(\Yab\LaravelApiContract\Contracts\RouteAnalyzerContract::class);
        $routes = $analyzer->discover();

        $this->assertSame('users.index', $routes->findByName('users.index')?->name());
        $this->assertSame('users.store', $routes->findByName('users.store')?->name());
        $this->assertSame('users.show', $routes->findByName('users.show')?->name());
    }

    public function test_captures_middleware(): void
    {
        Route::middleware('api')->group(function () {
            Route::get('api/admin', [self::class, 'dummy'])
                ->middleware(['auth:sanctum', 'verified']);
        });

        $analyzer = $this->app->make(\Yab\LaravelApiContract\Contracts\RouteAnalyzerContract::class);
        $routes = $analyzer->discover();

        $this->assertCount(1, $routes);

        $route = $routes->all()[0];

        $this->assertContains('auth:sanctum', $route->middleware());
        $this->assertContains('verified', $route->middleware());
    }

    public function test_discovers_routes_from_resource_controller(): void
    {
        Route::middleware('api')->group(function () {
            Route::resource('api/posts', self::class);
        });

        $analyzer = $this->app->make(\Yab\LaravelApiContract\Contracts\RouteAnalyzerContract::class);
        $routes = $analyzer->discover();

        $uris = array_map(
            static fn ($r) => $r->uri(),
            $routes->all(),
        );

        $this->assertContains('api/posts', $uris);
        $this->assertContains('api/posts/{post}', $uris);
    }

    public function test_returns_empty_collection_when_no_api_routes(): void
    {
        Route::get('welcome', function () {
            return 'welcome';
        });
        Route::post('login', [self::class, 'dummy']);

        $analyzer = $this->app->make(\Yab\LaravelApiContract\Contracts\RouteAnalyzerContract::class);
        $routes = $analyzer->discover();

        $this->assertTrue($routes->isEmpty());
    }

    public function test_discovers_routes_with_group_prefix_and_middleware(): void
    {
        Route::prefix('api')->middleware('api')->group(function () {
            Route::get('users', [self::class, 'dummy']);
            Route::post('users', [self::class, 'dummy']);
            Route::get('users/{user}/posts', [self::class, 'dummy']);
        });

        $analyzer = $this->app->make(\Yab\LaravelApiContract\Contracts\RouteAnalyzerContract::class);
        $routes = $analyzer->discover();

        $uris = array_map(
            static fn ($r) => $r->uri(),
            $routes->all(),
        );

        $this->assertCount(3, $routes);
        $this->assertContains('api/users', $uris);
        $this->assertContains('api/users/{user}/posts', $uris);
    }

    public function test_analyzer_is_reusable(): void
    {
        Route::middleware('api')->group(function () {
            Route::get('api/items', [self::class, 'dummy']);
        });

        $analyzer = $this->app->make(\Yab\LaravelApiContract\Contracts\RouteAnalyzerContract::class);

        $first = $analyzer->discover();
        $second = $analyzer->discover();

        $this->assertEquals($first->count(), $second->count());
    }

    public function test_detects_routes_with_api_middleware_only(): void
    {
        Route::middleware('api')->group(function () {
            Route::get('api/v1/users', [self::class, 'dummy']);
        });

        Route::middleware(['api', 'throttle:60,1'])->group(function () {
            Route::get('internal-api/reports', [self::class, 'dummy']);
        });

        $analyzer = $this->app->make(\Yab\LaravelApiContract\Contracts\RouteAnalyzerContract::class);
        $routes = $analyzer->discover();

        $this->assertGreaterThanOrEqual(2, $routes->count());
    }

    public function dummy(): void
    {
    }
}
