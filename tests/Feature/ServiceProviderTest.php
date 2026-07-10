<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Feature;

use Yab\LaravelApiContract\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function test_config_is_merged(): void
    {
        $this->assertSame(
            'react',
            $this->app['config']->get('api-contract.frontend_framework'),
        );
    }

    public function test_registers_configuration_binding(): void
    {
        $this->assertInstanceOf(
            \Yab\LaravelApiContract\Config\Configuration::class,
            $this->app->make(\Yab\LaravelApiContract\Config\Configuration::class),
        );
    }

    public function test_config_file_is_publishable(): void
    {
        $this->assertFileExists(
            realpath(__DIR__ . '/../../config/api-contract.php'),
        );
    }
}
