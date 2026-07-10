<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit\Config;

use Yab\LaravelApiContract\Config\Configuration;
use Yab\LaravelApiContract\Tests\TestCase;

class ConfigurationTest extends TestCase
{
    private Configuration $configuration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configuration = $this->app->make(Configuration::class);
    }

    public function test_returns_default_frontend_framework(): void
    {
        $this->assertSame('react', $this->configuration->frontendFramework());
    }

    public function test_returns_default_auth_driver(): void
    {
        $this->assertSame('sanctum', $this->configuration->authenticationDriver());
    }

    public function test_returns_config_overrides(): void
    {
        $this->app['config']->set('api-contract.frontend_framework', 'vue');

        $this->assertSame('vue', $this->configuration->frontendFramework());
    }
}
