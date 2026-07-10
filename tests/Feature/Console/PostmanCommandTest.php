<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Feature\Console;

use Illuminate\Support\Facades\Route;
use Yab\LaravelApiContract\Tests\Support\TestControllers\UserController;
use Yab\LaravelApiContract\Tests\TestCase;

class PostmanCommandTest extends TestCase
{
    private string $outputPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outputPath = tempnam(sys_get_temp_dir(), 'postman-test-');
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
        $this->artisan('api-contract:postman')
            ->assertSuccessful();
    }

    public function test_command_writes_to_file(): void
    {
        $this->artisan('api-contract:postman', ['--path' => $this->outputPath])
            ->assertSuccessful();

        $this->assertFileExists($this->outputPath);

        $content = file_get_contents($this->outputPath);
        $this->assertJson($content);
    }

    public function test_generated_postman_has_correct_structure(): void
    {
        $this->artisan('api-contract:postman', ['--path' => $this->outputPath])
            ->assertSuccessful();

        $content = json_decode(file_get_contents($this->outputPath), true);

        $this->assertArrayHasKey('info', $content);
        $this->assertArrayHasKey('item', $content);
        $this->assertArrayHasKey('variable', $content);
    }

    public function test_generated_postman_contains_routes(): void
    {
        $this->artisan('api-contract:postman', ['--path' => $this->outputPath])
            ->assertSuccessful();

        $content = json_decode(file_get_contents($this->outputPath), true);

        $this->assertCount(1, $content['item']);
        $this->assertSame('Users', $content['item'][0]['name']);
        $this->assertCount(2, $content['item'][0]['item']);
    }

    public function test_generated_postman_has_variables(): void
    {
        $this->artisan('api-contract:postman', ['--path' => $this->outputPath])
            ->assertSuccessful();

        $content = json_decode(file_get_contents($this->outputPath), true);

        $baseUrlVars = array_values(array_filter(
            $content['variable'],
            fn (array $v): bool => $v['key'] === 'base_url',
        ));

        $this->assertCount(1, $baseUrlVars);
    }
}
