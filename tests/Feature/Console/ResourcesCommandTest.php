<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Feature\Console;

use Illuminate\Support\Facades\Route;
use Yab\LaravelApiContract\Tests\Support\TestControllers\UserResourceController;
use Yab\LaravelApiContract\Tests\TestCase;

class ResourcesCommandTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        Route::middleware('api')->prefix('api')->group(function () {
            Route::get('users', [UserResourceController::class, 'index']);
            Route::get('users/{id}', [UserResourceController::class, 'show']);
        });
    }

    public function test_command_succeeds(): void
    {
        $this->artisan('api-contract:resources')
            ->assertSuccessful();
    }

    public function test_command_output_contains_resource_info(): void
    {
        $this->artisan('api-contract:resources')
            ->expectsOutputToContain('api/users')
            ->expectsOutputToContain('Response Fields')
            ->assertSuccessful();
    }
}
