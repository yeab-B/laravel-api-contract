<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Feature\Console;

use Illuminate\Support\Facades\Route;
use Yab\LaravelApiContract\Tests\Support\TestControllers\UserResourceController;
use Yab\LaravelApiContract\Tests\TestCase;

class ClientCommandTest extends TestCase
{
    private string $outputDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outputDir = sys_get_temp_dir() . '/client-test-' . uniqid();
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->outputDir);

        parent::tearDown();
    }

    protected function defineRoutes($router): void
    {
        Route::middleware('api')->prefix('api')->group(function () {
            Route::get('users', [UserResourceController::class, 'index']);
            Route::post('users', [UserResourceController::class, 'store']);
        });
    }

    public function test_command_succeeds(): void
    {
        $this->artisan('api-contract:client')
            ->assertSuccessful();
    }

    public function test_command_writes_to_directory(): void
    {
        $this->artisan('api-contract:client', ['--output' => $this->outputDir])
            ->assertSuccessful();

        $this->assertFileExists($this->outputDir . '/client.ts');
        $this->assertFileExists($this->outputDir . '/user.service.ts');
    }

    public function test_client_file_contains_axios(): void
    {
        $this->artisan('api-contract:client', ['--output' => $this->outputDir])
            ->assertSuccessful();

        $content = file_get_contents($this->outputDir . '/client.ts');

        $this->assertStringContainsString("import axios from 'axios'", $content);
        $this->assertStringContainsString('baseURL', $content);
    }

    public function test_service_file_contains_function(): void
    {
        $this->artisan('api-contract:client', ['--output' => $this->outputDir])
            ->assertSuccessful();

        $content = file_get_contents($this->outputDir . '/user.service.ts');

        $this->assertStringContainsString('getUsers', $content);
        $this->assertStringContainsString('createUser', $content);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
