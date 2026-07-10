<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Feature\Console;

use Illuminate\Support\Facades\Route;
use Yab\LaravelApiContract\Tests\TestCase;

class RoutesCommandTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        Route::middleware('api')->prefix('api')->group(function () {
            Route::get('users', [self::class, 'dummy']);
            Route::post('users', [self::class, 'dummy']);
            Route::get('users/{id}', [self::class, 'dummy']);
        });
    }

    public function dummy(): void
    {
    }

    public function test_command_succeeds(): void
    {
        $this->artisan('api-contract:routes')
            ->assertSuccessful();
    }

    public function test_command_output_contains_http_methods(): void
    {
        $this->artisan('api-contract:routes')
            ->expectsOutputToContain('GET')
            ->expectsOutputToContain('POST')
            ->assertSuccessful();
    }

    public function test_command_output_contains_uri(): void
    {
        $this->artisan('api-contract:routes')
            ->expectsOutputToContain('api/users')
            ->assertSuccessful();
    }
}
