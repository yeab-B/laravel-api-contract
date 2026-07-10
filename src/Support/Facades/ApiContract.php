<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Support\Facades;

use Illuminate\Support\Facades\Facade;

class ApiContract extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'api-contract.manager';
    }
}
