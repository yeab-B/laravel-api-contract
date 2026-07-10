<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function test_package_namespace_is_registered(): void
    {
        $this->assertTrue(
            class_exists(\Yab\LaravelApiContract\Providers\LaravelApiContractServiceProvider::class),
        );
    }
}
