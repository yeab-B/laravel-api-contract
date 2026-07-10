<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Yab\LaravelApiContract\Providers\LaravelApiContractServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelApiContractServiceProvider::class,
        ];
    }
}
