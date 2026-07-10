<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Feature\Console;

use Illuminate\Support\Facades\Route;
use Yab\LaravelApiContract\Tests\Support\TestControllers\UserController;
use Yab\LaravelApiContract\Tests\TestCase;

class MarkdownCommandTest extends TestCase
{
    private string $outputPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outputPath = tempnam(sys_get_temp_dir(), 'markdown-test-');
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
        $this->artisan('api-contract:docs')
            ->assertSuccessful();
    }

    public function test_command_writes_to_file(): void
    {
        $this->artisan('api-contract:docs', ['--path' => $this->outputPath])
            ->assertSuccessful();

        $this->assertFileExists($this->outputPath);

        $content = file_get_contents($this->outputPath);

        $this->assertStringContainsString('Users', $content);
    }

    public function test_generated_markdown_has_correct_structure(): void
    {
        $this->artisan('api-contract:docs', ['--path' => $this->outputPath])
            ->assertSuccessful();

        $content = file_get_contents($this->outputPath);

        $this->assertStringContainsString('#', $content);
        $this->assertStringContainsString('**Version:**', $content);
        $this->assertStringContainsString('## API Endpoints', $content);
    }

    public function test_generated_markdown_contains_routes(): void
    {
        $this->artisan('api-contract:docs', ['--path' => $this->outputPath])
            ->assertSuccessful();

        $content = file_get_contents($this->outputPath);

        $this->assertStringContainsString('GET /api/users', $content);
        $this->assertStringContainsString('POST /api/users', $content);
    }
}
