<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Services;

use Illuminate\Support\Facades\File;
use Yab\LaravelApiContract\Services\InstallService;
use Yab\LaravelApiContract\Tests\TestCase;

class InstallServiceTest extends TestCase
{
    private InstallService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(InstallService::class);
    }

    public function test_creates_required_directories(): void
    {
        $path = resource_path('api-docs');

        if (File::exists($path)) {
            File::deleteDirectory($path);
        }

        $this->service->createDirectories();

        $this->assertFileExists($path);
    }

    public function test_detects_if_not_installed(): void
    {
        $configPath = config_path('api-contract.php');

        if (File::exists($configPath)) {
            File::delete($configPath);
        }

        $this->assertFalse($this->service->isInstalled());
    }

    public function test_detects_if_installed(): void
    {
        $this->artisan('api-contract:install');

        $this->assertTrue($this->service->isInstalled());
    }

    public function test_reports_requirements(): void
    {
        $requirements = $this->service->requirements();

        $this->assertArrayHasKey('php', $requirements);
        $this->assertArrayHasKey('laravel', $requirements);
    }
}
