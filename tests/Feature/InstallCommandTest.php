<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Feature;

use Illuminate\Support\Facades\File;
use Yab\LaravelApiContract\Tests\TestCase;

class InstallCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $configPath = $this->app->configPath('api-contract.php');

        if (File::exists($configPath)) {
            File::delete($configPath);
        }
    }

    public function test_install_command_publishes_configuration(): void
    {
        $this->artisan('api-contract:install')
            ->expectsOutputToContain('Installing Laravel API Contract')
            ->assertSuccessful();

        $this->assertFileExists($this->app->configPath('api-contract.php'));
    }

    public function test_install_command_is_idempotent(): void
    {
        $this->artisan('api-contract:install')->assertSuccessful();
        $this->artisan('api-contract:install')->assertSuccessful();
    }

    public function test_install_command_with_force_flag(): void
    {
        File::put($this->app->configPath('api-contract.php'), '<?php return [];');

        $this->artisan('api-contract:install --force')
            ->assertSuccessful();
    }
}
