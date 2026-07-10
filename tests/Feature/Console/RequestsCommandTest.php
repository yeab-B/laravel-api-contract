<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Feature\Console;

use Illuminate\Support\Facades\Route;
use Yab\LaravelApiContract\Tests\Support\TestControllers\UserController;
use Yab\LaravelApiContract\Tests\TestCase;

class RequestsCommandTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        Route::middleware('api')->prefix('api')->group(function () {
            Route::post('users', [UserController::class, 'store']);
        });
    }

    public function test_command_succeeds(): void
    {
        $this->artisan('api-contract:requests')
            ->assertSuccessful();
    }

    public function test_command_output_contains_route_uri(): void
    {
        $this->artisan('api-contract:requests')
            ->expectsOutputToContain('api/users')
            ->assertSuccessful();
    }
}
