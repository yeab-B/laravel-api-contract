<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Feature\Console;

use Illuminate\Support\Facades\Route;
use Yab\LaravelApiContract\Tests\Support\TestControllers\UserController;
use Yab\LaravelApiContract\Tests\TestCase;

class BuildCommandTest extends TestCase
{
    private string $outputPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outputPath = tempnam(sys_get_temp_dir(), 'api-contract-');
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
        $this->artisan('api-contract:build', ['--path' => $this->outputPath])
            ->assertSuccessful();
    }

    public function test_command_creates_json_file(): void
    {
        $this->artisan('api-contract:build', ['--path' => $this->outputPath])
            ->assertSuccessful();

        $this->assertFileExists($this->outputPath);

        $content = file_get_contents($this->outputPath);
        $this->assertJson($content);
    }

    public function test_command_output_contains_endpoints(): void
    {
        $this->artisan('api-contract:build', ['--path' => $this->outputPath])
            ->expectsOutputToContain('Endpoints discovered')
            ->assertSuccessful();
    }

    public function test_generated_json_has_correct_structure(): void
    {
        $this->artisan('api-contract:build', ['--path' => $this->outputPath])
            ->assertSuccessful();

        $content = json_decode(file_get_contents($this->outputPath), true);

        $this->assertArrayHasKey('name', $content);
        $this->assertArrayHasKey('version', $content);
        $this->assertArrayHasKey('authentication', $content);
        $this->assertArrayHasKey('endpoints', $content);
        $this->assertArrayHasKey('metadata', $content);
        $this->assertCount(2, $content['endpoints']);
    }

    public function test_generated_json_contains_route_details(): void
    {
        $this->artisan('api-contract:build', ['--path' => $this->outputPath])
            ->assertSuccessful();

        $content = json_decode(file_get_contents($this->outputPath), true);

        $methods = array_map(fn ($e) => $e['method'], $content['endpoints']);
        $uris = array_map(fn ($e) => $e['uri'], $content['endpoints']);

        $this->assertContains('GET', $methods);
        $this->assertContains('POST', $methods);
        $this->assertContains('api/users', $uris);
    }
}
