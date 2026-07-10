<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Feature\Console;

use Illuminate\Support\Facades\Route;
use Yab\LaravelApiContract\Tests\Support\TestControllers\UserController;
use Yab\LaravelApiContract\Tests\TestCase;

class SwaggerCommandTest extends TestCase
{
    private string $outputPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outputPath = tempnam(sys_get_temp_dir(), 'swagger-test-');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->outputPath)) {
            unlink($this->outputPath);
        }

        parent::tearDown();
    }

    protected function defineRoutes($router): void
    {
        Route::middleware('api')->prefix('api')->group(function () {
            Route::get('users', [UserController::class, 'index']);
            Route::post('users', [UserController::class, 'store']);
        });
    }

    public function test_command_succeeds(): void
    {
        $this->artisan('api-contract:swagger')
            ->assertSuccessful();
    }

    public function test_command_writes_to_file(): void
    {
        $this->artisan('api-contract:swagger', ['--path' => $this->outputPath])
            ->assertSuccessful();

        $this->assertFileExists($this->outputPath);

        $content = file_get_contents($this->outputPath);
        $this->assertJson($content);
    }

    public function test_generated_swagger_has_correct_structure(): void
    {
        $this->artisan('api-contract:swagger', ['--path' => $this->outputPath])
            ->assertSuccessful();

        $content = json_decode(file_get_contents($this->outputPath), true);

        $this->assertSame('3.0.0', $content['openapi']);
        $this->assertArrayHasKey('info', $content);
        $this->assertArrayHasKey('paths', $content);
    }

    public function test_generated_swagger_contains_routes(): void
    {
        $this->artisan('api-contract:swagger', ['--path' => $this->outputPath])
            ->assertSuccessful();

        $content = json_decode(file_get_contents($this->outputPath), true);

        $this->assertArrayHasKey('/api/users', $content['paths']);
        $this->assertArrayHasKey('get', $content['paths']['/api/users']);
        $this->assertArrayHasKey('post', $content['paths']['/api/users']);
    }

    public function test_generated_swagger_contains_operation_summary(): void
    {
        $this->artisan('api-contract:swagger', ['--path' => $this->outputPath])
            ->assertSuccessful();

        $content = json_decode(file_get_contents($this->outputPath), true);

        $getOperation = $content['paths']['/api/users']['get'];
        $this->assertArrayHasKey('summary', $getOperation);
    }
}
