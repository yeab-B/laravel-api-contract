<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Feature\Console;

use Illuminate\Support\Facades\Route;
use Yab\LaravelApiContract\Tests\Support\TestControllers\UserController;
use Yab\LaravelApiContract\Tests\TestCase;

class ControllersCommandTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        Route::middleware('api')->prefix('api')->group(function () {
            Route::get('users', [UserController::class, 'index']);
            Route::post('users', [UserController::class, 'store']);
        });
    }

    public function test_command_succeeds(): void
    {
        $this->artisan('api-contract:controllers')
            ->assertSuccessful();
    }

    public function test_command_output_contains_controller_info(): void
    {
        $this->artisan('api-contract:controllers')
            ->expectsOutputToContain('api/users')
            ->expectsOutputToContain(UserController::class . '@index')
            ->expectsOutputToContain(UserController::class . '@store')
            ->assertSuccessful();
    }
}
