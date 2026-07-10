<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Feature\Console;

use Illuminate\Support\Facades\Route;
use Yab\LaravelApiContract\Tests\Support\TestControllers\UserController;
use Yab\LaravelApiContract\Tests\TestCase;

class TestCommandTest extends TestCase
{
    private string $outputDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outputDir = sys_get_temp_dir() . '/postman-test-' . uniqid();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->outputDir)) {
            $files = glob($this->outputDir . '/*');

            if ($files !== false) {
                foreach ($files as $file) {
                    unlink($file);
                }
            }

            rmdir($this->outputDir);
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
        $this->artisan('api-contract:tests')
            ->assertSuccessful();
    }

    public function test_command_writes_to_directory(): void
    {
        $this->artisan('api-contract:tests', ['--output' => $this->outputDir])
            ->assertSuccessful();

        $this->assertFileExists($this->outputDir . '/UserTest.php');

        $content = file_get_contents($this->outputDir . '/UserTest.php');

        $this->assertStringContainsString('class UserTest extends TestCase', $content);
    }

    public function test_generated_test_has_php_structure(): void
    {
        $this->artisan('api-contract:tests', ['--output' => $this->outputDir])
            ->assertSuccessful();

        $content = file_get_contents($this->outputDir . '/UserTest.php');

        $this->assertStringContainsString('<?php', $content);
        $this->assertStringContainsString('declare(strict_types=1);', $content);
        $this->assertStringContainsString('namespace Tests\Feature\API;', $content);
    }

    public function test_generated_test_contains_routes(): void
    {
        $this->artisan('api-contract:tests', ['--output' => $this->outputDir])
            ->assertSuccessful();

        $content = file_get_contents($this->outputDir . '/UserTest.php');

        $this->assertStringContainsString('test_can_list_users', $content);
        $this->assertStringContainsString('test_can_create_user', $content);
    }
}
