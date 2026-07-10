<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Analyzers;

use Illuminate\Support\Facades\Route;
use Yab\LaravelApiContract\Analyzers\RouteAnalyzer;
use Yab\LaravelApiContract\Tests\TestCase;

class RouteAnalyzerTest extends TestCase
{
    private RouteAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->analyzer = $this->app->make(\Yab\LaravelApiContract\Contracts\RouteAnalyzerContract::class);
    }

    protected function defineRoutes($router): void
    {
        Route::middleware('api')->group(function () {
            Route::get('api/users', [self::class, 'dummy']);
            Route::post('api/users', [self::class, 'dummy']);
            Route::put('api/users/{id}', [self::class, 'dummy']);
            Route::patch('api/users/{id}', [self::class, 'dummy']);
            Route::delete('api/users/{id}', [self::class, 'dummy']);
        });

        Route::middleware('web')->group(function () {
            Route::get('home', [self::class, 'dummy']);
            Route::post('login', [self::class, 'dummy']);
        });
    }

    public function dummy(): void
    {
    }

    public function test_discovers_all_http_methods(): void
    {
        $routes = $this->analyzer->discover();

        $methods = array_map(
            static fn ($r) => $r->method(),
            $routes->all(),
        );

        $this->assertContains('GET', $methods);
        $this->assertContains('POST', $methods);
        $this->assertContains('PUT', $methods);
        $this->assertContains('PATCH', $methods);
        $this->assertContains('DELETE', $methods);
    }

    public function test_discovers_api_routes_only(): void
    {
        $routes = $this->analyzer->discover();

        foreach ($routes->all() as $route) {
            $this->assertStringStartsWith('api/', $route->uri());
        }
    }

    public function test_does_not_include_web_routes(): void
    {
        $routes = $this->analyzer->discover();

        foreach ($routes->all() as $route) {
            $this->assertNotSame('home', $route->uri());
            $this->assertNotSame('login', $route->uri());
        }
    }

    public function test_captures_uri_parameters(): void
    {
        $routes = $this->analyzer->discover();

        $putRoute = $routes->findByMethod('PUT')->all()[0] ?? null;

        $this->assertNotNull($putRoute);
        $this->assertSame(['id'], $putRoute->parameters());
    }

    public function test_discovers_correct_count(): void
    {
        $routes = $this->analyzer->discover();

        $this->assertCount(5, $routes);
    }
}
